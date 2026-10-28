
document.addEventListener('DOMContentLoaded', function () {
    // Confirmation prompts
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            const message = element.dataset.confirm || 'Are you sure?';

            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Mobile sidebar
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    const overlay = document.getElementById('sidebarOverlay');

    function openSidebar() {
        if (!sidebar || !menuToggle || !overlay) return;

        sidebar.classList.add('sidebar-open');
        overlay.classList.add('overlay-visible');
        menuToggle.setAttribute('aria-expanded', 'true');
    }

    function closeSidebar() {
        if (!sidebar || !menuToggle || !overlay) return;

        sidebar.classList.remove('sidebar-open');
        overlay.classList.remove('overlay-visible');
        menuToggle.setAttribute('aria-expanded', 'false');
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', function () {
            if (sidebar && sidebar.classList.contains('sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    if (sidebar) {
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });
});
