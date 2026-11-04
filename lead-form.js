const form = document.querySelector('#lead-form');

if (form) {
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const status = form.querySelector('.form-status');
    status.classList.remove('error');
    status.textContent = '';
    if (!form.reportValidity()) return;

    const submit = form.querySelector('button[type="submit"]');
    submit.disabled = true;
    const fields = new FormData(form);
    const payload = {
      name: String(fields.get('name') || '').trim(),
      email: String(fields.get('email') || '').trim(),
      phone: String(fields.get('phone') || '').trim(),
      projectType: String(fields.get('projectType') || '').trim(),
      location: String(fields.get('location') || '').trim(),
      message: String(fields.get('message') || '').trim(),
      consent: fields.get('consent') === 'on',
      website: String(fields.get('website') || ''),
    };

    try {
      const response = await fetch('/api/leads.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      if (!response.ok) throw new Error(`Submission failed: ${response.status}`);
      form.reset();
      status.textContent = 'Thank you. Your enquiry has been received. We will be in touch soon.';
    } catch {
      status.classList.add('error');
      status.textContent = 'We could not send your enquiry right now. Please try again shortly.';
    } finally {
      submit.disabled = false;
    }
  });
}
