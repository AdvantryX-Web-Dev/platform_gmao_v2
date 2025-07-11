<?php

use App\Controllers\InterventionController;
use App\Models\implantation_Prod_model;


// Initialisation du filtre chaîne AVANT tout HTML
$chaines = implantation_Prod_model::findAllChaines();
$selectedChaine = isset($_GET['chaine']) ? $_GET['chaine'] : '';
if ($selectedChaine === '' && count($chaines) > 0) {
    $selectedChaine = $chaines[0]['prod_line'];
}
$nomCh = $selectedChaine;
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
        <?php include(__DIR__ . "/../views/layout/sidebar.php") ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php include(__DIR__ . "/../views/layout/navbar.php") ?>

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
                                <h6 class="m-0 font-weight-bold text-primary">Historique des Interventions sur les Machines de chaine
                                    <?php echo htmlspecialchars($nomCh); ?> :
                                </h6>
                                <div class="d-flex align-items-center">
                                    <form method="get" class="form-inline mb-0 mr-2" style="display:inline-block;">
                                        <input type="hidden" name="route" value="intervention_preventive">
                                        <select name="chaine" id="chaine" class="form-control mr-2" onchange="this.form.submit()">
                                            <option value="">-- Toutes les chaînes --</option>
                                            <?php
                                            foreach ($chaines as $ch) {
                                                $selected = ($selectedChaine == $ch['prod_line']) ? 'selected' : '';
                                                echo '<option value="' . htmlspecialchars($ch['prod_line']) . '" ' . $selected . '>' . htmlspecialchars($ch['prod_line']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </form>
                                    <button class="btn btn-success mr-2" data-toggle="modal" data-target="#planningModal">
                                        <i class="fas fa-calendar-plus"></i> Ajouter au planning
                                    </button>
                                    <?php 
                                    // Get count of future interventions
                                    $planningController = new \App\Controllers\InterventionPlanningController();
                                    $futureCount = $planningController->getFutureInterventionsCount();
                                    ?>
                                    <a href="../../public/index.php?route=intervention_planning/list" class="btn btn-info">
                                        <i class="fas fa-calendar-alt"></i> Liste des planifications
                                        <?php if ($futureCount > 0): ?>
                                        <span class="badge badge-light ml-1"><?= $futureCount ?></span>
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
                                        $machines = $interventionController->getByChaine($nomCh);

                                        foreach ($machines as $machine) {
                                            $nbInterP = 0;
                                            $id_machine = $machine['machine_id'];
                                            $interves = $interventionController->getInterventionByMachine($id_machine);
                                            // Filtrer les interventions préventives
                                            $preventives = array_filter($interves, function ($interv) {
                                                return strtolower($interv['intervention_type']) !== 'curative';
                                            });
                                            if (empty($preventives)) {
                                                continue; // Sauter les machines sans intervention préventive
                                            }
                                            $lastDate = '';
                                            foreach ($interves as $interv) {
                                                if (strtolower($interv['intervention_type']) !== 'curative') {
                                                    $lastDate = $interves[0]['intervention_date'];
                                                    $nbInterP++;
                                                }
                                            }

                                            echo '<tr class="machine-row" >
                                            <td  machine-id="' . $machine['machine_id'] . '" style="color: #0056b3; cursor: pointer;"> ' . $machine['machine_id'] . '</td>
                                            <td>' . $machine['designation'] . '</td>
                                                <td>' . $machine['smartbox'] . '</td>
                                                <td><a href="?route=historique_intervs_mach&type=p&id_machine=' . $machine['machine_id'] . '">' . $nbInterP . '</td>
                                                <td>' . htmlspecialchars($lastDate) .
                                                '</td>
                                                </tr>';
                                            $nbInterAndpannes = $interventionController->getNbInterPannMach($id_machine);

                                            $machineData = array();
                                            foreach ($nbInterAndpannes as $resultat) {

                                                $machineData[] = array(
                                                    'codePanne' => $resultat['codePanne'],
                                                    'nbInter' => $resultat['nbInter'],
                                                );
                                            }
                                            $machinesData[$id_machine] = $machineData;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="container-fluid" id="statsCard"
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
                </div>



            </div>
            <!-- End of Content Wrapper -->
            <!-- Footer -->
            <?php include(__DIR__ . "/../views/layout/footer.php"); ?>
            <!-- End of Footer -->
        </div>
        <!-- End of Page Wrapper -->

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
        <script>
            var allMachinesData = <?php echo json_encode($machinesData); ?>;
        </script>
        <script src="/public/js/grapheMachine.js"></script>
        <script src="/public/js/sideBare.js"></script>
        <script src="/public/js/bootstrap.bundle.min.js"></script>
        <script src="/public/js/sb-admin-2.min.js"></script>
        <script src="/public/js/dataTables.bootstrap4.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#dataTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.6/i18n/fr_fr.json'
                    }
                });
            });
        </script>
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    </div>

    <!-- Planning Modal -->
    <div class="modal fade" id="planningModal" tabindex="-1" role="dialog" aria-labelledby="planningModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="planningModalLabel">
                        <i class="fas fa-calendar-plus"></i> Ajouter une intervention planifiée
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="planningForm" action="../../public/index.php?route=intervention_planning/save" method="POST">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="machine_id"> Machine :</label>
                            <select class="form-control" id="machine_id" name="machine_id" required>
                                <option value="">-- Sélectionner une machine --</option>
                                <?php
                                if (isset($machines) && is_array($machines)) {
                                    foreach ($machines as $machine) {
                                        echo '<option value="' . htmlspecialchars($machine['id']) . '">' .
                                            htmlspecialchars($machine['machine_id']) .  '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="intervention_type_id"> Type d'intervention :</label>
                            <select class="form-control" id="intervention_type_id" name="intervention_type_id" required>
                                <option value="">-- Sélectionner un type --</option>
                                <?php
                                // Load intervention types (only preventive)
                                $interventionTypes = \App\Models\Intervention_type_model::findByType('preventive');
                                if (isset($interventionTypes) && is_array($interventionTypes)) {
                                    foreach ($interventionTypes as $type) {
                                        echo '<option value="' . htmlspecialchars($type['id']) . '">' .
                                            htmlspecialchars($type['designation']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="planned_date"> Date planifiée :</label>
                            <input type="date" class="form-control" id="planned_date" name="planned_date" required>
                        </div>
                        <div class="form-group">
                            <label for="comments"><i class="fas fa-comment"></i> Commentaires :</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">
                            Valider
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                             Annuler
                        </button>

                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>