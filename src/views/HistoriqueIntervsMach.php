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
    <!-- <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.6/css/jquery.dataTables.css"> -->
    <!-- les icones-->
    <link href="/public/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="/public/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="/public/js/jquery-3.6.4.min.js"></script>
    <script src="/public/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="/public/css/table.css">
    <link rel="stylesheet" href="/public/css/HistoriqueIntervsMach.css">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include_once(__DIR__ . "/../views/layout/sidebar.php") ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- navBar -->
                <?php include(__DIR__ . "/../views/layout/navbar.php") ?>
                <!-- End of navBar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarTo"><i class="fas fa-bars"></i></button>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <?php if (isset($_GET['id_machine']) && isset($_GET['type'])) {
                                $machine_id = $_GET['id_machine']; ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Historique des Interventions
                                        <?php echo ($_GET['type'] == "c") ? "curatives" : "préventives"; ?> sur la Machine
                                        <?php echo $machine_id; ?> :
                                    </h6>
                                    <div class="col-md-3 " style="margin-right: -20px;">

                                    </div>
                                </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th> Période D'intervention <i class="fas fa-sort"></i></th>
                                            <th> Date Début <i class="fas fa-sort"></i></th>
                                            <th>Date Fin <i class="fas fa-sort"></i></th>
                                            <th>Maintenancier <i class="fas fa-sort"></i></th>
                                            <th>Etat Machine <i class="fas fa-sort"></i></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $interventionController = new \App\Controllers\InterventionController();
                                    $type = $_GET['type'];
                                    $interves = $interventionController->getInterventionByMachine($machine_id);
                                  
                                    foreach ($interves as $interv) {
                                        $dateDebut = new DateTime($interv['intervention_date'] . ' ' . $interv['intervention_time']);
                                        $dateFin = new DateTime($interv['endDate']);
                                        $periodeIn = $dateDebut->diff($dateFin)->format('%a jours, %h heures, %i minutes');
                                        $heureMinuteSeconde = substr($interv['intervention_time'], 0, 8);
                                        if (strtolower($type) === 'c' && strtolower($interv['intervention_type']) === 'curative') {
                                            echo '<tr><td>' . $periodeIn . '</td><td>' . $interv['intervention_date'] . '  ' . $heureMinuteSeconde . '</td><td>' . $interv['endDate'] . '</td><td>' . $interv['maintainer_matricule'] . '</td><td>' . ($interv['etatMachine'] === 'reparee' ? '<span class="text-success"><i class="fas fa-check"></i> Réparée</span>' : '<span class="text-danger"><i class="fas fa-times"></i> Non réparée</span>') . '</td></tr>';
                                        } elseif (strtolower($type) === 'p' && strtolower($interv['intervention_type']) === 'preventive') {
                                            echo '<tr><td>' . $periodeIn . '</td><td>' . $interv['intervention_date'] . '  ' . $heureMinuteSeconde . '</td><td>' . $interv['endDate'] . '</td><td>' . $interv['maintainer_matricule'] . '</td><td>' . ($interv['etatMachine'] === 'reparee' ? '<span class="text-success"><i class="fas fa-check"></i> Réparée</span>' : '<span class="text-danger"><i class="fas fa-times"></i> Non réparée</span>') . '</td></tr>';
                                        }
                                    }
                                } ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
            <!-- Footer -->
            <?php include(__DIR__ . "/../views/layout/footer.php"); ?>
            <!-- End of Footer -->
            <!-- End of Content Wrapper -->
        </div>
        <!-- End of Page Wrapper -->

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

    </div>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable({
                language: {
                    search: "Rechercher:",
                    // searchPlaceholder: "Saisissez votre recherche",
                    lengthMenu: "Afficher _MENU_ éléments par page",
                    info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
                    infoEmpty: "Aucun élément à afficher",
                    infoFiltered: "(filtré de _MAX_ éléments au total)",
                    zeroRecords: "Aucun enregistrement correspondant trouvé",
                    paginate: {
                        first: "Premier",
                        previous: "Précédent",
                        next: "Suivant",
                        last: "Dernier",

                    }

                },
                "order": [
                    [2, 'desc']

                ]
            });
        });
    </script>
    <script src="/public/js/sideBare.js"></script>
    <script src="/public/js/bootstrap.bundle.min.js"></script>
    <script src="/public/js/sb-admin-2.min.js"></script>
    <script src="/public/js/dataTables.bootstrap4.min.js"></script>
</body>

</html>