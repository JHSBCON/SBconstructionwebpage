(() => {
  const storageKey = 'sb-constructions-ads-consent-v1';
  const containerId = 'GTM-TQXJNLLV';
  let loaded = false;

  window.dataLayer = window.dataLayer || [];
  window.gtag = window.gtag || function gtag() { window.dataLayer.push(arguments); };

  function readChoice() {
    try { return localStorage.getItem(storageKey); } catch { return null; }
  }

  function saveChoice(choice) {
    try { localStorage.setItem(storageKey, choice); } catch { /* Continue for this page. */ }
  }

  function loadGoogleTag() {
    if (loaded) return;
    loaded = true;
    window.gtag('consent', 'default', {
      ad_storage: 'granted',
      ad_user_data: 'granted',
      ad_personalization: 'granted',
      analytics_storage: 'granted'
    });
    window.gtag('js', new Date());
    window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtm.js?id=${containerId}`;
    document.head.appendChild(script);
  }

  function setChoice(choice, banner) {
    saveChoice(choice);
    banner.hidden = true;
    if (choice === 'accepted') {
      loadGoogleTag();
    } else if (loaded) {
      window.gtag('consent', 'update', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: 'denied'
      });
      // A reload removes the already-loaded container after withdrawal.
      location.reload();
    }
  }

  function ready() {
    const banner = document.createElement('section');
    banner.className = 'consent-banner';
    banner.setAttribute('aria-label', 'Advertising cookies');
    banner.innerHTML = '<div><strong>Advertising cookies</strong><p>May we use Google tags to measure our advertising? You can accept or reject. The website works either way.</p></div><div class="consent-actions"><button type="button" data-consent="rejected">Reject</button><button type="button" data-consent="accepted">Accept</button></div>';
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
    if (choice === 'accepted') loadGoogleTag();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready);
  else ready();
})();
