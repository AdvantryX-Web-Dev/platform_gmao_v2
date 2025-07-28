<?php

use App\Controllers\InterventionController;
use App\Models\Implantation_Prod_model;

use App\Models\Database;

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
                                        $machines = $interventionController->preventiveByChaine();

                                        foreach ($machines as $machine) {

                                            $id_machine = $machine['id'];


                                            echo '<tr class="machine-row" >
                                            <td  machine-id="' . $machine['machine_id'] . '" style="color: #0056b3; cursor: pointer;"> ' . $machine['machine_id'] . '</td>
                                            <td>' . $machine['designation'] . '</td>
                                            <td>' . $machine['smartbox'] . '</td>
                                            <td><a href="?route=historique_intervs_mach&type=preventive&machine=' . $machine['machine_id'] . '&id_machine=' . $id_machine . '">' . $machine['nb_interventions'] . '</a></td>
                                            <td>' . date('d/m/Y', strtotime($machine['last_date'])) . '</td>
                                            </tr>';
                                           // $nbInterAndpannes = $interventionController->getNbInterPannMach($id_machine);

                                            // $machineData = array();
                                            // foreach ($nbInterAndpannes as $resultat) {

                                            //     $machineData[] = array(
                                            //         'codePanne' => $resultat['codePanne'],
                                            //         'nbInter' => $resultat['nbInter'],
                                            //     );
                                            // }
                                            // $machinesData[$id_machine] = $machineData;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- <div class="container-fluid" id="statsCard"
                    style="display: none; margin: 20px auto;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="m-0">
                            <i class="fas fa-chart-bar"></i> Statistiques de la Machine
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-center">
                            <canvas id="myChart" width="800" height="400"></canvas>
                        </div>
                    </div>
                </div> -->



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
            });
        </script>
    </div>

    <!-- Include modals -->
    <?php include(__DIR__ . "/../../views/modals/PlanningModal.php") ?>
    <?php include(__DIR__ . "/../../views/modals/AjoutInterventionPreventive.php") ?>
</body>

</html>

</html>