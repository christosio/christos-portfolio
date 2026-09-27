const button = document.querySelector('.menu-button');
const nav = document.querySelector('.site-nav');
const closeMenu = () => { button.setAttribute('aria-expanded', 'false'); nav.classList.remove('is-open'); };
button.addEventListener('click', () => { const open = button.getAttribute('aria-expanded') !== 'true'; button.setAttribute('aria-expanded', String(open)); nav.classList.toggle('is-open', open); });
nav.addEventListener('click', event => { if (event.target.closest('a')) closeMenu(); });
document.addEventListener('keydown', event => { if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') { closeMenu(); button.focus(); } });
document.addEventListener('click', event => { if (!nav.contains(event.target) && !button.contains(event.target)) closeMenu(); });
window.matchMedia('(min-width: 901px)').addEventListener('change', closeMenu);
document.getElementById('year').textContent = new Date().getFullYear();
