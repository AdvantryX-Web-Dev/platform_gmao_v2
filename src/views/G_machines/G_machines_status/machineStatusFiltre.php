<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Etat des machines</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Style personnalisé pour les filtres */
        .select2-container--default .select2-selection--single {
            height: 31px !important;
            border: 1px solid #d1d3e2 !important;
            border-radius: 0.35rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 29px !important;
            padding-left: 8px !important;
            font-size: 0.875rem !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 29px !important;
        }

        .select2-container {
            width: 100% !important;
        }

        .select2-dropdown {
            border: 1px solid #d1d3e2 !important;
            border-radius: 0.35rem !important;
        }

        /* Espacement uniforme pour les filtres */
        .filter-row .col-md-2 {
            padding-left: 8px;
            padding-right: 8px;
        }

        .filter-row .col-md-2:first-child {
            padding-left: 15px;
        }

        .filter-row .col-md-2:last-child {
            padding-right: 15px;
        }
    </style>

</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../../views/layout/navbar.php"); ?>
                <div class="container-fluid">

                    <?php if (!empty($_SESSION['flash_message'])): ?>
                        <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                            <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>

                    <?php if ($cardFilterLabel): ?>
                        <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <strong>Filtre appliqué :</strong>
                                <?= htmlspecialchars($cardFilterLabel); ?>
                            </div>
                            <a href="?route=Gestion_machines/status" class="btn btn-outline-info btn-sm">
                                Retour à la vue complète
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="card shadow mb-4">

                        <div class="card-body">
                            <div class="mb-4">
                                <!-- card de statistiques -->
                                <div class="row justify-content-center mb-4">
                                    <?php
                                    // Calculer les statistiques
                                    $countProduction = 0;
                                    $countParc = 0;
                                    $countPanne = 0;
                                    $countFerraille = 0;
                                    $countEmpty = 0;
                                    $countInactive = 0;
                                    $countTotal = is_array($machinesData) ? count($machinesData) : 0;

                                    if (is_array($machinesData)) {
                                        foreach ($machinesData as $m) {
                                            if (!empty($m['location_category']) && $m['location_category'] == 'prodline') {
                                                $countProduction++;
                                            }
                                            if (!empty($m['location_category']) && $m['location_category'] == 'parc') {
                                                $countParc++;
                                            }
                                            if (!empty($m['location_category']) && $m['location_category'] == 'ferraille') {
                                                $countFerraille++;
                                            }
                                            if (!empty($m['etat_machine'])) {
                                                $etatMachine = strtolower($m['etat_machine']);
                                                if ($etatMachine === 'en panne') {
                                                    $countPanne++;
                                                } elseif ($etatMachine === 'ferraille') {
                                                    $countFerraille++;
                                                }
                                            }
                                             $status = $m['status_name_final'] ?? $m['etat_machine'];
                                             if ($status === 'inactive') {
                                                $countInactive++;
                                            }
                                            if (empty($m['location'])) {
                                                $countEmpty++;
                                            }
                                        }
                                    }

                                    // Code du filtre supprimé
                                    ?>
                                    <!-- Production Card -->
                                    <div class="col-xl-4 col-md-4 mb-4">
                                        <div class="card border-left-success shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">En production</div>
                                                        <div class="status-count text-gray-800" id="count-production"><?php echo $countProduction; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-cogs fa-2x status-card-icon text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Parc Card -->
                                    <div class="col-xl-4 col-md-4 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Dans le parc</div>
                                                        <div class="status-count text-gray-800" id="count-parc"><?php echo $countParc; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-warehouse fa-2x status-card-icon text-primary"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Total Card -->
                                    <div class="col-xl-3 col-md-4 mb-4">
                                        <div class="card border-left-info shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total machines</div>
                                                        <div class="status-count text-gray-800" id="count-total"><?php echo $countTotal; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-clipboard-list fa-2x status-card-icon text-info"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th></th>

                                            <th>Machine</th>
                                            <th>Maintenancier</th>
                                            <!-- <th>Référence</th> -->
                                            <th>Désignation</th>
                                            <!-- <th>Type</th> -->
                                            <th>Emplacement</th>
                                            <th>Etat</th>
                                            <th>Date de la dernière présence</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($machinesData) && count($machinesData) > 0):
                                        ?>
                                            <?php foreach ($machinesData as $machine):
                                                $status = $machine['status_name_final'] ?? $machine['etat_machine'];
                                                $lastPresenceDate = $machine['cur_date_time'] ?? null;
                                                $highlightInactiveRow = false;

                                                if ($status === 'inactive' && !empty($lastPresenceDate)) {
                                                    try {
                                                        $presenceDate = new DateTime($lastPresenceDate);
                                                        $thresholdDate = new DateTime('-3 days');
                                                        $highlightInactiveRow = $presenceDate <= $thresholdDate;
                                                    } catch (Exception $e) {
                                                        // Ignore parsing issues, simply skip highlighting
                                                        $highlightInactiveRow = false;
                                                    }
                                                }
                                            ?>
                                                <tr class="<?= $highlightInactiveRow ? 'table-danger' : '' ?>">
                                                    <td>
                                                        <a href="?route=mouvement_machines/history&machine_id=<?= htmlspecialchars($machine['machine_id']) ?>" class="btn btn-info btn-sm" title="Voir l'historique des mouvements">
                                                            <i class="fas fa-history"></i>
                                                        </a>
                                                        <a href="?route=machines/presence/history&machine_id=<?= htmlspecialchars($machine['machine_id']) ?>" class="btn btn-primary btn-sm mt-1" title="Voir l'historique de présence">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                    <td><?= isset($machine['machine_id']) ? htmlspecialchars($machine['machine_id']) : 'Non défini' ?></td>
                                                    <td><?= isset($machine['maintener_id']) ? htmlspecialchars($machine['maintener_matricule'])
                                                            . ' <br> ' . '<span class="small text-secondary"> ' . htmlspecialchars($machine['maintener_name']) : '<span class="badge badge-secondary">Non défini</span>' ?></td>



                                                    <!-- <td><?= isset($machine['reference']) ? htmlspecialchars($machine['reference']) : 'Non défini' ?></td> -->
                                                    <td><?= isset($machine['designation']) ? htmlspecialchars($machine['designation']) : 'Non défini' ?></td>

                                                    <!-- <td><?= isset($machine['type']) ? htmlspecialchars($machine['type']) : 'Non défini' ?></td> -->
                                                    <td>
                                                        <?php
                                                        if (empty($machine['location_category'])) {
                                                            echo '<span class="badge badge-secondary">Non défini</span>';
                                                        } elseif ($machine['location_category'] == 'parc') {
                                                            echo '<span class="badge badge-primary"> ' . htmlspecialchars($machine['location']) . '</span>';
                                                        } elseif ($machine['location_category'] == 'prodline') {
                                                            echo '<span class="badge badge-success"> ' . htmlspecialchars($machine['location']) . '</span>';
                                                        } else {
                                                            echo htmlspecialchars($machine['location_category']);
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php
                                                        if ($status == 'active') {
                                                            echo '<span class="badge badge-success ">' . htmlspecialchars($status) . '</span>';
                                                        } elseif ($status == 'en panne' || $status == 'ferraille') {
                                                            echo '<span class="badge badge-danger">' . htmlspecialchars($status) . '</span>';
                                                        } elseif ($status == 'inactive') {
                                                            echo '<span class="badge badge-warning">'
                                                                . htmlspecialchars($status)

                                                                . '</span>';
                                                        } else {
                                                            echo '<span class="badge badge-secondary ">' . htmlspecialchars($status ?? 'Non défini') . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if ($status == 'inactive') {
                                                            echo isset($machine['cur_date_time']) ? htmlspecialchars($machine['cur_date_time'])  : 'Non défini';
                                                        } else {
                                                            echo '<span ">' . ' ' . '</span>';
                                                        }
                                                        ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6">Aucune machine trouvée.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../../../views/layout/footer.php"); ?>

        </div>

        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

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
                // Init Select2 for better search/UX
                $('#matricule, #machine_id, #location, #status, #type').select2({
                    width: '100%',
                    placeholder: 'Sélectionner...',
                    allowClear: true,
                    language: {
                        noResults: function() {
                            return "Aucun résultat trouvé";
                        },
                        searching: function() {
                            return "Recherche en cours...";
                        }
                    }
                });

                // Filtrage automatique après chaque sélection
                $('#matricule, #machine_id, #location, #status, #type').on('change', function() {
                    $('#filterForm').submit();
                });
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
                    ],
                    // Désactiver le filtrage côté client pour forcer l'utilisation des filtres backend
                    searching: false,
                    info: true,
                    paging: true,
                    ordering: true
                });

                // Faire disparaître les messages flash après 3 secondes
                setTimeout(function() {
                    $("#flash-message").fadeOut("slow");
                }, 4000);

            });
        </script>
    </div>
</body>

</html>