<?php

use App\Models\implantation_Prod_model;


// Initialisation du filtre chaîne AVANT tout HTML
$chaines = implantation_Prod_model::findAllChaines();

$selectedprodline_id = isset($_GET['id']) ? $_GET['id'] : '';
$selectedChaine = isset($_GET['chaine']) ? $_GET['chaine'] : '';

if ($selectedChaine === '') {
    $selectedChaine = $chaines[0]['id'];
}

$prodline_id = $selectedChaine;
$findChaineById = implantation_Prod_model::findChaineById($prodline_id);
$nomCh = $findChaineById ? $findChaineById[0]['prod_line'] : $selectedChaine;

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
                            <div class="alert <?php echo $statusType; ?>  statusM" id="statusMessage">
                                <?php echo $statusMsg; ?>
                            </div>
                        </div> <?php } ?>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Liste des interventions curatives sur les Machines de chaine
                                    <?php echo htmlspecialchars($nomCh); ?> :
                                </h6>
                                <form method="get" class="form-inline mb-0" style="display:inline-block;">
                                    <input type="hidden" name="route" value="intervention_curative">
                                    <select name="chaine" id="chaine" class="form-control mr-2" onchange="this.form.submit()">
                                        <option value="">-- Toutes les chaînes --</option>
                                        <?php
                                        foreach ($chaines as $ch) {
                                            $selected = ($selectedChaine == $ch['id']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($ch['id']) . '" ' . $selected . '>' . htmlspecialchars($ch['prod_line']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </form>
                                <div class="col-md-3 " style="margin-right: -20px;">
                                    <a class=" btn btn-outline-danger" data-toggle="modal"
                                        data-target="#ajouterDemandeInterventionModal">
                                        <i class="fas fa-wrench"></i> Intervention Machine
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
                                            <th>Nombre D'intervention Curative <i class="fas fa-sort"></i></th>
                                            <th>Dernière date intervention <i class="fas fa-sort"></i></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php

                                        $interventionController = new \App\Controllers\InterventionController();

                                        // Récupérer les interventions curatives avec prodline_id et nomCh

                                        $machines = $interventionController->curativeByChaine($prodline_id, $nomCh);

                                        foreach ($machines as $machine) {
                                            // Les données sont déjà agrégées par machine par la méthode curativeByChaine
                                            $nbInterC = $machine['nb_interventions'];
                                            $lastDate = $machine['last_date'];

                                            echo
                                            '<tr class="machine-row" >
                                                <td  machine-id="' . $machine['machine_id'] . '" style="color: #0056b3; cursor: pointer;"> ' . $machine['machine_id'] . '</td>
                                                <td>' . $machine['designation'] . '</td>
                                                <td>' . $machine['smartbox'] . '</td>
                                                <td><a href="?route=historique_intervs_mach&type=c&id_machine=' . $machine['machine_id'] . '">' . $nbInterC . '</a></td>
                                                <td>' . date('d/m/Y', strtotime($lastDate)) . '</td>
                                            </tr>';
                                            $nbInterAndpannes = $interventionController->getNbInterPannMach($machine['machine_id']);

                                            $machineData = array();
                                            foreach ($nbInterAndpannes as $resultat) {

                                                $machineData[] = array(
                                                    'codePanne' => $resultat['codePanne'],
                                                    'nbInter' => $resultat['nbInter'],
                                                );
                                            }
                                            $machinesData[$machine['machine_id']] = $machineData;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="container-fluid " id="statsCard"
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

                <?php include(__DIR__ . "/../views/modals/AjoutDemInterCu.php") ?>


            </div>
            <!-- End of Content Wrapper -->
            <!-- Footer -->
            <?php include(__DIR__ . "/../views/layout/footer.php"); ?>
            <!-- End of Footer -->
        </div>
        <!-- End of Page Wrapper -->


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

    </div>

</body>

</html>