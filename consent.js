(() => {
  const storageKey = 'sb-constructions-ads-consent-v1';

  function readChoice() {
    try { return localStorage.getItem(storageKey); } catch { return null; }
  }

  function saveChoice(choice) {
    try { localStorage.setItem(storageKey, choice); } catch { /* Continue for this page. */ }
  }

  function setChoice(choice, banner) {
    saveChoice(choice);
    banner.hidden = true;
    const value = choice === 'accepted' ? 'granted' : 'denied';
    window.gtag('consent', 'update', {
      ad_storage: value,
      ad_user_data: value,
      ad_personalization: value,
      analytics_storage: value
    });
  }

  function ready() {
    const banner = document.createElement('section');
    banner.className = 'consent-banner';
    banner.setAttribute('aria-label', 'Advertising cookies');
    banner.innerHTML = '<div><strong>Advertising cookies</strong><p>Google tags run with advertising cookies disabled unless you accept. May we use cookies to measure our advertising? You can change your choice anytime.</p></div><div class="consent-actions"><button type="button" data-consent="rejected">Reject</button><button type="button" data-consent="accepted">Accept</button></div>';
    document.body.appendChild(banner);
    banner.querySelectorAll('[data-consent]').forEach(button => {
      button.addEventListener('click', () => setChoice(button.dataset.consent, banner));
    });

    const settings = document.createElement('button');
    settings.type = 'button';
    settings.className = 'consent-settings';
    settings.textContent = 'Cookie settings';
    settings.addEventListener('click', () => { banner.hidden = false; banner.querySelector('button').focus(); });
    document.querySelector('.footer-bottom')?.appendChild(settings);

    const choice = readChoice();
    banner.hidden = choice === 'accepted' || choice === 'rejected';
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
  else ready();
})();
