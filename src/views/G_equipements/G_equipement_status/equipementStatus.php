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
                            <h6 class="m-0 font-weight-bold text-primary">Etat des équipements</h6>
                            <!-- Suppression du filtre par état -->
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <!-- Replace the current card row with this structure that places all cards in one row -->
                                <div class="row justify-content-center mb-4">
                                    <?php
                                    // Calculer les statistiques
                                    $countProduction = 0;
                                    $countmagasin = 0;
                                    $countDisponible = 0;
                                    $countNonFonctionnel = 0;
                                    $countnondefini = 0;
                                    $countTotal = is_array($equipements) ? count($equipements) : 0;

                                    if (is_array($equipements)) {
                                        foreach ($equipements as $m) {
                                            if (!empty($m['location_category']) && $m['location_category'] == 'prodline') {
                                                $countProduction++;
                                            }
                                            if (!empty($m['location_category']) && $m['location_category'] == 'magasin') {
                                                $countmagasin++;
                                            }
                                            if (!empty($m['etat_equipement']) && ($m['etat_equipement'] == 'fonctionnelle') && $m['location_category'] == 'magasin') {
                                                $countDisponible++;
                                            } elseif (!empty($m['etat_equipement']) && ($m['etat_equipement'] == 'non fonctionnelle')) {
                                                $countNonFonctionnel++;
                                            }
                                            if (empty($m['etat_equipement'])) {
                                                $countnondefini++;
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
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">dans le magasin</div>
                                                        <div class="status-count text-gray-800" id="count-magasin"><?php echo $countmagasin; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-cogs fa-2x status-card-icon text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- prod Card -->
                                    <div class="col-xl-2 col-md-4 mb-4">
                                        <div class="card border-left-primary shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Dans la production</div>
                                                        <div class="status-count text-gray-800" id="count-production"><?php echo $countProduction; ?></div>
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
                                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Equipements non définies</div>
                                                        <div class="status-count text-gray-800" id="count-empty-status"><?php echo $countnondefini; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-question-circle fa-2x status-card-icon text-warning"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- non fonctionnel Card -->
                                    <div class="col-xl-2 col-md-4 mb-4">
                                        <div class="card border-left-danger shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Feraille</div>
                                                        <div class="status-count text-gray-800" id="count-nonFonctionnel"><?php echo $countNonFonctionnel; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-exclamation-triangle fa-2x status-card-icon text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- fonctionnel Card -->
                                    <div class="col-xl-2 col-md-4 mb-4">
                                        <div class="card border-left-success shadow h-100 py-3">
                                            <div class="card-body">
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col mr-2">
                                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Disponible</div>
                                                        <div class="status-count text-gray-800" id="count-Fonctionnel"><?php echo $countDisponible; ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <i class="fas fa-check-circle fa-2x status-card-icon text-success"></i>
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
                                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total equipements implementés</div>
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
                                            <!-- <th></th> -->
                                            <th>Equipement</th>
                                            <th>Référence</th>
                                            <th>Machine</th>
                                            <th>Emplacement</th>
                                            <th>Etat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($equipements) && count($equipements) > 0): ?>
                                            <?php foreach ($equipements as $equipement): ?>
                                                <tr>
                                                    <!-- <td>
                                                        <a href="?route=mouvement_equipements/history&equipement_id=<?= htmlspecialchars($equipement['id']) ?>" class="btn btn-info btn-sm" title="Voir l'historique des mouvements">
                                                            <i class="fas fa-history"></i>
                                                        </a>

                                                    </td> -->
                                                    <td><?= isset($equipement['equipment_id']) ? htmlspecialchars($equipement['equipment_id']) : 'Non défini' ?></td>
                                                    <td><?= isset($equipement['reference']) ? htmlspecialchars($equipement['reference']) : 'Non défini' ?></td>
                                                    <td><?= isset($equipement['machine_id']) ? htmlspecialchars($equipement['machine_id']) : 'Non défini' ?></td>

                                                    <td>
                                                        <?php
                                                        if (empty($equipement['location_category'])) {
                                                            echo '<span class="badge badge-secondary">Non défini</span>';
                                                        } elseif ($equipement['location_category'] == 'prodline') {
                                                            echo '<span class="badge badge-success">' . htmlspecialchars($equipement['location_name']) . '</span>';
                                                        } else {
                                                            echo '<span class="badge badge-primary">' . htmlspecialchars($equipement['location_name']) . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td class="text-center">
                                                        
                                                        <?php
                                                        $status = $equipement['etat_equipement'] ?? 'Non défini';
                                                        if (empty($equipement['etat_equipement'])) {
                                                            echo '<span class="badge badge-secondary ">' . 'non défini' . '</span>';
                                                        } elseif ($status == 'disponible' ) {
                                                            echo '<span class="badge badge-success ">' . 'Disponible' . '</span>';
                                                        } elseif ($status == 'non disponible') {
                                                            echo '<span class="badge badge-warning ">' . 'Non disponible' . '</span>';
                                                        } elseif ($status == 'ferraille' ) {
                                                            echo '<span class="badge badge-danger ">' . 'Ferraille' . '</span>';
                                                        }else{
                                                            echo '<span class="badge badge-secondary ">' .   $status . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6">Aucun équipement trouvé.</td>
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
                // table.on('search.dt', function() {
                //     updateCountersFromTable(table);
                // });
            });
        </script>
    </div>
</body>

</html>