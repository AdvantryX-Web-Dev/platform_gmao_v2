<?php
// Récupérer les informations de la machine si disponibles
$machine_id = isset($_GET['machine_id']) ? $_GET['machine_id'] : '';

// Si aucun aléa n'est trouvé
if (empty($aleas)) {
    $machine_info = ['machine_id' => $machine_id, 'smartbox' => '', 'prod_line' => ''];
} else {
    // Utiliser le premier aléa pour obtenir les informations de la machine
    $machine_info = [
        'machine_id' => $aleas[0]['machine_id'] ?? $machine_id,
        'smartbox' => $aleas[0]['smartbox'] ?? '',
        'prod_line' => $aleas[0]['prod_line'] ?? ''
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>GMAO Digitex | by AdvantryX</title>
    <!-- Favicon-- logo dans l'ongle-->
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.6/css/jquery.dataTables.css">
    <!-- les icones-->
    <link href="/public/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="/public/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="/public/js/jquery-3.6.4.min.js"></script>
    <script src="/public/js/jquery.dataTables.min.js"></script>
    <script src="/public/js/chart.js"></script>
    <link rel="stylesheet" href="/public/css/table.css">
    <link rel="stylesheet" href="/public/css/InterventionChefMain.css">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include(__DIR__ . "/../../views/layout/sidebar.php") ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php include(__DIR__ . "/../../views/layout/navbar.php") ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarTo"><i class="fas fa-bars"></i></button>

                    <!-- Informations Machine Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Informations Machine</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>ID Machine:</strong> <?php echo htmlspecialchars($machine_info['machine_id']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>SmartBox:</strong> <?php echo htmlspecialchars($machine_info['smartbox']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Ligne de production:</strong> <?php echo htmlspecialchars($machine_info['prod_line']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Liste des aléas pour la machine <?php echo htmlspecialchars($machine_id); ?></h6>
                                <a href="?route=intervention_aleas" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Retour à la liste
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Type d'aléa</th>
                                            <th>Opérateur</th>
                                            <th>Date demande</th>
                                            <th>Maintenancier</th>
                                            <th>Date fin</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($aleas)): ?>
                                            <?php foreach ($aleas as $alea): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($alea['id'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($alea['aleas_type_name'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($alea['operator_name'] ?? ''); ?></td>
                                                    <td><?php echo !empty($alea['created_at']) ? date('d/m/Y H:i', strtotime($alea['created_at'])) : ''; ?></td>
                                                    <td><?php echo htmlspecialchars($alea['monitor_name'] ?? ''); ?></td>
                                                    <td><?php echo !empty($alea['end_date']) ? date('d/m/Y H:i', strtotime($alea['end_date'])) : ''; ?></td>
                                                    <td>
                                                        <?php if (!empty($alea['end_date'])): ?>
                                                            <span class="badge badge-success">Terminé</span>
                                                        <?php elseif (!empty($alea['monitor'])): ?>
                                                            <span class="badge badge-warning">En cours</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">En attente</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End of Main Content -->

                <!-- Footer -->
                <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>
                <!-- End of Footer -->
            </div>
            <!-- End of Content Wrapper -->
        </div>
        <!-- End of Page Wrapper -->

        <script src="/public/js/sideBare.js"></script>
        <script src="/public/js/bootstrap.bundle.min.js"></script>
        <script src="/public/js/sb-admin-2.min.js"></script>
        <script src="/public/js/dataTables.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function() {
                // Configuration DataTables avec gestion des tables vides
                $('#dataTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.6/i18n/fr_fr.json',
                        emptyTable: "Aucun aléa trouvé pour cette machine"
                    },
                    pageLength: 10,
                    order: [
                        [3, 'desc']
                    ], // Tri par date de demande décroissant
                    // Résoudre le problème des DataTables avec tables vides
                    initComplete: function(settings, json) {
                        if ($('#dataTable tbody tr').length === 0) {
                            $('#dataTable tbody').append('<tr><td colspan="7" class="text-center">Aucun aléa trouvé pour cette machine</td></tr>');
                        }
                    }
                });
            });
        </script>
    </div>
</body>

</html>