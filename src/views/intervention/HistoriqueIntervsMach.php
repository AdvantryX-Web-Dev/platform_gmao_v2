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
    <link href="/platform_gmao/public/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="/platform_gmao/public/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
    <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/HistoriqueIntervsMach.css">
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include_once(__DIR__ . "/../../views/layout/sidebar.php") ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <!-- navBar -->
                <?php include(__DIR__ . "/../../views/layout/navbar.php") ?>
                <!-- End of navBar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">
                    
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <?php if (isset($_GET['id_machine']) && isset($_GET['type'])) {
                                $id_machine = $_GET['id_machine']; ?>
                                <?php $machine = $_GET['machine']; ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">Historique des Interventions
                                        <?php echo ($_GET['type'] == "curative") ? "curatives" : "préventives"; ?> sur la Machine
                                        <?php echo $machine; ?> :
                                    </h6>
                                    <div class="col-md-3  text-right">
                                        <a href="javascript:history.back()" class="btn btn-primary">
                                            <i class="fas fa-arrow-left"></i> Retour
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
                                            <th> Chaîne de production <i class="fas fa-sort"></i></th>
                                            <th> Type d'intervention <i class="fas fa-sort"></i></th>
                                            <th> Maintenancier <i class="fas fa-sort"></i></th>
                                            <th> Date de création <i class="fas fa-sort"></i></th>
                                            <th> Planifié <i class="fas fa-sort"></i></th>
                                            <th> Date d'intervention <i class="fas fa-sort"></i></th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $intervesByMachine = \App\Models\Intervention_model::findByMachine($id_machine,$_GET['type']);
                                    foreach ($intervesByMachine as $interve) {

                                        echo '<tr>
                                            <td>' . $interve['machine'] . '</td>
                                            <td>' . $interve['prodline'] . '</td>
                                            <td>' . $interve['intervention_type_designation'] . '</td>
                                            <td>' . $interve['maintainer_last_name'] . ' ' . $interve['maintainer_first_name'] . '</td>
                                            <td>' . $interve['created_at'] . '</td>
                                            <td>' . $interve['planning_date'] . '</td>
                                            <td>' . $interve['intervention_date'] . '</td>

                                        </tr>';
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
            <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>
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
                    [6, 'desc'] // Tri par date de création (colonne 7) en ordre décroissant
                ]
            });
        });
    </script>
    <script src="/platform_gmao/public/js/sideBare.js"></script>
    <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
    <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
    <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
</body>

</html>