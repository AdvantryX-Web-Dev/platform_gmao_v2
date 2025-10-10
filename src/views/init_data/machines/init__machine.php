<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Models\Machine_model;

$machines = Machine_model::findAll();

// Vérifie si l'utilisateur est un administrateur
$isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Liste des machines</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/init_Machine.css">
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
                            <h6 class="m-0 font-weight-bold text-primary">Liste des machines :</h6>
                            <?php if ($isAdmin): ?>
                                <div>

                                    <a href="../../platform_gmao/public/index.php?route=machine/create" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Ajouter une machine
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <?php if ($isAdmin): ?>
                                                <th style="width: 5%;">Actions</th>
                                            <?php endif; ?>
                                            <th>Machine ID</th>
                                            <th class="d-none d-md-table-cell">Référence</th>
                                            <th>Marque</th>
                                            <th class="d-none d-md-table-cell">Type</th>
                                            <th>Désignation</th>
                                            <th class="d-none d-md-table-cell">Numéro Facture</th>
                                            <th class="d-none d-md-table-cell">Date Facture</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($machines) && count($machines) > 0): ?>
                                            <?php foreach ($machines as $machine): ?>
                                                <tr>
                                                    <?php if ($isAdmin): ?>
                                                        <td>
                                                            <a href="../../platform_gmao/public/index.php?route=machine/edit&id=<?= urlencode($machine['machine_id'] ?? '') ?>"><i class="fas fa-edit m-2"></i></a>
                                                            <a href="../../platform_gmao/public/index.php?route=machine/delete&id=<?= urlencode($machine['machine_id'] ?? '') ?>" onclick="return confirm('Supprimer cette machine ?');"><i class="fas fa-trash text-danger"></i></a>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td><?= htmlspecialchars($machine['machine_id'] ?? '') ?></td>
                                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($machine['reference'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($machine['brand'] ?? '') ?></td>
                                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($machine['type'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($machine['designation'] ?? '') ?></td>
                                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($machine['billing_num'] ?? '') ?></td>
                                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($machine['bill_date'] ?? '') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="<?= $isAdmin ? 8 : 7 ?>">Aucune machine trouvée.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Carte pour afficher les statistiques -->
                    <div id="statsCard" class="card shadow mb-4" style="display: none;">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h5 class="m-0"><i class="fas fa-chart-bar"></i> Statistiques de la Machine</h5>
                            <div>
                                <button type="button" id="reset_chart_filters" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-redo"></i> Réinitialiser les filtres
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <input type="hidden" id="current_machine_id" value="" />
                            <div class="row mb-4">
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="chart_start_date">Du</label>
                                        <input type="date" id="chart_start_date" class="form-control form-control-sm" />
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="form-group">
                                        <label for="chart_end_date">Au</label>
                                        <input type="date" id="chart_end_date" class="form-control form-control-sm" />
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" id="apply_chart_filters" class="btn btn-sm btn-primary">
                                        <i class="fas fa-filter"></i> Filtrer
                                    </button>
                                </div>
                            </div>
                            <canvas id="myChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../../../views/layout/footer.php"); ?>

        </div>
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
        <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
        <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
        <script src="/platform_gmao/public/js/sideBare.js"></script>
        <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
        <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
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
                    pageLength: 10
                });

                // Faire disparaître les messages flash après 4 secondes
                setTimeout(function() {
                    $("#flash-message").fadeOut("slow");
                }, 4000);
            });
        </script>
        <!-- Script pour initialiser les données du graphique -->
        <script>
            // Données des statistiques d'interventions pour chaque machine
            var allMachinesData = <?php echo json_encode($machine_stats ?? []); ?>;

            // Fonction pour charger les données d'une machine spécifique avec les filtres appliqués
            function loadMachineData(machineId, startDate, endDate) {
                let url = '/platform_gmao/public/index.php?route=machine/stats&id_machine=' + machineId;

                if (startDate) {
                    url += '&start_date=' + startDate;
                }

                if (endDate) {
                    url += '&end_date=' + endDate;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Erreur:', data.error);
                            return;
                        }

                        // Mettre à jour les données de la machine
                        allMachinesData[machineId] = data;

                        // Réafficher le graphique avec les nouvelles données
                        afficherGraphique(machineId);
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des données:', error);
                    });
            }

            // Ajouter un écouteur pour le bouton d'application des filtres
            document.addEventListener('DOMContentLoaded', function() {
                const applyFilterBtn = document.getElementById('apply_chart_filters');
                if (applyFilterBtn) {
                    applyFilterBtn.addEventListener('click', function() {
                        const machineId = document.getElementById('current_machine_id').value;
                        const startDate = document.getElementById('chart_start_date').value;
                        const endDate = document.getElementById('chart_end_date').value;

                        if (machineId) {
                            loadMachineData(machineId, startDate, endDate);
                        }
                    });
                }
            });
        </script>
    </div>
</body>

</html>