<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Models\Maintainer_model;

$maintainers = Maintainer_model::findAll();

// Vérifie si l'utilisateur est un administrateur
$isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des emplacement</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/init_Machine.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../../views/layout/navbar.php"); ?>
                <div class="container-fluid">


                    <?php if (!empty($_SESSION['flash_message'])): ?>
                        <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                            <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Liste des emplacements</h6>
                            <?php if ($isAdmin): ?>
                                <div>
                                    <a href="../../platform_gmao/public/index.php?route=location/create" class="btn btn-success mr-2">
                                        <i class="fas fa-plus"></i> Ajouter emplacement
                                    </a>
                                   
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTableLocations" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <?php if ($isAdmin): ?>
                                                <th style="width: 8%;">Actions</th>
                                            <?php endif; ?>
                                            <th>Nom</th>
                                            <th>Emplacement</th>
                                           <!-- <th>Type</th> -->

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($Locations)): ?>
                                            <?php foreach ($Locations as $loc): ?>
                                                <tr>
                                                    <?php if ($isAdmin): ?>
                                                        <td>
                                                            <a href="../../platform_gmao/public/index.php?route=location/edit&id=<?= urlencode($loc['id']) ?>">
                                                                <i class="fas fa-edit m-2 text-primary"></i>
                                                            </a>
                                                            <a href="../../platform_gmao/public/index.php?route=location/delete&id=<?= urlencode($loc['id']) ?>"
                                                                onclick="return confirm('Supprimer cet emplacement ?');">
                                                                <i class="fas fa-trash text-danger"></i>
                                                            </a>
                                                        </td>
                                                    <?php endif; ?>

                                                    <td><?= htmlspecialchars($loc['location_name'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($loc['location_category'] ?? '') ?></td>
                                                    <!-- <td>
                                                        <span class="badge badge-<?= $loc['location_type'] === 'machine' ? 'primary' : 'success' ?>">
                                                            <?= htmlspecialchars($loc['location_type'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    -->
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="<?= $isAdmin ? '4' : '3' ?>" class="text-center">
                                                    Aucun emplacement trouvé.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../../../views/layout/footer.php"); ?>
        </div>
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
        <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
        <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
        <script src="/platform_gmao/public/js/sideBare.js"></script>
        <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
        <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
        <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#dataTableLocations').DataTable({
                    language: {
                        search: "Rechercher:",
                        lengthMenu: "Afficher _MENU_ éléments par page",
                        info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
                        infoEmpty: "Aucun élément à afficher",
                        infoFiltered: "(filtré de _MAX_ éléments au total)",
                        zeroRecords: "Aucun enregistrement correspondant trouvé",
                        paginate: {
                            first: "Premier",
                            previous: "Précédent",
                            next: "Suivant",
                            last: "Dernier"
                        }
                    },
                    pageLength: 10,
                    order: [
                        [1, 'asc']
                    ], // Trier par défaut sur la colonne Type
                    columnDefs: [{
                            targets: 1, // Colonne Type
                            width: "15%"
                        },
                        <?php if ($isAdmin): ?> {
                                targets: 0, // Colonne Actions
                                orderable: false,
                                searchable: false
                            }
                        <?php endif; ?>
                    ]
                });

                // Faire disparaître les messages flash après 4 secondes
                setTimeout(function() {
                    $("#flash-message").fadeOut("slow");
                }, 4000);
            });
        </script>
    </div>
</body>

</html>