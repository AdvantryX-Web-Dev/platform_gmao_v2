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

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Etat des machines</h6>
                            <div>
                                <button type="button" class="btn btn-success btn-sm" onclick="exportToExcel()">
                                    <i class="fas fa-file-excel"></i> Exporter Excel
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <!-- Replace the current card row with this structure that places all cards in one row -->
                                <div class="row justify-content-center mb-4">
                                    <?php
                                    // Calculer les statistiques
                                    $countProduction = 0;
                                    $countParc = 0;
                                    $countPanne = 0;
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
                                            if (!empty($m['etat_machine']) && ($m['etat_machine'] == 'en panne' || $m['etat_machine'] == 'ferraille')) {
                                                $countPanne++;
                                            }
                                            if (!empty($m['etat_machine']) && $m['etat_machine'] == 'inactive') {
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
                                    <div class="col-xl-2 col-md-4 mb-4">
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
                                    <div class="col-xl-2 col-md-4 mb-4">
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
                                    <!-- No Status Card -->
                                    <div class="col-xl-2 col-md-4 mb-4">
                                        <div class="card border-left-warning shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Machines non définis</div>
                                                        <div class="status-count text-gray-800" id="count-empty-status"><?php echo $countEmpty; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-question-circle fa-2x status-card-icon text-warning"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Panne Card -->
                                    <div class="col-xl-2 col-md-4 mb-4">
                                        <div class="card border-left-danger shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">En panne - ferraille</div>
                                                        <div class="status-count text-gray-800" id="count-panne"><?php echo $countPanne; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-exclamation-triangle fa-2x status-card-icon text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Inactive Card -->
                                    <div class="col-xl-2 col-md-4 mb-4">
                                        <div class="card border-left-warning shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Inactives</div>
                                                        <div class="status-count text-gray-800" id="count-inactive"><?php echo $countInactive; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-pause-circle fa-2x status-card-icon text-warning"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Card -->
                                    <div class="col-xl-2 col-md-4 mb-4">
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

                            <!-- Section des filtres -->
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="" id="filterForm">
                                        <input type="hidden" name="route" value="Gestion_machines/status">
                                        <div class="row align-items-end filter-row">
                                            <!-- Filtre par mainteneur -->
                                            <div class="col-md-2 mb-3">
                                                <label for="matricule" class="form-label small text-muted mb-1">Mainteneur</label>
                                                <select name="matricule" id="matricule" class="form-control form-control-sm" <?= !$isAdmin ? 'disabled' : '' ?>>
                                                    <option value="">Tous les mainteneurs</option>
                                                    <?php foreach ($maintainers as $maintainer): ?>
                                                        <option value="<?= htmlspecialchars($maintainer['matricule']) ?>"
                                                            <?= (isset($_GET['matricule']) && $_GET['matricule'] == $maintainer['matricule']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($maintainer['matricule'] . ' - ' . $maintainer['full_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                            </div>

                                            <!-- Filtre par machine ID -->
                                            <div class="col-md-2 mb-3">
                                                <label for="machine_id" class="form-label small text-muted mb-1">Machine ID</label>
                                                <select name="machine_id" id="machine_id" class="form-control form-control-sm">
                                                    <option value="">Toutes les machines</option>
                                                    <?php foreach ($machinesList as $machine): ?>
                                                        <option value="<?= htmlspecialchars($machine['machine_id']) ?>"
                                                            <?= (isset($_GET['machine_id']) && $_GET['machine_id'] == $machine['machine_id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($machine['machine_id']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Filtre par emplacement -->
                                            <div class="col-md-2 mb-3">
                                                <label for="location" class="form-label small text-muted mb-1">Emplacement</label>
                                                <select name="location" id="location" class="form-control form-control-sm">
                                                    <option value="">Tous les emplacements</option>
                                                    <?php foreach ($locations as $location): ?>
                                                        <option value="<?= htmlspecialchars($location['location_name']) ?>"
                                                            <?= (isset($_GET['location']) && $_GET['location'] == $location['location_name']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($location['location_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Filtre par état -->
                                            <div class="col-md-2 mb-3">
                                                <label for="status" class="form-label small text-muted mb-1">État</label>
                                                <select name="status" id="status" class="form-control form-control-sm">
                                                    <option value="">Tous les états</option>
                                                    <?php foreach ($statuses as $status): ?>
                                                        <option value="<?= htmlspecialchars($status['id']) ?>"
                                                            <?= (isset($_GET['status']) && $_GET['status'] == $status['id']) ? 'selected' : '' ?>>
                                                            <?php
                                                            if ($status['status_category'] !== 'equipment') {
                                                                echo htmlspecialchars($status['status_name']);
                                                            }
                                                            ?>

                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!-- Boutons pour filtrer et réinitialiser -->
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label small text-muted mb-1">&nbsp;</label>
                                                <div>
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-filter"></i> Filtrer
                                                    </button>
                                                    <a href="?route=Gestion_machines/status" class="btn btn-secondary btn-sm ml-1">
                                                        <i class="fas fa-undo"></i> Réinitialiser
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
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
                                            <th>Date de la dernière action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($machinesData) && count($machinesData) > 0): ?>
                                            <?php foreach ($machinesData as $machine):

                                            ?>
                                                <tr>
                                                    <td>
                                                        <a href="?route=mouvement_machines/history&machine_id=<?= htmlspecialchars($machine['machine_id']) ?>" class="btn btn-info btn-sm" title="Voir l'historique des mouvements">
                                                            <i class="fas fa-history"></i>
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
                                                        $status = $machine['status_name_final'] ?? $machine['etat_machine'];


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
                                                            echo isset($machine['cur_date_time']) ? htmlspecialchars($machine['cur_date_time']) : (isset($machine['updated_at']) ? htmlspecialchars($machine['updated_at']) : 'Non défini');
                                                        } else {
                                                            echo isset($machine['updated_at']) ? htmlspecialchars($machine['updated_at']) : 'Non défini';
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
                $('#matricule, #machine_id, #location, #status').select2({
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

            // Fonction d'export Excel
            window.exportToExcel = function() {
                // Récupérer les paramètres de filtrage actuels
                const urlParams = new URLSearchParams(window.location.search);
                const matricule = urlParams.get('matricule') || '';
                const machine_id = urlParams.get('machine_id') || '';
                const location = urlParams.get('location') || '';
                const status = urlParams.get('status') || '';

                // Construire l'URL d'export avec les mêmes filtres
                let exportUrl = '?route=Gestion_machines/export&';
                if (matricule) exportUrl += 'matricule=' + encodeURIComponent(matricule) + '&';
                if (machine_id) exportUrl += 'machine_id=' + encodeURIComponent(machine_id) + '&';
                if (location) exportUrl += 'location=' + encodeURIComponent(location) + '&';
                if (status) exportUrl += 'status=' + encodeURIComponent(status) + '&';

                // Supprimer le dernier & si présent
                exportUrl = exportUrl.replace(/&$/, '');

                // Rediriger vers l'URL d'export
                window.location.href = exportUrl;
            };
        </script>
    </div>
</body>

</html>