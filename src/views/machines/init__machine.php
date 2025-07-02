<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Models\Machine_model;

$machines = Machine_model::findAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des machines</title>
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
        <?php include(__DIR__ . "/../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../views/layout/navbar.php"); ?>
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarTo"><i class="fas fa-bars"></i></button>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Liste des machines :</h6>
                            <a href="../../public/index.php?route=machine/create" class="btn btn-success">Ajouter une machine</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Actions</th>
                                            <th>Machine ID</th>
                                            <th>Référence</th>
                                            <th>Marque</th>
                                            <th>Type</th>
                                            <th>Désignation</th>
                                            <th>Numéro Facture</th>
                                            <th>Date Facture</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($machines) && count($machines) > 0): ?>
                                            <?php foreach ($machines as $machine): ?>
                                                <tr>
                                                    <td>
                                                        <a href="../../public/index.php?route=machine/edit&id=<?= urlencode($machine['machine_id'] ?? '') ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i></a>
                                                        <a href="../../public/index.php?route=machine/delete&id=<?= urlencode($machine['machine_id'] ?? '') ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cette machine ?');"><i class="fas fa-trash"></i></a>
                                                    </td>
                                                    <td><?= htmlspecialchars($machine['machine_id'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($machine['reference'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($machine['brand'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($machine['type'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($machine['designation'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($machine['billing_num'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($machine['bill_date'] ?? '') ?></td>

                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10">Aucune machine trouvée.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>
            </div>
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
            });
        </script>
    </div>
</body>

</html>