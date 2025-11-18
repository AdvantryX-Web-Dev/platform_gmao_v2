<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Historique Inventaire</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../views/layout/navbar.php"); ?>

                <div class="container-fluid">

                    <!-- Cartes de statistiques -->
                    <div class="row mb-4">
                        <!-- Taux de Conformité -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Taux de Conformité</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $tauxConformite ?>%</div>
                                            <small class="text-muted">machines inventoriées</small>
                                            <br> <small class="text-muted">
                                                <?= $confirmes ?>/<?= $totalMachines ?> machines </small>

                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-check-circle fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Couverture d'Inventaire -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Couverture d'Inventaire</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $tauxCouverture ?>%</div>
                                            <small class="text-muted"><?= ($confirmes + $nonConformes) ?>/<?= $totalMachines ?> machines inventoriées</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Taux Non conforme -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Non Conforme</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $tauxNonConforme ?>%</div>
                                            <small class="text-muted"><?= $nonConformes ?> machines non conformes</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Machines -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                <?= $isAdmin ? 'Total Machines' : 'Mes Machines' ?>
                                            </div>

                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?= $totalMachines ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php if ($nonInventoriees > 0): ?>
                                                    <span class="text-danger"><?= $nonInventoriees ?> non inventoriées</span>
                                                <?php else: ?>
                                                    <span class="text-success">Toutes inventoriées</span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-cogs fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filtres -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Filtres</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="index.php">
                                <input type="hidden" name="route" value="historyInventaire">
                                <div class="row justify-content-end">
                                    <?php if ($isAdmin): ?>
                                        <div class="col-md-3">
                                            <label for="filter_maintainer" class="form-label">Maintenancier </label>
                                            <select name="filter_maintainer" id="filter_maintainer" class="form-control">
                                                <option value="">Tous les maintenanciers</option>
                                                <?php foreach ($allMaintainers as $maintainer): ?>
                                                    <option value="<?= htmlspecialchars($maintainer['id']) ?>"

                                                        <?= ($filterMaintainer == $maintainer['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($maintainer['matricule']) ?> - <?= htmlspecialchars($maintainer['first_name']) ?> <?= htmlspecialchars($maintainer['last_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                    <?php else: ?>
                                        <!-- Champ caché pour maintenir le filtre du maintenancier connecté -->
                                        <input type="hidden" name="filter_maintainer" value="<?= htmlspecialchars($connectedMaintainerId ?? '') ?>">
                                        <div class="col-md-3">
                                            <label class="form-label">Maintenancier d'inventaire</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($connectedMatricule) ?> - Vous" readonly disabled>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-md-2">
                                        <label for="filter_status" class="form-label">Evaluation</label>
                                        <select name="filter_status" id="filter_status" class="form-control">
                                            <option value="">Toutes les évaluations</option>
                                            <option value="conforme" <?= ($filterStatus == 'conforme') ? 'selected' : '' ?>>Conforme</option>
                                            <option value="non_conforme" <?= ($filterStatus == 'non_conforme') ? 'selected' : '' ?>>Non Conforme</option>

                                        </select>
                                    </div>


                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tableau de comparaison -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Evaluation Inventaire</h6>
                            <div class="btn-group">
                                <a href="index.php?route=historyInventaire&export=excel<?= $exportParams ?>"
                                    class="btn btn-success btn-sm">
                                    <i class="fas fa-file-excel"></i> Exporter Excel
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Machine ID</th>
                                            <th class="d-none d-md-table-cell">Référence</th>
                                            <th class="d-none d-md-table-cell">Type</th>
                                            <th>Emplacement </th>
                                            <th>Statut </th>
                                            <?php if ($isAdmin): ?>
                                                <th>Maintenancier Inventaire</th>
                                                <th>Maintenancier </th>
                                            <?php endif; ?>
                                            <th>Evaluation</th>
                                            <th>Différence</th>
                                            <th>Date Inventaire</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($HistoriqueInventaire as $comp):  ?>

                                            <tr>
                                                <td>
                                                    <?= htmlspecialchars($comp['machine_id'] ?? '') ?>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    <?= htmlspecialchars($comp['reference'] ?? '') ?>
                                                </td>
                                                <td class="d-none d-md-table-cell">
                                                    <?= htmlspecialchars($comp['type'] ?? '') ?>
                                                </td>
                                                <!-- emplacement machine -->
                                                <td>
                                                    <?= htmlspecialchars($comp['current_location'] ?? 'Non défini') ?>
                                                </td>

                                                <!-- statut machine -->

                                                <td>
                                                    <?php
                                                    $machineStatus = $comp['current_status_name'] ?? null;

                                                    $status = $machineStatus ?? null;


                                                    if ($status):
                                                        $statusClass = 'secondary';

                                                        if ($status === 'active') {
                                                            $statusClass = 'success';
                                                        } elseif (in_array($status, ['en panne', 'ferraille'])) {
                                                            $statusClass = 'danger';
                                                        } elseif ($status === 'inactive') {
                                                            $statusClass = 'warning';
                                                        }
                                                    ?>
                                                        <span class="badge badge-<?= $statusClass ?>">
                                                            <?= htmlspecialchars($status) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Non défini </span>
                                                    <?php endif; ?>
                                                </td>


                                                <?php if ($isAdmin): ?>
                                                    <!-- Maintenancier Inventaire -->
                                                    <td>
                                                        <?php if ($comp['inventory_maintainer']): ?>
                                                            <?= htmlspecialchars($comp['inventory_maintainer'] ?? '') ?>
                                                            <!-- <?php if ($comp['inventory_maintainer_matricule']): ?>
                                                                <br><small class="text-muted"><?= htmlspecialchars($comp['inventory_maintainer_matricule']) ?></small>
                                                            <?php endif; ?> -->
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <?php if ($isAdmin): ?>
                                                    <!-- Maintenancier Actuel -->
                                                    <td>
                                                        <?php if ($comp['current_maintainer']): ?>
                                                            <?= htmlspecialchars($comp['current_maintainer']) ?>
                                                            <!-- <?php if ($comp['current_maintainer_matricule']): ?>
                                                                <br><small class="text-muted"><?= htmlspecialchars($comp['current_maintainer_matricule']) ?></small>
                                                            <?php endif; ?> -->
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <!--  inventaire evaluation  -->
                                                <td class="text-center">
                                                    <?php if ($comp['evaluation'] == 'conforme'): ?>
                                                        <span class="badge badge-success">Conforme</span>
                                                    <?php elseif ($comp['evaluation'] == 'non_conforme'): ?>
                                                        <span class="badge badge-danger">Non Conforme</span>

                                                    <?php else: ?>
                                                        <span class="badge badge-secondary"><?= $comp['evaluation'] ?></span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Différence/Non-conformité -->
                                                <td>
                                                    <?php if (!empty($comp['differences']) && $comp['differences'] !== 'non defini'): ?>
                                                        <div class="text-danger">
                                                            <?= $comp['differences'] ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Aucune différence</span>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- Date Inventaire -->
                                                <td>
                                                    <?= htmlspecialchars($comp['inventory_date']) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>
        </div>
    </div>

    <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
    <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
    <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
    <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
    <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
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
                    [0, 'asc']
                ]
            });

            // Filtrage automatique
            $('#filter_maintainer:not(:disabled), #filter_status, #filter_date_from, #filter_date_to').on('change', function() {
                // Soumettre automatiquement le formulaire
                $(this).closest('form').submit();
            });
        });
    </script>
</body>

</html>