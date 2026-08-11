// SPMS client helpers + mobile menu
document.addEventListener('DOMContentLoaded', () => {
  // Auto-dismiss alerts after 6s
  document.querySelectorAll('.alert-dismissible').forEach((el) => {
    setTimeout(() => {
      const btn = el.querySelector('.btn-close');
      if (btn) btn.click();
    }, 6000);
  });

  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const openBtn = document.getElementById('menuToggle');
  const closeBtn = document.getElementById('sidebarClose');

  function openMenu() {
    if (!sidebar) return;
    sidebar.classList.add('open');
    if (backdrop) {
      backdrop.classList.add('show');
      backdrop.setAttribute('aria-hidden', 'false');
    }
    if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeMenu() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (backdrop) {
      backdrop.classList.remove('show');
      backdrop.setAttribute('aria-hidden', 'true');
    }
    if (openBtn) openBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  if (openBtn) openBtn.addEventListener('click', openMenu);
  if (closeBtn) closeBtn.addEventListener('click', closeMenu);
  if (backdrop) backdrop.addEventListener('click', closeMenu);

  // Close drawer after navigating on mobile
  if (sidebar) {
    sidebar.querySelectorAll('a.nav-link').forEach((link) => {
      link.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 991.98px)').matches) {
          closeMenu();
        }
      });
    });
  }

  window.addEventListener('resize', () => {
    if (window.matchMedia('(min-width: 992px)').matches) {
      closeMenu();
    }
  });
});
