document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('sidebarTo');
    var sidebar = document.getElementById('accordionSidebar');

    // Hide sidebar by default on small screens (tablet/mobile)
    if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
        if (sidebar) sidebar.style.display = 'none';
    }

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            if (sidebar.style.display === 'none' || sidebar.style.display === '') {
                sidebar.style.display = 'block';
            } else {
                sidebar.style.display = 'none';
            }
        });
    }
});