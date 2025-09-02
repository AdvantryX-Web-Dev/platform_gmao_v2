<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggleTop');

            const $toggleBtn = $('#sidebarToggleTop');
            const $sidebar = $('#accordionSidebar');
            // FORCER LE MASQUAGE INITIAL - TOUJOURS CACHÉ PAR DÉFAUT
            // Cibler uniquement les tablettes (ex : largeur entre 768px et 1024px)
            if (window.matchMedia("(max-width: 768px) ").matches) {
                $sidebar.css({
                    'display': 'none',
                    'visibility': 'hidden',
                    'opacity': '0'
                });
            }


            // Event listener pour le bouton toggle
            $toggleBtn.off('click').on('click', function(e) {
                e.preventDefault();

                const currentDisplay = $sidebar.css('display');

                if (currentDisplay === 'none') {
                    $sidebar.css({
                        'display': 'block',
                        'visibility': 'visible',
                        'opacity': '1'
                    });
                } else {
                    $sidebar.css({
                        'display': 'none',
                        'visibility': 'hidden',
                        'opacity': '0'
                    });
                }
            });

        });
    </script>
    <ul class="navbar-nav ml-auto align-items-center">
        <!-- Icône alerte -->
        <li class="nav-item mx-2">
            <a class="nav-link" href="#">
                <i class="fas fa-bell fa-lg text-gray-600"></i>
            </a>
        </li>
        <!-- Icône settings avec menu au hover -->
        <li class="nav-item dropdown mx-2" style="position:relative;">
            <a class="nav-link" href="#" id="settingsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-cog fa-lg text-gray-600"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="settingsDropdown">
                <a class="dropdown-item" href="../../platform_gmao/public/index.php?route=compte/edit_email&id=<?= urlencode($_SESSION['matricule'] ?? '') ?>">
                    <i class="fas fa-envelope fa-sm fa-fw mr-2 text-gray-400"></i>
                    Paramètres du compte
                </a>
            </div>
        </li>
        <!-- Icône logout avec tooltip au hover -->
        <li class="nav-item mx-2">
            <a class="nav-link" href="../../platform_gmao/public/index.php?route=logout" title="Se déconnecter" data-toggle="tooltip" data-placement="bottom">
                <i class="fas fa-sign-out-alt fa-lg text-gray-600"></i>
            </a>
        </li>
        <!-- Cercle avec la première lettre de l'email, cliquable pour éditer le compte -->
        <?php if (isset($_SESSION['email'])):
            $email = $_SESSION['email'];
            $firstLetter = strtoupper(substr($email, 0, 1));
        ?>
            <li class="nav-item mx-2">
                <a href="../../platform_gmao/public/index.php?route=compte/update_compte&id=<?= urlencode($_SESSION['matricule'] ?? '') ?>" title="Modifier mon compte">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:40px; height:40px; font-size:1.2rem; cursor:pointer;" data-toggle="tooltip" data-placement="bottom" title="<?= htmlspecialchars($email) ?>">
                        <?= $firstLetter ?>
                    </div>
                </a>
            </li>
        <?php endif; ?>
    </ul>
    <script>
        // Active le tooltip Bootstrap
        $(function() {
            $('[data-toggle=\"tooltip\"]').tooltip();
        });
    </script>
    <style>
        /* Affiche le menu settings au hover */
        #settingsDropdown:hover+.dropdown-menu,
        .dropdown-menu:hover {
            display: block !important;
        }
    </style>
</nav>