<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des interventions planifiées | GMAO Digitex</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .today-row {
            background-color: #fff7e0 !important;
            /* Light yellow background */
            font-weight: bold;
            border-left: 4px solid #ffc107;
            /* Warning color border */
        }

        .today-row td {
            position: relative;
        }

        .today-row:hover {
            background-color: #fff0c5 !important;
            /* Slightly darker yellow on hover */
        }

        /* Additional styles for today's intervention cells */
        .today-row td:first-child::before {
            content: "⚠️ ";
            font-size: 14px;
        }

        .today-row td:nth-child(3) {
            color: #ff6b01;
            font-weight: bold;
        }

        /* Make the date column more prominent */
        table.dataTable tbody tr td:nth-child(3) {
            font-weight: 500;
        }
    </style>
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../views/layout/navbar.php"); ?>
                <div class="container-fluid">

                    <?php if (!empty($_SESSION['flash_message'])): ?>
                        <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                            <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>

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



                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Interventions planifiées
                                </h6>
                                <div class="d-flex align-items-center">
                                    <button class="btn btn-success mr-2" data-toggle="modal" data-target="#planningModal">
                                        <i class="fas fa-calendar-plus"></i> Ajouter au planning
                                    </button>
                                    <a href="../../platform_gmao/public/index.php?route=intervention_preventive" class="btn btn-primary">
                                        <i class="fas fa-arrow-left"></i> Retour
                                    </a>
                                    <!-- <button class="btn btn-success m-2" data-toggle="modal" data-target="#planningModal">
                                        <i class="fas fa-plus"></i> Ajouter une planification
                                    </button> -->
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Machine</th>
                                                <th>Type d'intervention</th>
                                                <th>Etat</th>
                                                <th>Date de création</th>
                                                <th>Date planifiée</th>
                                                <th>Date d'intervention</th>

                                                <th>Commentaires</th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <?php if (is_array($plannings) && count($plannings) > 0): ?>
                                                <?php foreach ($plannings as $planning): ?>
                                                    <?php
                                                    $planned_date = new DateTime($planning['planned_date']);
                                                    $today = new DateTime();
                                                    $today->setTime(0, 0, 0);

                                                    // Determine status and status class
                                                    $intervention_date_raw = $planning['intervention_date'] ?? null;
                                                    if (!empty($intervention_date_raw)) {
                                                        $status = 'Validé';
                                                        $statusClass = 'success';
                                                    } elseif ($planned_date < $today) {
                                                        $status = 'En retard';
                                                        $statusClass = 'danger';
                                                    } elseif ($planned_date->format('Y-m-d') === $today->format('Y-m-d')) {
                                                        $status = "Aujourd'hui";
                                                        $statusClass = 'warning';
                                                    } else {
                                                        $status = 'À venir';
                                                        $statusClass = 'info';
                                                    }

                                                    ?>
                                                    <tr class="<?= (empty($intervention_date_raw) && $planned_date->format('Y-m-d') === $today->format('Y-m-d')) ? 'today-row' : '' ?>">
                                                    <td><?= htmlspecialchars($planning['machine_id']  . ':' .  ($planning['reference'] ?? 'sans réf')) ?></td>
                                                    <td><?= htmlspecialchars($planning['intervention_type'] ?? 'Non défini') ?></td>
                                                        <td>
                                                            <span class="badge badge-<?= $statusClass ?>"><?= $status ?></span>
                                                        </td>

                                                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($planning['created_at']))) ?></td>
                                                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($planning['planned_date']))) ?></td>
                                                        <td>
                                                            <?php
                                                            $date = $planning['intervention_date'] ?? null;
                                                            if (!empty($date)) {
                                                                echo htmlspecialchars(date('d/m/Y', strtotime($date)));
                                                            } else {
                                                                echo '-';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($planning['comments'] ?? '-') ?></td>

                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="9" class="text-center">Aucune intervention planifiée trouvée</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>

            <?php include(__DIR__ . "/../../views/modals/PlanningModal.php") ?>
            <!-- Scripts JavaScript -->
            <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
            <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
            <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
            <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
            <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
            <script src="/platform_gmao/public/js/sideBare.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

            <script>
                $(document).ready(function() {
                    $('#dataTable').DataTable({
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
                            [3, 'asc'] // Sort by planned date ascending
                        ]
                    });

                    // Faire disparaître les messages flash après 3 secondes
                    setTimeout(function() {
                        $(".alert-dismissible").fadeOut("slow");
                    }, 4000);
                });
            </script>
             <script>
            $(document).ready(function () {
                function initMachineSelect($parentModal, selector) {
                    var $sel = $parentModal.find(selector);
                    if ($.fn.select2 && $sel.length) {
                        $sel.select2({
                            placeholder: '-- Sélectionner une machine --',
                            width: '100%',
                            language: 'fr',
                            allowClear: true,
                            minimumInputLength: 0,
                            dropdownParent: $parentModal,
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
                }

                var $planningModal = $('#planningModal');
                var $preventiveModal = $('#ajoutInterventionPreventiveModal');
                initMachineSelect($planningModal, '#machine_id');
                initMachineSelect($preventiveModal, '#machine_id');

                // Re-initialize on modal show in case of dynamic content
                $planningModal.on('shown.bs.modal', function () { initMachineSelect($planningModal, '#machine_id'); });
                $preventiveModal.on('shown.bs.modal', function () { initMachineSelect($preventiveModal, '#machine_id'); });
            });
        </script>
        </div>
</body>

</html>