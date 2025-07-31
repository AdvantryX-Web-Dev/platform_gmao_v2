<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est un administrateur
$isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
if (!$isAdmin) {
    header('Location: /platform_gmao/public/index.php?route=equipementsCategory/list');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Historique des modifications - Catégories</title>
    <link rel="icon" type="image/x-icon" href="/platform_gmao/public/images/images.png" />
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
                    <button class="btn btn-primary" id="sidebarTo"><i class="fas fa-bars"></i></button>

                    <h1 class="h3 mb-4 text-gray-800">Historique des modifications - Catégories d'équipements</h1>
                    
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Historique des audits</h6>
                            <a href="/platform_gmao/public/index.php?route=equipementsCategory/list" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Retour à la liste
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Utilisateur</th>
                                            <th>Action</th>
                                            <th>Anciennes valeurs</th>
                                            <th>Nouvelles valeurs</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($auditTrails) && count($auditTrails) > 0): ?>
                                            <?php foreach ($auditTrails as $audit): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($audit['timestamp']) ?></td>
                                                    <td><?= htmlspecialchars($audit['user_id']) ?></td>
                                                    <td>
                                                        <?php if ($audit['action'] === 'add'): ?>
                                                            <span class="badge badge-success">Ajout</span>
                                                        <?php elseif ($audit['action'] === 'update'): ?>
                                                            <span class="badge badge-warning">Modification</span>
                                                        <?php elseif ($audit['action'] === 'delete'): ?>
                                                            <span class="badge badge-danger">Suppression</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-info"><?= htmlspecialchars($audit['action']) ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $oldValues = json_decode($audit['old_values'], true);
                                                        if (is_array($oldValues)) {
                                                            echo '<ul class="mb-0 list-unstyled">';
                                                            foreach ($oldValues as $key => $value) {
                                                                echo '<li><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</li>';
                                                            }
                                                            echo '</ul>';
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $newValues = json_decode($audit['new_values'], true);
                                                        if (is_array($newValues)) {
                                                            echo '<ul class="mb-0 list-unstyled">';
                                                            foreach ($newValues as $key => $value) {
                                                                echo '<li><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</li>';
                                                            }
                                                            echo '</ul>';
                                                        } else {
                                                            echo '-';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5">Aucun historique d'audit disponible.</td>
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
                    pageLength: 10,
                    order: [[0, 'desc']] // Trier par date (première colonne) en ordre décroissant
                });
            });
        </script>
    </div>
</body>

</html> 