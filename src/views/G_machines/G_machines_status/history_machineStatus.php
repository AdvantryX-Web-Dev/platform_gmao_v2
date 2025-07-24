<?php
// Récupérer l'ID de la machine
$machine_id = isset($_GET['machine_id']) ? $_GET['machine_id'] : '';

use App\Models\MouvementMachine_model;
use App\Models\Machine_model;

// Récupérer les informations de la machine
$machineInfo = null;
if (!empty($machine_id)) {
    $machineInfo = Machine_model::findById($machine_id);
}

// Récupérer l'historique des mouvements
$mouvements = [];
if (!empty($machine_id)) {
    $mouvements = MouvementMachine_model::historiqueMachine($machine_id);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <title>GMAO Digitex | Historique des mouvements</title>
    <!-- Favicon-->
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <!-- les icones-->
    <link href="/public/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="/public/css/sb-admin-2.min.css" rel="stylesheet">
    <script src="/public/js/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="/public/css/table.css">
    <link rel="stylesheet" href="/public/css/datatables.min.css">


    <style>
        /* Badge styles */
        .badge-parc-chaine {
            background-color: #28a745;
            color: white;
        }

        .badge-chaine-parc {
            background-color: #dc3545;
            color: white;
        }

        .badge-inter-chaine {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>

<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include(__DIR__ . "/../../../views/layout/sidebar.php") ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php include(__DIR__ . "/../../../views/layout/navbar.php") ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Notification Messages -->
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
                            <?= $_SESSION['flash_message']['text'] ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>


                    <!-- Machine Information Card -->
                    <?php if ($machineInfo): ?>
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Informations de la machine</h6>
                                <?php if ($machineInfo): ?>
                                    Machine <?= htmlspecialchars($machineInfo['machine_id']) ?> (<?= htmlspecialchars($machineInfo['designation']) ?>)
                                <?php endif; ?>
                                <a href="javascript:history.back()" class="btn btn-secondary float-right">
                                    <i class="fas fa-arrow-left"></i> Retour
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <p><strong>ID:</strong> <?= htmlspecialchars($machineInfo['machine_id']) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Référence:</strong> <?= htmlspecialchars($machineInfo['reference'] ?? 'Non définie') ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Désignation:</strong> <?= htmlspecialchars($machineInfo['designation']) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Statut actuel:</strong>
                                            <?php
                                            if (!empty($mouvements) && isset($mouvements[0]['status_name'])) {
                                                echo htmlspecialchars($mouvements[0]['status_name']);
                                            } else {
                                                echo 'Non défini';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Movements History Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Historique des mouvements</h6>
                        </div>
                        <div class="card-body">
                            <?php if (empty($mouvements)): ?>
                                <div class="alert alert-info">
                                    Aucun mouvement trouvé pour cette machine.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type de mouvement</th>
                                                <th>Raison</th>
                                                <th>Initié par</th>
                                                <th>Accepté par</th>
                                                <th>Statut</th>
                                                <th>Emplacement</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($mouvements as $mouvement): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($mouvement['date_mouvement'])) ?></td>
                                                    <td>
                                                        <?php
                                                        $typeClass = '';
                                                        $typeText = '';

                                                        switch ($mouvement['type_Mouv']) {
                                                            case 'parc_chaine':
                                                                $typeClass = 'badge-parc-chaine';
                                                                $typeText = 'Sorti de parc';
                                                                break;
                                                            case 'chaine_parc':
                                                                $typeClass = 'badge-chaine-parc';
                                                                $typeText = 'Entrée au parc';
                                                                break;
                                                            case 'inter_chaine':
                                                                $typeClass = 'badge-inter-chaine';
                                                                $typeText = 'Inter-Chaîne';
                                                                break;
                                                            default:
                                                                $typeClass = 'badge-secondary';
                                                                $typeText = $mouvement['type_Mouv'];
                                                        }
                                                        ?>
                                                        <span class="badge <?= $typeClass ?>"><?= $typeText ?></span>
                                                    </td>
                                                    <td><?= htmlspecialchars($mouvement['raison_mouv'] ?? 'Non définie') ?></td>
                                                    <td><?= htmlspecialchars($mouvement['emp_initiator_name'] ?? 'Non défini') ?></td>
                                                    <td><?= htmlspecialchars($mouvement['emp_acceptor_name'] ?? 'Non défini') ?></td>
                                                    <td><?= htmlspecialchars($mouvement['status_name'] ?? 'Non défini') ?></td>
                                                    <td><?= htmlspecialchars($mouvement['location_name'] ?? 'Non défini') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- /.container-fluid -->
            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include(__DIR__ . "/../../../views/layout/footer.php"); ?>
            <!-- End of Footer -->
        </div>
        <!-- End of Content Wrapper -->
    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="/public/js/sideBare.js"></script>
    <script src="/public/js/bootstrap.bundle.min.js"></script>
    <script src="/public/js/sb-admin-2.min.js"></script>
    <script src="/public/js/jquery.dataTables.min.js"></script>
    <script src="/public/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
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
                pageLength: 10,
                order: [
                    [0, 'asc']
                ]
            });
        });
    </script>
</body>

</html>