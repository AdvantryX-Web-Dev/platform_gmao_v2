<?php

use App\Models\Implantation_Prod_model;


// Initialisation du filtre chaîne AVANT tout HTML
$chaines = Implantation_Prod_model::findAllChaines();


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
    <style>
        .date-filter-section {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .date-filter-section .form-label {
            font-weight: 600;
            color: #495057;
        }

        .date-filter-section .btn {
            margin-top: 5px;
        }

        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .is-invalid {
            border-color: #dc3545;
        }
    </style>

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

                    <?php
                    if (!empty($_GET['status'])) {
                        switch ($_GET['status']) {
                            case 'succ':
                                $statusType = 'alert-success';
                                $statusMsg = 'La demande de maintenance a été transmise avec succès au maintenancier pour traitement.';
                                break;
                            case 'error':
                                $statusType = 'alert-danger';
                                $statusMsg = 'Désolé, une erreur est survenue lors de la transmission de la demande de maintenance au maintenancier. ';
                                break;
                            default:
                                $statusType = '';
                                $statusMsg = '';
                        }
                    }
                    ?>
                    <?php if (!empty($statusMsg)) { ?>
                        <div class="col-xs-12">
                            <div class="alert <?php echo $statusType; ?> alert-dismissible fade show statusM" id="statusMessage" role="alert">
                                <?php echo $statusMsg; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Fermer">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['error']) ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Fermer">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['success']) ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Fermer">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary"> Liste des interventions préventives sur les Machines de chaine
                                    :
                                </h6>
                                <div class="d-flex align-items-center">

                                    <button class="btn btn-primary mr-2" data-toggle="modal" data-target="#ajoutInterventionPreventiveModal">
                                        <i class="fas fa-tools"></i> Ajouter intervention préventive
                                    </button>
                                    <button class="btn btn-success mr-2" data-toggle="modal" data-target="#planningModal">
                                        <i class="fas fa-calendar-plus"></i> Ajouter au planning
                                    </button>
                                    <?php
                                    // Get count of future interventions
                                    $planningController = new \App\Controllers\InterventionPlanningController();
                                    $futureCount = $planningController->getFutureInterventionsCount();
                                    ?>
                                    <a href="../../platform_gmao/public/index.php?route=intervention_planning/list" class="btn btn-info">
                                        <i class="fas fa-calendar-alt"></i> Liste des planifications
                                        <?php if ($futureCount > 0): ?>
                                            <span class="badge badge-danger ml-1"><?= $futureCount ?></span>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Filtre de date -->
                        <div class="card-body border-bottom date-filter-section">
                            <form id="dateFilterForm" method="GET" class="row justify-content-end align-items-end ">
                                <input type="hidden" name="route" value="intervention_preventive">
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
                                            <th> Machine <i class="fas fa-sort"></i></th>
                                            <th> Désignation <i class="fas fa-sort"></i></th>
                                            <th>SmartBox <i class="fas fa-sort"></i></th>
                                            <th>Nombre D'intervention Préventive <i class="fas fa-sort"></i></th>
                                            <th>Dernière date intervention <i class="fas fa-sort"></i></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php

                                        $interventionController = new \App\Controllers\InterventionController();

                                        // Récupérer les dates de filtre
                                        $date_debut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-1 month'));
                                        $date_fin = $_GET['date_fin'] ?? date('Y-m-d');

                                        // Passer les dates au contrôleur
                                        $machines = $interventionController->preventiveByChaine($date_debut, $date_fin);

                                        foreach ($machines as $machine) {

                                            $id_machine = $machine['id'];


                                            echo '<tr class="machine-row" >
                                            <td  machine-id="' . $machine['machine_id'] . '" style="color: #0056b3; cursor: pointer;"> ' . $machine['machine_id'] . '</td>
                                            <td>' . $machine['designation'] . '</td>
                                            <td>' . $machine['smartbox'] . '</td>
                                            <td><a href="?route=historique_intervs_mach&type=preventive&machine=' . $machine['machine_id'] . '&id_machine=' . $id_machine . '">' . $machine['nb_interventions'] . '</a></td>
                                            <td>' . date('d/m/Y', strtotime($machine['last_date'])) . '</td>
                                            </tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>



            </div>
            <!-- End of Content Wrapper -->
            <!-- Footer -->
            <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>
            <!-- End of Footer -->
        </div>
        <!-- End of Page Wrapper -->



        <script>
            var allMachinesData = <?php echo json_encode($machinesData); ?>;
        </script>
        <script src="/platform_gmao/public/js/grapheMachine.js"></script>
        <script src="/platform_gmao/public/js/sideBare.js"></script>
        <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
        <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
        <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#dataTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.6/i18n/fr_fr.json'
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

    <!-- Include modals -->
    <?php include(__DIR__ . "/../../views/modals/PlanningModal.php") ?>
    <?php include(__DIR__ . "/../../views/modals/AjoutInterventionPreventive.php") ?>
</body>

</html>