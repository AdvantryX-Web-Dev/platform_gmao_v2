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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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
                                $statusMsg = 'L\'ajout de l\'intervention curative a été effectué avec succès.';
                                break;
                            case 'error':
                                $statusType = 'alert-danger';
                                $statusMsg = 'Désolé, une erreur est survenue lors de l\'ajout de l\'intervention curative.';
                                break;
                        }
                    }
                    ?>
                    <?php if (!empty($statusMsg)) { ?>
                        <div class="col-xs-12">
                            <div class="alert <?php echo $statusType; ?> alert-dismissible fade show statusM" id="statusMessage">
                                <?php echo $statusMsg; ?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>
                        <script>
                            // Faire disparaître le message automatiquement après 5 secondes
                            setTimeout(function() {
                                $("#statusMessage").fadeOut('slow');
                            }, 5000);
                        </script>
                    <?php } ?>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Liste des interventions curatives sur les Machines
                                </h6>

                                <div class="d-flex">
                                    <div class="mr-2 col-md-6">
                                        <a href="?route=intervention_aleas" class="btn btn-info">
                                            <i class="fas fa-exclamation-triangle"></i> Aléas en production
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a class="btn btn-primary" data-toggle="modal"
                                            data-target="#ajouterDemandeInterventionModal">
                                            <i class="fas fa-wrench"></i> Intervention Machine
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Filtre de date -->
                        <div class="card-body border-bottom date-filter-section">
                            <form id="dateFilterForm" method="GET" class="row justify-content-end align-items-end ">
                                <input type="hidden" name="route" value="intervention_curative">
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
                                            <th> Machine </th>
                                            <th> Désignation </th>
                                            <th>SmartBox </th>
                                            <th>Nombre d'intervention Curative </th>
                                            <th>Dernière date intervention </th>

                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php
                                        // Récupérer les dates de filtre
                                        $date_debut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('-1 month'));
                                        $date_fin = $_GET['date_fin'] ?? date('Y-m-d');

                                        $interventionController = new \App\Controllers\InterventionController();

                                        // Récupérer les interventions curatives avec prodline_id et nomCh

                                        $machines = $interventionController->curativeByChaine($date_debut, $date_fin);

                                        foreach ($machines as $machine) {
                                            // Les données sont déjà agrégées par machine par la méthode curativeByChaine
                                            $nbInterC = $machine['nb_interventions'];
                                            $lastDate = $machine['last_date'];
                                            $id_machine = $machine['id'];

                                            $machineReference = isset($machine['reference']) ? $machine['reference'] : '__';
                                            echo
                                            '<tr class="machine-row" >
                                                <td  machine-id="' . $machine['machine_id'] . '-' . $machineReference . '" style="color: #0056b3; cursor: pointer;"> ' . $machine['machine_id'] . '</td>
                                                <td>' . $machine['designation'] . '</td>
                                                <td>' . $machine['smartbox'] . '</td>
                                                <td><a href="?route=historique_intervs_mach&type=curative&machine=' . $machine['machine_id'] . '&id_machine=' . $id_machine . '">' . $machine['nb_interventions'] . '</a></td>
                                                <td>' . date('d/m/Y', strtotime($lastDate)) . '</td>
                                               
                                            </tr>';
                                            
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
               

                <?php include(__DIR__ . "/../../views/modals/AjoutDemInterCu.php") ?>


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
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                $('#dataTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.6/i18n/fr_fr.json'
                    }
                });

                // Initialiser Select2 pour la sélection de machine dans le modal
                var $machine = $('#ajouterDemandeInterventionModal #machine');
                if ($.fn.select2 && $machine.length) {
                    $machine.select2({
                        placeholder: '-- Sélectionner une machine --',
                        width: '100%',
                        language: 'fr',
                        allowClear: true,
                        minimumInputLength: 1,
                        dropdownParent: $('#ajouterDemandeInterventionModal'),
                        matcher: function (params, data) {
                            if ($.trim(params.term) === '') { return data; }
                            if (!data.element) { return null; }
                            var ref = data.element.getAttribute('data-reference') || '';
                            var term = params.term.toString().toLowerCase();
                            if (ref.toString().toLowerCase().indexOf(term) > -1) { return data; }
                            return null;
                        }
                    });
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
        </script>

    </div>

</body>

</html>