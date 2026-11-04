<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    site_json(['error' => 'Method not allowed'], 405);
}
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) {
    site_json(['error' => 'Expected JSON'], 415);
}
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 16000) {
    site_json(['error' => 'Submission is too large'], 413);
}
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host = $_SERVER['HTTP_HOST'] ?? '';
if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== preg_replace('/:\d+$/', '', $host)) {
    site_json(['error' => 'Invalid origin'], 403);
}
$raw = file_get_contents('php://input', false, null, 0, 16001);
if ($raw === false || strlen($raw) > 16000) {
    site_json(['error' => 'Submission is too large'], 413);
}
$body = json_decode($raw, true);
if (!is_array($body)) {
    site_json(['error' => 'Invalid JSON'], 400);
}
$field = static fn(string $key): string => isset($body[$key]) && is_string($body[$key]) ? trim($body[$key]) : '';
if ($field('website') !== '') {
    site_json(['ok' => true], 200);
}
$name = $field('name');
$email = $field('email');
$phone = $field('phone');
$projectType = $field('projectType');
$location = $field('location');
$message = $field('message');
$types = ['New-build villa', 'Complete renovation', 'Interiors and finishes', 'Commercial project', 'Other'];
if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || mb_strlen($phone) > 50 || !in_array($projectType, $types, true) || mb_strlen($location) > 120
    || mb_strlen($message) < 10 || mb_strlen($message) > 3000 || ($body['consent'] ?? false) !== true) {
    site_json(['error' => 'Please check the required fields'], 400);
}

$id = bin2hex(random_bytes(16));
$createdAt = gmdate('Y-m-d H:i:s');
try {
    $config = site_config();
    $db = site_db($config);
    $save = $db->prepare('INSERT INTO leads (id, created_at, name, email, phone, project_type, location, message, consent_at, email_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $save->execute([$id, $createdAt, $name, $email, $phone ?: null, $projectType, $location ?: null, $message, $createdAt, 'pending']);
} catch (Throwable $error) {
    error_log('SB Constructions lead save failed: ' . $error->getMessage());
    site_json(['error' => 'Unable to save enquiry'], 503);
}

$status = 'pending';
$emailError = 'Email service is not configured';
if (!empty($config['resend_api_key']) && !empty($config['lead_from']) && !empty($config['lead_to']) && function_exists('curl_init')) {
    $notice = implode("\n", [
        'New website enquiry', 'Reference: ' . $id, '', 'Name: ' . $name, 'Email: ' . $email,
        'Phone: ' . ($phone ?: 'Not provided'), 'Project type: ' . $projectType,
        'Location: ' . ($location ?: 'Not provided'), '', 'Project details:', $message,
    ]);
    $request = curl_init('https://api.resend.com/emails');
    if ($request !== false) {
        curl_setopt_array($request, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $config['resend_api_key'], 'Content-Type: application/json', 'Idempotency-Key: lead-enquiry/' . $id],
            CURLOPT_POSTFIELDS => json_encode([
                'from' => $config['lead_from'], 'to' => [$config['lead_to']], 'reply_to' => $email,
                'subject' => 'New SB Constructions enquiry: ' . $projectType, 'text' => $notice,
            ]),
        ]);
        curl_exec($request);
        $code = curl_getinfo($request, CURLINFO_RESPONSE_CODE);
        if ($code >= 200 && $code < 300) {
            $status = 'sent';
            $emailError = null;
        } else {
            $emailError = $code ? 'Email provider returned ' . $code : 'Email provider could not be reached';
            error_log('SB Constructions lead ' . $id . ': ' . $emailError);
        }
        curl_close($request);
    }
}
try {
    $update = $db->prepare('UPDATE leads SET email_status = ?, email_error = ? WHERE id = ?');
    $update->execute([$status, $emailError, $id]);
} catch (Throwable $error) {
    error_log('SB Constructions email status update failed: ' . $error->getMessage());
}
site_json(['ok' => true, 'reference' => $id], 201);
