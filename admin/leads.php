<?php
declare(strict_types=1);
require dirname(__DIR__) . '/api/bootstrap.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'");

try {
    $config = site_config();
} catch (Throwable $error) {
    http_response_code(503);
    exit('The private leads page is not configured.');
}
$expectedUser = (string) ($config['admin_user'] ?? '');
$expectedPassword = (string) ($config['admin_password'] ?? '');
if ($expectedUser === '' || $expectedPassword === '') {
    http_response_code(503);
    exit('The private leads page is not configured.');
}
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
if (!$isHttps) {
    http_response_code(403);
    exit('HTTPS is required.');
}
$authUser = $_SERVER['PHP_AUTH_USER'] ?? '';
$authPassword = $_SERVER['PHP_AUTH_PW'] ?? '';
if ($authUser === '' && isset($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Basic ')) {
    $decoded = base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6), true);
    if ($decoded !== false && str_contains($decoded, ':')) {
        [$authUser, $authPassword] = explode(':', $decoded, 2);
    }
}
if (!hash_equals($expectedUser, $authUser) || !hash_equals($expectedPassword, $authPassword)) {
    header('WWW-Authenticate: Basic realm="SB Constructions leads", charset="UTF-8"');
    http_response_code(401);
    exit('Sign in to view enquiries.');
}
try {
    $rows = site_db($config)->query('SELECT created_at, name, email, phone, project_type, location, message, email_status FROM leads ORDER BY created_at DESC LIMIT 200')->fetchAll();
} catch (Throwable $error) {
    error_log('SB Constructions lead list failed: ' . $error->getMessage());
    http_response_code(503);
    exit('The enquiry list is temporarily unavailable.');
}
$escape = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Website enquiries | SB Constructions</title><style>
body{font:15px/1.5 system-ui,sans-serif;margin:0;background:#f7f6f1;color:#1e2a24}header{background:#1d3028;color:white;padding:25px max(24px,5vw)}header a{color:white}main{max-width:1400px;margin:auto;padding:35px 24px}table{width:100%;border-collapse:collapse;background:white}th,td{text-align:left;padding:15px;border-bottom:1px solid #ddd;vertical-align:top}th{background:#e7e9e2}td{min-width:100px}td:nth-child(2){min-width:190px}td:nth-child(4){min-width:220px}details p{white-space:pre-wrap}summary{cursor:pointer;text-decoration:underline}small{color:#68786a}@media(max-width:700px){table,thead,tbody,tr,td{display:block}thead{display:none}tr{margin:0 0 18px;background:white;border:1px solid #ddd}td{border-bottom:0;min-width:0!important;padding:7px 15px}td:first-child{padding-top:17px}td:last-child{padding-bottom:17px}}
</style></head><body><header><a href="/">SB Constructions</a></header><main><h1>Website enquiries</h1><p><?= count($rows) ?> most recent enquiries.</p>
<?php if ($rows): ?><table><thead><tr><th>Date</th><th>Contact</th><th>Project</th><th>Message</th><th>Email notice</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= $escape($row['created_at']) ?> UTC</td><td><strong><?= $escape($row['name']) ?></strong><br><a href="mailto:<?= $escape($row['email']) ?>"><?= $escape($row['email']) ?></a><br><?= $escape($row['phone']) ?></td><td><?= $escape($row['project_type']) ?><br><small><?= $escape($row['location']) ?></small></td><td><details><summary>Read enquiry</summary><p><?= $escape($row['message']) ?></p></details></td><td><?= $escape($row['email_status']) ?></td></tr><?php endforeach; ?>
</tbody></table><?php else: ?><p>No enquiries yet.</p><?php endif; ?></main></body></html>
