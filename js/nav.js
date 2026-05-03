// ============================================================
// jesse.ventures — Navigation
// Marks the active nav link based on current URL path
// ============================================================

(function () {
  const path = window.location.pathname;
  const links = document.querySelectorAll('.site-nav__links a');

  links.forEach(function (link) {
    const href = link.getAttribute('href');
    if (href === '/' && path === '/') {
      link.classList.add('active');
    } else if (href !== '/' && path.startsWith(href)) {
      link.classList.add('active');
    }
  });
})();
