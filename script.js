const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('#main-nav');
toggle.addEventListener('click', () => {
  const open = toggle.getAttribute('aria-expanded') !== 'true';
  toggle.setAttribute('aria-expanded', String(open));
  toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
  nav.classList.toggle('open', open);
  document.body.classList.toggle('menu-open', open);
});
nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
  toggle.setAttribute('aria-expanded', 'false');
  toggle.setAttribute('aria-label', 'Open menu');
  nav.classList.remove('open');
  document.body.classList.remove('menu-open');
}));
document.querySelector('#year').textContent = new Date().getFullYear();
