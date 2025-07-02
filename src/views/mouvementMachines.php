<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title> ISA Digitex by AdvantryX</title>
    <!-- Favicon-- logo dans l'ongle-->
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <!-- Custom fonts for this template-->
    <link href="/public/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="/public/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="/public/js/jquery-3.6.4.min.js"></script>
    <script src="/public/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="/public/css/mouvementMachines.css">

</head>


<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->

        <?php include(__DIR__ . "/layout/sidebar.php") ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow"></nav>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                   
                    <?php
                    $chaine = isset($_GET['chaine']) ? $_GET['chaine'] : '';
                    include_once(__DIR__ . "/../controllers/Implantation_Prod_controller.php");
                    $machines = findMachines();
                    $dateActuelle = date('Y-m-d');
                    foreach ($machines as $machine) {
                        if ($machine['prod_line'] == $chaine) {
                            $dateMachine = $machine['cur_date'];
                            $difference = strtotime($dateActuelle) - strtotime($dateMachine);
                            $joursDifference = floor($difference / (60 * 60 * 24));

                            if ($joursDifference >= 3) {
                                $message = 'La machine ' . $machine['machine_id'] . ' de référence "' . $machine['reference'] . ' " est hors service dans la chaîne ' . $machine['prod_line'] . ' pendant ' . $joursDifference . ' jours.'
                                    ?>
                                <div id="alertBox" class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-cogs"></i>
                                    <?php echo $message ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php }
                        }
                    } ?>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Mouvements Machine
                                <?php echo isset($_GET['machine_id']) ? $_GET['machine_id'] : ''; ?>:
                            </h6>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Machine_id <i class="fas fa-sort"></i></th>
                                            <th>Numéro Tête <i class="fas fa-sort"></i></th>
                                            <th>Désignation Machine <i class="fas fa-sort"></i></th>
                                            <th>Emplacement <i class="fas fa-sort"></i></th>
                                            <th>SmartBox <i class="fas fa-sort"></i></th>
                                            <th>Nombre des opération effectuées <i class="fas fa-sort"></i></th>
                                            <th>Date <i class="fas fa-sort"></i></th>
                                            <th>Time <i class="fas fa-sort"></i></th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php

                                        include_once(__DIR__ . "/../models/HistoriqueMachine_model.php");
                                        if (isset($_GET['machine_id'])) {
                                            $machine_id = $_GET['machine_id'];
                                            $machines = MouvementMachine_model::getMachinesWithDetails($machine_id);
                                            foreach ($machines as $machine) {

                                                $nbOpération = $machine['nbOperations'] ? $machine['nbOperations'] : 0;
                                                $classeEmplacement = '';
                                                if (strtolower($machine['emplacement']) == 'e_parcmachine') {
                                                    $classeEmplacement = 'vert'; // Classe pour les emplacements E_ParcMachine
                                                } elseif (strtolower($machine['emplacement']) == 's_parcmachine') {
                                                    $classeEmplacement = 'rouge'; // Classe pour les emplacements S_ParcMachine
                                                }
                                                // Vérifiez si $tempsDispo est différent de zéro avant de faire la division
                                                $efficacite = $machine['tempsDispo'] != 0 ? ($machine['tempsFonctionnement'] / $machine['tempsDispo']) * 100 : 0;

                                                echo '<tr><td > ' . $machine['machine_id'] . '</td> <td> ' . $machine['refMachine'] . '</td><td>' . $machine['designationMachine'] .
                                                    '</td><td class="' . $classeEmplacement . '">' . $machine['emplacement'] . '</td><td>' . $machine['box'] . '</td><td>' . $nbOpération . '</td><td>' . $machine['date'] . '</td><td>' . $machine['time'] . '</td></ul>';
                                            }
                                        }
                                        ?>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include(__DIR__ . "/layout/footer.php"); ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <script>
        $(document).ready(function () {
            var table = $('#dataTable').DataTable({
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
                "order": [
                    [6, 'desc']
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