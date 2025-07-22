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
    <link rel="stylesheet" href="/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/public/css/table.css">
    <link rel="stylesheet" href="/public/css/datatables.min.css">
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
                            <!-- Suppression du filtre par état -->
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
                                    $countTotal = is_array($machines) ? count($machines) : 0;
                                    
                                    if (is_array($machines)) {
                                        foreach ($machines as $m) {
                                            if (!empty($m['location']) && $m['location'] == 'prodline') {
                                                $countProduction++;
                                            }
                                            if (!empty($m['location']) && $m['location'] == 'parc') {
                                                $countParc++;
                                            }
                                            if (!empty($m['etat_machine']) && ($m['etat_machine'] == 'en panne' || $m['etat_machine'] == 'ferraille')) {
                                                $countPanne++;
                                            }
                                            if (!empty($m['etat_machine']) && $m['etat_machine'] == 'inactive') {
                                                $countInactive++;
                                            }
                                            if (empty($m['etat_machine'])) {
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

                                    <!-- No Status Card -->
                                    <div class="col-xl-2 col-md-4 mb-4">
                                        <div class="card border-left-warning shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Machines non définies</div>
                                                        <div class="status-count text-gray-800" id="count-empty-status"><?php echo $countEmpty; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-question-circle fa-2x status-card-icon text-warning"></i>
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
                                            <th>Référence</th>
                                            <th>Désignation</th>
                                            <th>Type</th>
                                            <th>Emplacement</th>
                                            <th>Etat</th>
                                            <th>Date de la dernière action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($machines) && count($machines) > 0): ?>
                                            <?php foreach ($machines as $machine): ?>
                                                <tr>
                                                    <td>
                                                        <a href="?route=mouvement_machines/history&machine_id=<?= htmlspecialchars($machine['machine_id']) ?>" class="btn btn-info btn-sm" title="Voir l'historique des mouvements">
                                                            <i class="fas fa-history"></i>
                                                        </a>

                                                    </td>
                                                    <td><?= isset($machine['machine_id']) ? htmlspecialchars($machine['machine_id']) : 'Non défini' ?></td>
                                                    <td><?= isset($machine['reference']) ? htmlspecialchars($machine['reference']) : 'Non défini' ?></td>
                                                    <td><?= isset($machine['designation']) ? htmlspecialchars($machine['designation']) : 'Non défini' ?></td>

                                                    <td><?= isset($machine['type']) ? htmlspecialchars($machine['type']) : 'Non défini' ?></td>
                                                    <td>
                                                        <?php
                                                        if (empty($machine['location'])) {
                                                            echo '<span class="badge badge-secondary">Non défini</span>';
                                                        } elseif ($machine['location'] == 'parc') {
                                                            echo '<span class="badge badge-primary">Parc</span>';
                                                        } elseif ($machine['location'] == 'prodline') {
                                                            echo '<span class="badge badge-success">En production</span>';
                                                        } else {
                                                            echo htmlspecialchars($machine['location']);
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php
                                                        $status = $machine['etat_machine'] ?? 'Non défini';

                                                        if ($status == 'active') {
                                                            echo '<span class="badge badge-success ">' . htmlspecialchars($status) . '</span>';
                                                        } elseif ($status == 'en panne' || $status == 'ferraille') {
                                                            echo '<span class="badge badge-danger">' . htmlspecialchars($status) . '</span>';
                                                        } elseif ($status == 'inactive') {
                                                            echo '<span class="badge badge-warning ">' . htmlspecialchars($status) . '</span>';
                                                        } else {
                                                            echo '<span class="badge badge-secondary ">' . htmlspecialchars($status) . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?= isset($machine['updated_at']) ? htmlspecialchars($machine['updated_at']) : 'Non défini' ?></td>
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
                <?php include(__DIR__ . "/../../../views/layout/footer.php"); ?>
            </div>
        </div>

        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <!-- Scripts JavaScript -->
        <script src="/public/js/jquery-3.6.4.min.js"></script>
        <script src="/public/js/bootstrap.bundle.min.js"></script>
        <script src="/public/js/jquery.dataTables.min.js"></script>
        <script src="/public/js/dataTables.bootstrap4.min.js"></script>
        <script src="/public/js/sb-admin-2.min.js"></script>
        <script src="/public/js/sideBare.js"></script>

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

                // Faire disparaître les messages flash après 3 secondes
                setTimeout(function() {
                    $("#flash-message").fadeOut("slow");
                }, 4000);

                // Mettre à jour les compteurs lors de la recherche
                table.on('search.dt', function() {
                    updateCountersFromTable(table);
                });
            });

            // Fonction pour mettre à jour les compteurs selon le filtre de recherche
            function updateCountersFromTable(table) {
                var countProduction = 0;
                var countParc = 0;
                var countPanne = 0;
                var countTotal = 0;
                var countEmpty = 0;
                var countInactive = 0;

                // Parcourir toutes les lignes visibles du tableau
                table.rows({
                    search: 'applied'
                }).every(function() {
                    var data = this.data();
                    countTotal++;
                    
                    // Vérifier l'emplacement de la machine (colonne 5)
                    if (data[5] && data[5].includes('En production')) {
                        countProduction++;
                    } else if (data[5] && data[5].includes('Parc')) {
                        countParc++;
                    }
                    
                    // Vérifier l'état de la machine (colonne 6)
                    if (data[6] && data[6].includes('en panne')) {
                        countPanne++;
                    }
                    
                    if (!data[6] || data[6].includes('Non défini')) {
                        countEmpty++;
                    }

                    if (data[6] && data[6].includes('inactive')) {
                        countInactive++;
                    }
                });

                // Mettre à jour les compteurs dans l'interface pour les résultats filtrés
                $('#count-production').text(countProduction);
                $('#count-parc').text(countParc);
                $('#count-panne').text(countPanne);
                $('#count-total').text(countTotal);
                $('#count-empty-status').text(countEmpty);
                $('#count-inactive').text(countInactive);
            }

            // Fonction pour obtenir la classe CSS en fonction de l'état
            function getStateBadgeClass(state) {
                switch (state) {
                    case 'En production':
                        return 'badge-success';

                    case 'En panne':
                        return 'badge-danger';
                    default:
                        return 'badge-secondary';
                }
            }
        </script>
    </div>
</body>

</html>

<?php
// Fonctions d'aide pour la mise en forme
function getRowClass($state)
{
    switch ($state) {
        case 'En production':
            return 'table-success';
        case 'Dans le parc':
            return 'table-primary';
        case 'En panne':
            return 'table-danger';
        default:
            return '';
    }
}

function getStateBadgeClass($state)
{
    switch ($state) {
        case 'En production':
            return 'badge-success';
        case 'Dans le parc':
            return 'badge-primary';
        case 'En panne':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}
?>