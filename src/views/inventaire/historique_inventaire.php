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
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pourcentageConformite ?>%</div>
                                            <small class="text-muted"><?= $confirmes ?>/<?= $totalInventoriees ?> machines inventoriées</small>
                                            <br> <small class="text-muted"><?= $confirmes ?>/<?= $totalMachines ?> machines </small>

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
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">machines inventoriées</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pourcentageinventoriees ?>%</div>
                                            <small class="text-muted"><?= $totalInventoriees ?>/<?= $totalMachines ?> machines inventoriées</small>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clipboard-list fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Taux d'Écarts -->
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Non Conforme</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pourcentageNonConforme ?>%</div>
                                            <small class="text-muted"><?= $differences ?> Non conforme</small>
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
                                          
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalMachines ?></div>
                                            <small class="text-muted">
                                                <?php if ($totalNonInventoriees > 0): ?>
                                                    <span class="text-danger"><?= $totalNonInventoriees ?> non inventoriées</span>
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
                                            <label for="filter_maintainer" class="form-label">Maintenancier d'inventaire</label>
                                            <select name="filter_maintainer" id="filter_maintainer" class="form-control">
                                                <option value="">Tous les maintenanciers</option>
                                                <?php foreach ($allMaintainers as $maintainer): ?>
                                                    <option value="<?= htmlspecialchars($maintainer['matricule']) ?>"

                                                        <?= ($filterMaintainer == $maintainer['matricule']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($maintainer['matricule']) ?> - <?= htmlspecialchars($maintainer['first_name']) ?> <?= htmlspecialchars($maintainer['last_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                    <?php else: ?>
                                        <!-- Champ caché pour maintenir le filtre du maintenancier connecté -->
                                        <input type="hidden" name="filter_maintainer" value="<?= htmlspecialchars($connectedMatricule) ?>">
                                        <div class="col-md-3">
                                            <label class="form-label">Maintenancier d'inventaire</label>
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($connectedMatricule) ?> - Vous" readonly disabled>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-md-3">
                                        <label for="filter_status" class="form-label">Évaluation</label>
                                        <select name="filter_status" id="filter_status" class="form-control">
                                            <option value="">Toutes les évaluations</option>
                                            <option value="conforme" <?= ($filterStatus == 'conforme') ? 'selected' : '' ?>>Conforme</option>
                                            <option value="non_conforme" <?= ($filterStatus == 'non_conforme') ? 'selected' : '' ?>>Non Conforme</option>
                                            <option value="non_inventoriee" <?= ($filterStatus == 'non_inventoriee') ? 'selected' : '' ?>>Non Inventoriée</option>
                                            <option value="supprimer" <?= ($filterStatus == 'supprimer') ? 'selected' : '' ?>>Machine Supprimée</option>
                                            <option value="ajouter" <?= ($filterStatus == 'ajouter') ? 'selected' : '' ?>>Machine Ajoutée</option>
                                        </select>
                                    </div>

                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Tableau de comparaison -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Comparaison Machines vs Historique Inventaire</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Machine ID</th>
                                            <th>Référence</th>
                                            <th>Type</th>
                                            <th>Emplacement Actuel</th>
                                            <th>Statut Actuel</th>
                                            <?php if ($isAdmin): ?>
                                                <th>Maintenancier Inventaire</th>
                                                <th>Maintenancier Actuel</th>
                                            <?php endif; ?>
                                            <th>Évaluation</th>
                                            <th>Différence</th>
                                            <th>Date Inventaire</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($comparisons as $comp): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($comp['machine']): ?>
                                                        <?= htmlspecialchars($comp['machine']['machine_id'] ?? '') ?>
                                                    <?php elseif ($comp['historique']): ?>
                                                        <?= htmlspecialchars($comp['historique']['machine_code'] ?? '') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($comp['machine']): ?>
                                                        <?= htmlspecialchars($comp['machine']['reference'] ?? '') ?>
                                                    <?php elseif ($comp['historique']): ?>
                                                        <?= htmlspecialchars($comp['historique']['machine_reference'] ?? '') ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (($comp['machine']) || ($comp['historique'])): ?>
                                                        <?= htmlspecialchars($comp['machine']['type'] ?? $comp['historique']['type'] ?? '') ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Non défini ajoutée</span>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- emplacement machine -->
                                                <td>
                                                    <?php
                                                    $machineLocationName = $comp['machine']['location_name'] ?? null;
                                                    $historiqueLocationName = $comp['historique']['location_name'] ?? null;

                                                    $machineCategory = $comp['machine']['location_category'] ?? null;
                                                    $historiqueCategory = $comp['historique']['location_category'] ?? null;

                                                    $locationName = $machineLocationName ?? $historiqueLocationName;
                                                    $locationCategory = $machineCategory ?? $historiqueCategory;
                                                    ?>

                                                    <?php if ($locationName): ?>
                                                        <span class="badge badge-<?= ($locationCategory === 'prodline') ? 'success' : 'primary' ?>">
                                                            <?= htmlspecialchars($locationName) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">Non défini </span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- statut machine -->

                                                <td>
                                                    <?php
                                                    $machineStatus = $comp['machine']['status_name'] ?? null;
                                                    $historiqueStatus = $comp['historique']['status_name'] ?? null;

                                                    $status = $machineStatus ?? $historiqueStatus;

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
                                                        <?php if ($comp['historique']): ?>
                                                            <?= htmlspecialchars($comp['historique']['maintainer_matricule'] ?? '') ?>
                                                            <?php if ($comp['historique']['maintainer_name']): ?>
                                                                <br><small class="text-muted"><?= htmlspecialchars($comp['historique']['maintainer_name']) ?></small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <?php if ($isAdmin): ?>
                                                    <!-- Maintenancier Actuel -->
                                                    <td>
                                                        <?php if ($comp['machine'] && $comp['machine']['current_maintainer_matricule']): ?>
                                                            <?= htmlspecialchars($comp['machine']['current_maintainer_matricule']) ?>
                                                            <?php if ($comp['machine']['current_maintainer_name']): ?>
                                                                <br><small class="text-muted"><?= htmlspecialchars($comp['machine']['current_maintainer_name']) ?></small>
                                                            <?php endif; ?>
                                                        <?php elseif (!$comp['machine']): ?>
                                                            <span class="text-muted">Machine ajoutée</span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Non assigné</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                                <!--  inventaire evaluation  -->
                                                <td class="text-center">
                                                    <?php if ($comp['status'] == 'conforme'): ?>
                                                        <span class="badge badge-success">Conforme</span>
                                                    <?php elseif ($comp['status'] == 'non_conforme'): ?>
                                                        <span class="badge badge-danger">Non Conforme</span>
                                                    <?php elseif ($comp['status'] == 'non_inventoriee'): ?>
                                                        <span class="badge badge-light">Non Inventoriée</span>
                                                    <?php elseif ($comp['status'] == 'supprimer'): ?>
                                                        <span class="badge badge-warning">Machine Supprimée</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary"><?= $comp['status'] ?></span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- Différence/Non-conformité -->
                                                <td>
                                                    <?php if ($comp['status'] == 'non_conforme' && $comp['machine'] && $comp['historique'] && $comp['machine']['current_maintainer_matricule'] != $comp['historique']['maintainer_matricule']): ?>
                                                        <span class="text-danger">Machine ajoutée à ce maintenancier:
                                                            <?= htmlspecialchars($comp['historique']['maintainer_matricule'] ?? '') ?>
                                                            <?php if ($comp['historique']['maintainer_name']): ?>
                                                                <br><small class="text-muted"><?= htmlspecialchars($comp['historique']['maintainer_name']) ?></small>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php elseif ($comp['status'] == 'non_conforme'): ?>
                                                        <?php
                                                        $differences = [];
                                                        if ($comp['machine'] && $comp['historique']) {
                                                            if ($comp['machine']['machines_location_id'] != $comp['historique']['location_id']) {
                                                                $differences[] = '*Localisation modifiée: ' . ($comp['machine']['location_name'] ?? 'Non défini') . ' → ' . ($comp['historique']['location_name'] ?? 'Non défini');
                                                            }
                                                            if ($comp['machine']['machines_status_id'] != $comp['historique']['status_id']) {
                                                                $differences[] = '*Statut modifié: ' . ($comp['machine']['status_name'] ?? 'Non défini') . ' → ' . ($comp['historique']['status_name'] ?? 'Non défini');
                                                            }
                                                        }
                                                        echo implode('<br>', $differences);
                                                        ?>

                                                    <?php
                                                    elseif ($comp['status'] == 'non_inventoriee'): ?>
                                                        <span class="text-muted">Non inventoriée</span>
                                                    <?php
                                                    elseif ($comp['status'] == 'supprimer'): ?>
                                                        <span class="text-muted">Non inventoriée ou supprimée</span>
                                                    <?php elseif ($comp['status'] == 'ajouter'): ?>
                                                        <span class="text-muted">Machine ajoutée</span>
                                                    <?php elseif ($comp['status'] == 'conforme'): ?>
                                                        <span class="text-success">Données conformes </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- Date Inventaire -->
                                                <td>
                                                    <?php if ($comp['historique'] && $comp['historique']['created_at']): ?>
                                                        <?= date('Y-m-d H:i', strtotime($comp['historique']['created_at'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
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
            $('#filter_maintainer:not(:disabled), #filter_status').on('change', function() {
                // Soumettre automatiquement le formulaire
                $(this).closest('form').submit();
            });
        });
    </script>
</body>

</html>