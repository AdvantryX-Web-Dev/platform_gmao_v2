<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use App\Models\Machine_model;
use App\Models\Intervention_model;
use App\Models\Database;

// Connexion aux deux bases de données
$dbDigitex = Database::getInstance('db_digitex');
$connDigitex = $dbDigitex->getConnection();

$dbGMAO = Database::getInstance('MAHDCO_MAINT');
$connGMAO = $dbGMAO->getConnection();

// Récupérer le nombre total de machines
$totalMachines = is_array(Machine_model::findAll()) ? count(Machine_model::findAll()) : 0;

// Récupérer le nombre d'interventions préventives
$stmtPrev = $connGMAO->prepare("
    SELECT COUNT(*) FROM gmao__intervention_action
    LEFT JOIN gmao__type_intervention it ON it.id = gmao__intervention_action.intervention_type_id
    WHERE it.type = 'Préventive'
    AND intervention_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmtPrev->execute();
$totalPrev = $stmtPrev->fetchColumn();

// Récupérer le nombre d'interventions curatives
$stmtCur = $connGMAO->prepare("
    SELECT COUNT(*) FROM gmao__intervention_action
    LEFT JOIN gmao__type_intervention it ON it.id = gmao__intervention_action.intervention_type_id
    WHERE it.type = 'Curative'
    AND intervention_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmtCur->execute();
$totalCur = $stmtCur->fetchColumn();

// Récupérer le nombre d'équipements
$stmtEquip = $connGMAO->prepare("
    SELECT COUNT(*) FROM init__equipement
");
$stmtEquip->execute();
$totalEquip = $stmtEquip->fetchColumn();

// Récupérer les dernières interventions
$stmtInterv = $connGMAO->prepare("
    SELECT 
        ia.id, 
        m.reference AS machine_id, 
        it.type AS type,
        ia.intervention_date AS date
    FROM 
        gmao__intervention_action ia
    JOIN 
        db_mahdco.init__machine m ON ia.machine_id = m.id
    LEFT JOIN 
        gmao__type_intervention it ON it.id = ia.intervention_type_id
    ORDER BY 
        ia.intervention_date DESC
    LIMIT 10
");

$stmtInterv->execute();
$dernieresInterventions = $stmtInterv->fetchAll(\PDO::FETCH_ASSOC);
// Obtenir les statistiques d'interventions par mois (6 derniers mois)
$stmtMonthlyStats = $connGMAO->prepare("
    SELECT 
        MONTH(intervention_date) AS month,
        YEAR(intervention_date) AS year,
        COUNT(CASE WHEN it.type = 'Préventive' THEN 1 END) AS preventive,
        COUNT(CASE WHEN it.type = 'Curative' THEN 1 END) AS curative
    FROM gmao__intervention_action
    LEFT JOIN gmao__type_intervention it ON it.id = gmao__intervention_action.intervention_type_id
    WHERE intervention_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(intervention_date), MONTH(intervention_date)
    ORDER BY YEAR(intervention_date), MONTH(intervention_date)
");
$stmtMonthlyStats->execute();
$monthlyStats = $stmtMonthlyStats->fetchAll(\PDO::FETCH_ASSOC);

// Préparer les données pour les graphiques
$months = [];
$preventiveData = [];
$curativeData = [];

foreach ($monthlyStats as $stat) {
    $monthName = date('M', mktime(0, 0, 0, $stat['month'], 10));
    $months[] = $monthName;
    $preventiveData[] = $stat['preventive'];
    $curativeData[] = $stat['curative'];
    
}

// Récupérer les machines par statut
$stmtMachineStatus = $connDigitex->prepare("
    SELECT ms.status_name as status_name, COUNT(m.id) as count
    FROM init__machine m
    JOIN gmao__machines_status ms ON m.machines_status_id = ms.id
    GROUP BY m.machines_status_id
    ORDER BY count DESC
");
$stmtMachineStatus->execute();
$machinesByStatus = $stmtMachineStatus->fetchAll(\PDO::FETCH_ASSOC);


// Récupérer les interventions urgentes ou en retard
$stmtUrgentInterv = $connGMAO->prepare("
    SELECT 
    p.id, 
    m.machine_id AS machine_id, 
    p.planned_date AS date

FROM 
    gmao__planning p
JOIN 
    db_mahdco.init__machine m ON p.machine_id = m.id
    left join 
    gmao__intervention_action ia ON ia.planning_id = p.id
WHERE 
    ia.planning_id is null
    and
    p.planned_date < CURDATE()
ORDER BY 
    p.planned_date ASC
LIMIT 5;

");
$stmtUrgentInterv->execute();
$urgentInterventions = $stmtUrgentInterv->fetchAll(\PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Dashboard | GMAO</title>
    <link rel="icon" type="image/x-icon" href="/public/images/favicon.png" />
    <link rel="stylesheet" href="/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/public/css/table.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/layout/navbar.php"); ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Tableau de Bord</h1>
                       
                    </div>
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Machines</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalMachines ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-cogs fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Interventions Préventives (30j)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalPrev ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-shield-alt fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Interventions Curatives (30j)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalCur ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tools fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Équipements</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalEquip ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Graphique d'évolution des interventions -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Évolution des interventions (6 derniers mois)</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-area">
                                        <canvas id="interventionsChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique de répartition des machines par statut -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Machines par Statut</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="machineStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Dernières interventions -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Dernières interventions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Machine ID</th>
                                                    <th>Type</th>
                                                    <th>Date</th>
                                                    <!-- <th>Actions</th> -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($dernieresInterventions as $interv): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($interv['machine_id'] ?? 'N/A') ?></td>
                                                        <td><?= htmlspecialchars($interv['type'] ?? 'N/A') ?></td>
                                                        <td><?= htmlspecialchars($interv['date'] ?? 'N/A') ?></td>
                                                        
                                                        <!-- <td>
                                                            <a href="index.php?route=intervention_details&id=<?= $interv['id'] ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td> -->
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Interventions urgentes ou en retard -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-danger">Interventions urgentes / En retard</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (count($urgentInterventions) > 0): ?>
                                        <div class="list-group">
                                            <?php foreach ($urgentInterventions as $urgent): ?>
                                                
                                                <a href="index.php?route=intervention_details&id=<?= $urgent['id'] ?>" class="list-group-item list-group-item-action list-group-item-danger">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h5 class="mb-1"><?= htmlspecialchars($urgent['machine_id']) ?></h5>
                                                        <small><?= htmlspecialchars($urgent['date']) ?></small>
                                                    </div>
                                                    <?php if (strtotime($urgent['date']) < time()): ?>
                                                        <small class="text-danger"><i class="fas fa-exclamation-circle"></i> En retard</small>
                                                    <?php endif; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                            <p>Aucune intervention urgente en attente</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/layout/footer.php"); ?>

        </div>
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
        <script src="/public/js/jquery-3.6.4.min.js"></script>
        <script src="/public/js/bootstrap.bundle.min.js"></script>
        <script src="/public/js/sb-admin-2.min.js"></script>
        <script>
          
            // Graphique d'évolution des interventions
            var ctx = document.getElementById('interventionsChart').getContext('2d');
            var interventionsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($months) ?>,
                    datasets: [
                        {
                            label: 'Préventives',
                            data: <?= json_encode($preventiveData) ?>,
                            backgroundColor: 'rgb(54, 184, 204)',
                            borderColor: 'rgb(54, 204, 59)',
                            pointBackgroundColor: 'rgba(54, 185, 204, 1)',
                            pointBorderColor: '#fff',
                            tension: 0.3
                        },
                        {
                            label: 'Curatives',
                            data: <?= json_encode($curativeData) ?>,
                            backgroundColor: 'rgba(246, 194, 62, 0.98)',
                            borderColor: 'rgba(246, 194, 62, 1)',
                            pointBackgroundColor: 'rgba(246, 194, 62, 1)',
                            pointBorderColor: '#fff',
                            tension: 0.3
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            titleColor: "#6e707e",
                            titleMarginBottom: 10,
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            caretPadding: 10,
                        }
                    }
                }
            });

            // Graphique de répartition des machines par statut
            var ctxPie = document.getElementById('machineStatusChart').getContext('2d');
            var machineStatusChart = new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($machinesByStatus, 'status_name')) ?>,
                    
                    datasets: [{
                        data: <?= json_encode(array_column($machinesByStatus, 'count')) ?>,
                         backgroundColor: ['#f6c23e', '#1cc88a', '#e74a3b', '#e38f87', '#4e73df'],
                        hoverBackgroundColor: ['#6e707e', '#6e707e', '#6e707e', '#6e707e', '#6e707e'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            caretPadding: 10,
                        },
                        legend: {
                            position: 'bottom',
                            display: true,
                        }
                    },
                    cutout: '70%',
                },
            });
        </script>
    </div>
</body>
</html>
