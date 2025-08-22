<?php
// Only start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Import the controller class
use App\Controllers\Equipment_MachineController;
use App\Controllers\Machine_boxController;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title> GMAO Digitex by AdvantryX</title>
    <!-- Favicon-- logo dans l'ongle-->
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />


    <!-- Custom fonts for this template-->
    <link href="/platform_gmao/public/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="/platform_gmao/public/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
    <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/ImplantationMachBox.css">
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include(__DIR__ . "/../layout/sidebar.php") ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">
                <?php include(__DIR__ . "/../../views/layout/navbar.php"); ?>
                <!-- Begin Page Content -->

                <div class="container-fluid">
                    <?php
                    if (isset($_SESSION['equipement_success'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show">' . $_SESSION['equipement_success'] .
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button></div>';
                        unset($_SESSION['equipement_success']);
                    }
                    if (isset($_SESSION['equipement_error'])) {
                        echo '<div class="alert alert-danger alert-dismissible fade show">' . $_SESSION['equipement_error'] .
                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button></div>';
                        unset($_SESSION['equipement_error']);
                    }
                    ?>
                    <button class="btn btn-primary" id="sidebarTo"><i class="fas fa-bars"></i></button>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Affectation machine – équipement:</h6>
                            <div class="d-flex justify-content-end">
                                <a href="?route=equipement_machine/affectation_equipmentMachine" class="btn btn-primary mr-2">Ajouter une affectation</a>
                                <a href="?route=equipement_machine/affectation_scan" class="btn btn-outline-primary"><i class="fas fa-qrcode"></i> Scanner</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Equipement</th>
                                            <th>Machine</th>
                                            <th>Référence </th>
                                            <th>Responsable </th>
                                            <th>Date </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Use the correct path to the controller
                                        $machs_equip = Equipment_MachineController::ListEquipementsMachines();

                                        foreach ($machs_equip as $mach_equip) {

                                            echo '<tr>
                                            <td > ' . $mach_equip['accessory_ref']  . '</td>
                                            <td > ' . $mach_equip['machine_id'] . '</td>
                                            <td > ' . $mach_equip['reference'] . '</td>
                                            <td > ' . $mach_equip['Responsable'] . '</td>
                                            <td > ' . $mach_equip['allocation_date'] . '<br> H: ' . $mach_equip['allocation_time'] . '</td>

                                            </tr>';
                                        }
                                        ?>

                                    </tbody>
                                  

                                </table>
                            </div>
                        </div>
                    </div>



                </div>
                <!-- End of Main Content -->



            </div>
            <!-- Footer -->
            <?php include(__DIR__ . "/../layout/footer.php"); ?>
            <!-- End of Footer -->
            <!-- End of Content Wrapper -->

        </div>
        <!-- End of Page Wrapper -->

        <!-- Scroll to Top Button-->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
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
                        [5, 'desc']

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