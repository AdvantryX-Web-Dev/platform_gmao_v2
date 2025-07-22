<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Models\Intervention_type_model;

$intervention_types = Intervention_type_model::findAll();

// Vérifie si l'utilisateur est un administrateur
$isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des types de intervention </title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/public/css/table.css">
    <link rel="stylesheet" href="/public/css/init_Machine.css">
    <link rel="stylesheet" href="/public/css/datatables.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../../views/layout/navbar.php"); ?>
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarTo"><i class="fas fa-bars"></i></button>

                    <?php if (!empty($_SESSION['flash_message'])): ?>
                        <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                            <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Liste des types de intervention :</h6>
                            <?php if ($isAdmin): ?>
                                <div>

                                    <a href="/public/index.php?route=intervention_type/create" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Ajouter un type de intervention
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <?php if ($isAdmin): ?>
                                                <th style="width: 5%;">Actions</th>
                                            <?php endif; ?>
                                            <th>Designation</th>
                                            <th>Type</th>
                                            <th>Code</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($intervention_types) && count($intervention_types) > 0): ?>
                                            <?php foreach ($intervention_types as $intervention_type): ?>
                                                <tr>
                                                    <?php if ($isAdmin): ?>
                                                        <td>
                                                            <a href="/public/index.php?route=intervention_type/edit&id=<?= urlencode($intervention_type['id']) ?>"><i class="fas fa-edit m-2"></i></a>
                                                            <a href="/public/index.php?route=intervention_type/delete&id=<?= urlencode($intervention_type['id']) ?>" onclick="return confirm('Supprimer ce type de intervention ?');">
                                                                <i class="fas fa-trash text-danger"></i>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td><?= htmlspecialchars($intervention_type['designation'] ?? '') ?></td>
                                                    <td>
                                                        <?php if ($intervention_type['type'] == 'preventive') {
                                                            echo '<span class="badge badge-success">Preventive</span>';
                                                        } else {
                                                            echo '<span class="badge badge-warning">Curative</span>';
                                                        } ?></td>
                                                    <td><?= htmlspecialchars($intervention_type['code'] ?? '') ?></td>

                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4">Aucun type de intervention trouvé.</td>
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
        <script src="/public/js/jquery-3.6.4.min.js"></script>
        <script src="/public/js/jquery.dataTables.min.js"></script>
        <script src="/public/js/sideBare.js"></script>
        <script src="/public/js/bootstrap.bundle.min.js"></script>
        <script src="/public/js/sb-admin-2.min.js"></script>
        <script src="/public/js/dataTables.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#dataTable').DataTable({
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
                    pageLength: 10
                });
                // Faire disparaître les messages flash après 3 secondes
                setTimeout(function() {
                    $("#flash-message").fadeOut("slow");
                }, 4000);
            });
        </script>
    </div>
</body>

</html>