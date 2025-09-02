<?php

use App\Models\Implantation_Prod_model;


// Initialisation du filtre chaîne AVANT tout HTML
$chaines = Implantation_Prod_model::findAllChaines();

$selectedprodline_id = isset($_GET['id']) ? $_GET['id'] : '';
$selectedChaine = isset($_GET['chaine']) ? $_GET['chaine'] : '';

if ($selectedChaine === '') {
    $selectedChaine = $chaines[0]['id'];
}

$prodline_id = $selectedChaine;
$findChaineById = Implantation_Prod_model::findChaineById($prodline_id);
$nomCh = $findChaineById ? $findChaineById[0]['prod_line'] : $selectedChaine;

// Récupérer les dates de filtre
$date_debut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-1 month'));
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

// Passer les dates au contrôleur


$interventionController = new \App\Controllers\InterventionController();
$aleas = $interventionController->getAleasProduction($nomCh, $date_debut, $date_fin);

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
    <link href="/platform_gmao/public/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="/platform_gmao/public/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
    <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
    <script src="/platform_gmao/public/js/chart.js"></script>
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/InterventionChefMain.css">
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


                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Liste des aléas en production
                                    <?php if (!empty($nomCh)): ?>
                                        pour la chaîne <?php echo htmlspecialchars($nomCh); ?>
                                    <?php endif; ?>
                                </h6>
                                <div class="d-flex">
                                    <form method="get" class="form-inline mb-0 mr-2">
                                        <input type="hidden" name="route" value="intervention_aleas">
                                        <select name="chaine" id="chaine" class="form-control" onchange="this.form.submit()">
                                            <option value="">-- Toutes les chaînes --</option>
                                            <?php
                                            foreach ($chaines as $ch) {
                                                $selected = ($selectedChaine == $ch['id']) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($ch['id']) . '" ' . $selected . '>' . htmlspecialchars($ch['prod_line']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </form>
                                    <a href="?route=intervention_curative" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Retour
                                    </a>
                                </div>

                            </div>

                        </div>
                        <!-- Filtre de date -->
                        <div class="card-body border-bottom date-filter-section">
                            <form id="dateFilterForm" method="GET" class="row justify-content-end align-items-end ">
                                <input type="hidden" name="route" value="intervention_aleas">
                                <input type="hidden" name="chaine" value="<?= htmlspecialchars($selectedChaine) ?>">
                                <div class="col-md-3">
                                    <label for="date_debut" class="form-label">Date de début :</label>
                                    <input type="date" class="form-control" id="date_debut" name="date_debut"
                                        value="<?= $_GET['date_debut'] ?? date('Y-m-d', strtotime('-1 month')) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="date_fin" class="form-label">Date de fin :</label>
                                    <input type="date" class="form-control" id="date_fin" name="date_fin"
                                        value="<?= $_GET['date_fin'] ?? date('Y-m-d') ?>" required>
                                </div>
                            </form>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Machine</th>
                                            <th>SmartBox</th>
                                            <th>Type d'aléa</th>
                                            <th>Nombre d'aléas</th>
                                            <th>Opérateur</th>
                                            <th>Dernière demande</th>
                                            <th>Maintenancier</th>
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($aleas)): ?>
                                            <?php foreach ($aleas as $alea): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($alea['machine_id'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($alea['smartbox'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($alea['aleas_type_name'] ?? ''); ?></td>
                                                    <td>
                                                        <a href="?route=intervention_aleas_machine&machine_id=<?php echo urlencode($alea['machine_id']); ?>" class="badge badge-info">
                                                            <?php echo htmlspecialchars($alea['nb_aleas'] ?? '0'); ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($alea['operator_name'] ?? ''); ?></td>
                                                    <td><?php echo !empty($alea['created_at']) ? date('d/m/Y H:i', strtotime($alea['created_at'])) : ''; ?></td>
                                                    <td><?php echo htmlspecialchars($alea['monitor_name'] ?? ''); ?></td>
                                                    <td>
                                                        <?php if ($alea['status'] === 'Terminé'): ?>
                                                            <span class="badge badge-success">Terminé</span>
                                                        <?php elseif ($alea['status'] === 'En cours'): ?>
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


            </div>
            <!-- Footer -->
            <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>
            <!-- End of Footer -->
            <!-- End of Content Wrapper -->
        </div>
        <!-- End of Page Wrapper -->

        <script src="/platform_gmao/public/js/sideBare.js"></script>
        <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
        <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
        <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function() {
                // Configuration DataTables avec gestion des tables vides
                $('#dataTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.6/i18n/fr_fr.json',
                        emptyTable: "Aucune donnée disponible"
                    },
                    pageLength: 10,
                    order: [
                        [4, 'desc']
                    ], // Tri par date de demande décroissant
                    // Résoudre le problème des DataTables avec tables vides
                    initComplete: function(settings, json) {
                        if ($('#dataTable tbody tr').length === 0) {
                            $('#dataTable tbody').append('<tr><td colspan="9" class="text-center">Aucune donnée disponible</td></tr>');
                        }
                    }
                });
                // Soumission automatique du filtre dès qu'une date change
                $('#date_debut, #date_fin').on('change', function() {
                    var dateDebut = $('#date_debut').val();
                    var dateFin = $('#date_fin').val();
                    // Validation simple
                    if (dateDebut && dateFin && dateDebut <= dateFin) {
                        $('#date_fin').removeClass('is-invalid');
                        $('#date_fin').next('.invalid-feedback').remove();
                        $('#dateFilterForm')[0].submit();
                    } else if (dateDebut && dateFin && dateDebut > dateFin) {
                        $('#date_fin').addClass('is-invalid');
                        if (!$('#date_fin').next('.invalid-feedback').length) {
                            $('#date_fin').after('<div class="invalid-feedback">La date de fin doit être postérieure à la date de début</div>');
                        }
                    }
                });
            });
        </script>
    </div>
</body>

</html>