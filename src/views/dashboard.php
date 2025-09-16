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

$dbGMAO = Database::getInstance('db_digitex');
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
    SELECT COUNT(*) FROM gmao__init_equipment
");
$stmtEquip->execute();
$totalEquip = $stmtEquip->fetchColumn();

// Récupérer les statistiques de performance des machines (OPTIMISÉE)
$stmtPerformance = $connDigitex->prepare("
    SELECT 
        COUNT(*) as total_machines,
        SUM(CASE WHEN ms.status_name = 'en panne' THEN 1 ELSE 0 END) as machines_en_panne,
        SUM(CASE WHEN ms.status_name = 'fonctionnelle' THEN 1 ELSE 0 END) as machines_fonctionnelles,
        SUM(CASE WHEN ms.status_name = 'ferraille' THEN 1 ELSE 0 END) as machines_ferraille,
        SUM(CASE WHEN m.machines_status_id IS NULL THEN 1 ELSE 0 END) as machines_non_definies
    FROM init__machine m
    LEFT JOIN gmao__status ms ON m.machines_status_id = ms.id
");
$stmtPerformance->execute();
$performance = $stmtPerformance->fetch(\PDO::FETCH_ASSOC);

// Calculer les machines actives et inactives en une seule requête
$stmtActiveInactive = $connDigitex->prepare("
    SELECT 
        SUM(CASE WHEN pp.machine_id IS NOT NULL AND pp.p_state = 1 THEN 1 ELSE 0 END) as machines_actives
    FROM init__machine m
    LEFT JOIN gmao__status ms ON m.machines_status_id = ms.id
    LEFT JOIN prod__presence pp ON pp.machine_id = m.machine_id AND pp.cur_date = CURDATE()
    WHERE  m.machines_status_id IS NOT NULL
");
$stmtActiveInactive->execute();
$activeInactive = $stmtActiveInactive->fetch(\PDO::FETCH_ASSOC);

// Combiner les résultats
$performance['machines_actives'] = $activeInactive['machines_actives'] ?? 0;
$performance['machines_inactives'] = $performance['total_machines'] - $performance['machines_en_panne'] - $performance['machines_fonctionnelles'] - $performance['machines_ferraille'] - $performance['machines_non_definies'] - $performance['machines_actives'];

// Calculer le taux d'activité
$performance['taux_activite'] = $performance['total_machines'] > 0 ?
    round(($performance['machines_actives'] / $performance['total_machines']) * 100, 1) : 0;

// Récupérer les interventions par type cette semaine
$stmtWeeklyInterv = $connGMAO->prepare("
    SELECT 
        COUNT(CASE WHEN it.type = 'Préventive' THEN 1 END) as preventive_semaine,
        COUNT(CASE WHEN it.type = 'Curative' THEN 1 END) as curative_semaine,
        AVG(DATEDIFF(ia.intervention_date, p.planned_date)) as temps_reponse_moyen
    FROM gmao__intervention_action ia
    LEFT JOIN gmao__type_intervention it ON it.id = ia.intervention_type_id
    LEFT JOIN gmao__planning p ON p.id = ia.planning_id
    WHERE ia.intervention_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmtWeeklyInterv->execute();
$weeklyStats = $stmtWeeklyInterv->fetch(\PDO::FETCH_ASSOC);

// Récupérer les statistiques d'équipements
$stmtEquipmentStats = $connGMAO->prepare("
    SELECT 
        COUNT(*) as total_equipements,
        COUNT(CASE WHEN es.status_name = 'Disponible' THEN 1 END) as equipements_disponibles,
        COUNT(CASE WHEN es.status_name = 'En utilisation' THEN 1 END) as equipements_utilises,
        COUNT(CASE WHEN es.status_name = 'En maintenance' THEN 1 END) as equipements_maintenance,
        COUNT(CASE WHEN es.status_name = 'Hors service' THEN 1 END) as equipements_hs
    FROM gmao__init_equipment e
    LEFT JOIN gmao__status es ON e.status_id = es.id
");
$stmtEquipmentStats->execute();
$equipmentStats = $stmtEquipmentStats->fetch(\PDO::FETCH_ASSOC);

// Récupérer les équipements disponibles (non affectés aux machines)
$stmtAvailableEquipment = $connGMAO->prepare("
    SELECT COUNT(*) as equipements_disponibles
    FROM gmao__init_equipment e
    INNER JOIN gmao__status es ON e.status_id = es.id
    LEFT JOIN (
        -- On récupère uniquement la dernière ligne de chaque équipement
        SELECT pie1.*
        FROM gmao__prod_implementation_equipment pie1
        INNER JOIN (
            SELECT accessory_ref, MAX(id) AS last_id
            FROM gmao__prod_implementation_equipment
            GROUP BY accessory_ref
        ) AS last_pie
        ON pie1.id = last_pie.last_id
    ) AS pie ON e.equipment_id = pie.accessory_ref
    WHERE es.status_name = 'fonctionnelle'
      AND (pie.id IS NULL OR pie.is_removed = 1)
");
$stmtAvailableEquipment->execute();
$availableEquipment = $stmtAvailableEquipment->fetch(\PDO::FETCH_ASSOC);


// Récupérer les équipements par catégorie
$stmtEquipmentByCategory = $connGMAO->prepare("
    SELECT 
        c.category_name,
        COUNT(e.id) as count
    FROM gmao__init_equipment e
    LEFT JOIN gmao__init__category c ON e.equipment_category_id = c.id
    GROUP BY e.equipment_category_id, c.category_name
    ORDER BY count DESC
    LIMIT 5
");
$stmtEquipmentByCategory->execute();
$equipmentByCategory = $stmtEquipmentByCategory->fetchAll(\PDO::FETCH_ASSOC);

// Récupérer le nombre total de maintenanciers
$stmtMaintainers = $connGMAO->prepare("
    SELECT COUNT(*) as total_maintainers
    FROM init__employee 
    WHERE qualification = 'MAINTAINER'
");
$stmtMaintainers->execute();
$maintainers = $stmtMaintainers->fetch(\PDO::FETCH_ASSOC);

// Récupérer les mouvements d'équipements récents
$stmtRecentMovements = $connGMAO->prepare("
    SELECT 
        COUNT(CASE WHEN type_Mouv = 'entre_magasin' THEN 1 END) as entrees_semaine,
        COUNT(CASE WHEN type_Mouv = 'sortie_magasin' THEN 1 END) as sorties_semaine,
        COUNT(*) as total_mouvements
    FROM gmao__mouvement_equipment 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmtRecentMovements->execute();
$recentMovements = $stmtRecentMovements->fetch(\PDO::FETCH_ASSOC);
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

// Récupérer les machines par statut avec logique active/inactive basée sur prod__presence (OPTIMISÉE)
// Une machine est considérée ACTIVE si :
// - Elle a un status_id (pas NULL)
// - Son status n'est PAS 'en panne', 'ferraille', ou 'fonctionnelle'
// - Elle est présente dans prod__presence avec cur_date = aujourd'hui ET p_state = 1
// Une machine est INACTIVE si elle ne remplit pas les conditions d'ACTIVE
$stmtMachineStatus = $connDigitex->prepare("
    SELECT 
        'active' as status_name,
        COUNT(CASE 
            WHEN m.machines_status_id IS NOT NULL 
                AND ms.status_name NOT IN ('en panne', 'ferraille', 'fonctionnelle')
                AND pp.machine_id IS NOT NULL 
                AND pp.cur_date = CURDATE() 
                AND pp.p_state = 1
            THEN 1 END) as count
    FROM init__machine m
    LEFT JOIN gmao__status ms ON m.machines_status_id = ms.id
    LEFT JOIN prod__presence pp ON pp.machine_id = m.machine_id AND pp.cur_date = CURDATE()
    
    UNION ALL
    
    SELECT 
        'inactive' as status_name,
        COUNT(CASE 
            WHEN m.machines_status_id IS NOT NULL 
                AND ms.status_name NOT IN ('en panne', 'ferraille', 'fonctionnelle')
                AND (pp.machine_id IS NULL OR pp.cur_date != CURDATE() OR pp.p_state != 1) 
            THEN 1 END) as count
    FROM init__machine m
    LEFT JOIN gmao__status ms ON m.machines_status_id = ms.id
    LEFT JOIN prod__presence pp ON pp.machine_id = m.machine_id AND pp.cur_date = CURDATE()
    
    UNION ALL
    
    SELECT 
        'en panne' as status_name,
        COUNT(CASE WHEN ms.status_name = 'en panne' THEN 1 END) as count
    FROM init__machine m
    LEFT JOIN gmao__status ms ON m.machines_status_id = ms.id
    
    UNION ALL
    
    SELECT 
        'ferraille' as status_name,
        COUNT(CASE WHEN ms.status_name = 'ferraille' THEN 1 END) as count
    FROM init__machine m
    LEFT JOIN gmao__status ms ON m.machines_status_id = ms.id
    
    UNION ALL
    
    SELECT 
        'fonctionnelle' as status_name,
        COUNT(CASE WHEN ms.status_name = 'fonctionnelle' THEN 1 END) as count
    FROM init__machine m
    LEFT JOIN gmao__status ms ON m.machines_status_id = ms.id
    
    UNION ALL
    
    SELECT 
        'non defini' as status_name,
        COUNT(CASE WHEN m.machines_status_id IS NULL THEN 1 END) as count
    FROM init__machine m
    
    ORDER BY 
        CASE status_name 
            WHEN 'active' THEN 1
            WHEN 'inactive' THEN 2
            WHEN 'en panne' THEN 3
            WHEN 'ferraille' THEN 4
            WHEN 'fonctionnelle' THEN 5
            WHEN 'non defini' THEN 6
        END
");
$stmtMachineStatus->execute();
$machinesByStatus = $stmtMachineStatus->fetchAll(\PDO::FETCH_ASSOC);
// Récupérer les équipements par statut (Fonctionnel/Non fonctionnel/Non défini)
$stmtEquipmentStatus = $connGMAO->prepare("
    SELECT 
        CASE 
            WHEN es.status_name = 'fonctionnelle' THEN 'Fonctionnel'
            WHEN es.status_name = 'non fonctionnelle' THEN 'Non fonctionnel'
            ELSE 'Non défini'
        END AS status_category,
        COUNT(e.id) AS count
    FROM gmao__init_equipment e
    LEFT JOIN gmao__status es ON e.status_id = es.id
    GROUP BY 
        CASE 
            WHEN es.status_name = 'fonctionnelle' THEN 'Fonctionnel'
            WHEN es.status_name = 'non fonctionnelle' THEN 'Non fonctionnel'
            ELSE 'Non défini'
        END
    ORDER BY count DESC
");
$stmtEquipmentStatus->execute();
$equipmentsByStatus = $stmtEquipmentStatus->fetchAll(\PDO::FETCH_ASSOC);


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
$urgentInterventions = $stmtUrgentInterv->fetchAll(\PDO::FETCH_ASSOC);;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <title>Dashboard | GMAO</title>
    <link rel="icon" type="image/x-icon" href="/platform_gmao/public/images/favicon.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .progress-sm {
            height: 0.5rem;
        }

        .border-left-secondary {
            border-left: 0.25rem solid #6c757d !important;
        }

        .text-secondary {
            color: #6c757d !important;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .card-header h6 {
            color: white !important;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .border-left-danger {
            border-left: 4px solid #e74a3b !important;
        }

        .list-group-item {
            transition: all 0.3s ease;
        }

        .list-group-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .kpi-icon {
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .card:hover .kpi-icon {
            opacity: 1;
            transform: scale(1.1);
        }
    </style>
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
                    <!-- KPI Cards Row 1 -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Machines Actives</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $performance['machines_actives'] ?? 0 ?></div>
                                            <div class="text-xs text-gray-500">sur <?= $performance['total_machines'] ?? $totalMachines ?> machines</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-play-circle fa-2x text-gray-300 kpi-icon"></i>
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
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Maintainers</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $maintainers['total_maintainers'] ?? 0 ?></div>
                                            <div class="text-xs text-gray-500">Maintenanciers disponibles</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-cog fa-2x text-gray-300 kpi-icon"></i>
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
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Machines en Panne</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $performance['machines_en_panne'] ?? 0 ?></div>
                                            <div class="text-xs text-danger">
                                                <?php if (($performance['machines_en_panne'] ?? 0) > 0): ?>
                                                    <i class="fas fa-exclamation-triangle"></i> Attention requise
                                                <?php else: ?>
                                                    <i class="fas fa-check"></i> Aucune panne
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300 kpi-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Interventions (7j)</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= ($weeklyStats['preventive_semaine'] ?? 0) + ($weeklyStats['curative_semaine'] ?? 0) ?></div>
                                            <div class="text-xs text-gray-500">
                                                <span class="text-success"><?= $weeklyStats['preventive_semaine'] ?? 0 ?> Prév.</span> |
                                                <span class="text-warning"><?= $weeklyStats['curative_semaine'] ?? 0 ?> Cur.</span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-tools fa-2x text-gray-300 kpi-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Cards Row 2 -->
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Équipements</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalEquip ?></div>
                                            <div class="text-xs text-gray-500">Total équipements</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-boxes fa-2x text-gray-300 kpi-icon"></i>
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
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Équipements Disponibles</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $availableEquipment['equipements_disponibles'] ?? 0 ?></div>
                                            <div class="text-xs text-gray-500">
                                                Non affectés aux machines
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-boxes fa-2x text-gray-300 kpi-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-12 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Mouvements Équipements (7j)</div>
                                            <div class="row">
                                                <div class="col-4 text-center">
                                                    <div class="h6 mb-0 font-weight-bold text-success"><?= $recentMovements['entrees_semaine'] ?? 0 ?></div>
                                                    <div class="text-xs text-success">Entrée Magasin</div>
                                                </div>
                                                <div class="col-4 text-center">
                                                    <div class="h6 mb-0 font-weight-bold text-danger"><?= $recentMovements['sorties_semaine'] ?? 0 ?></div>
                                                    <div class="text-xs text-danger">Sortie Magasin</div>
                                                </div>
                                                <div class="col-4 text-center">
                                                    <div class="h6 mb-0 font-weight-bold text-primary"><?= $recentMovements['total_mouvements'] ?? 0 ?></div>
                                                    <div class="text-xs text-primary">Total Mouvements</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-exchange-alt fa-2x text-gray-300 kpi-icon"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Graphique d'évolution des interventions -->
                        <div class="col-xl-6 col-lg-6">
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
                          <div class="col-xl-3 col-lg-3">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Statut des machines</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="machineStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Graphique de répartition des équipements par statut -->
                        <div class="col-xl-3 col-lg-3">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary"> Statut des équipements</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-pie pt-4 pb-2">
                                        <canvas id="equipmentStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                 

                    <div class="row">
                        <!-- Alertes et Actions Prioritaires -->
                        <div class="col-xl-12 col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Centre d'Alertes
                                    </h6>
                                    <div class="dropdown no-arrow">
                                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-right shadow">
                                            <a class="dropdown-item" href="#">Actualiser</a>
                                            <a class="dropdown-item" href="#">Exporter</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <!-- Interventions urgentes -->
                                        <div class="col-xl-6 col-lg-6">
                                            <h6 class="font-weight-bold text-danger mb-3">
                                                <i class="fas fa-clock"></i> Interventions en Retard (<?= count($urgentInterventions) ?>)
                                            </h6>
                                            <?php if (count($urgentInterventions) > 0): ?>
                                                <div class="list-group list-group-flush">
                                                    <?php foreach ($urgentInterventions as $urgent): ?>
                                                        <div class="list-group-item border-left-danger px-3 py-2">
                                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                                <div>
                                                                    <h6 class="mb-1 font-weight-bold"><?= htmlspecialchars($urgent['machine_id']) ?></h6>
                                                                    <small class="text-danger">
                                                                        <i class="fas fa-calendar-times"></i>
                                                                        Prévu le <?= date('d/m/Y', strtotime($urgent['date'])) ?>
                                                                        <span class="badge badge-danger ml-2">
                                                                            <?= abs(floor((strtotime($urgent['date']) - time()) / 86400)) ?> jours de retard
                                                                        </span>
                                                                    </small>
                                                                </div>
                                                                <a href="index.php?route=intervention_details&id=<?= $urgent['id'] ?>" class="btn btn-sm btn-danger">
                                                                    <i class="fas fa-tools"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center py-3">
                                                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                                                    <p class="text-muted mb-0">Aucune intervention en retard</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Machines critiques -->
                                        <div class="col-xl-6 col-lg-6">
                                            <h6 class="font-weight-bold text-warning mb-3">
                                                <i class="fas fa-exclamation-circle"></i> Machines à Surveiller
                                            </h6>
                                            <div class="row">
                                                <div class="col-6">
                                                    <div class="card bg-danger text-white shadow-sm mb-2">
                                                        <div class="card-body py-2">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-times-circle fa-2x mr-3"></i>
                                                                <div>
                                                                    <div class="h4 mb-0"><?= $performance['machines_en_panne'] ?? 0 ?></div>
                                                                    <div class="small">En Panne</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="card bg-secondary text-white shadow-sm mb-2">
                                                        <div class="card-body py-2">
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-pause-circle fa-2x mr-3"></i>
                                                                <div>
                                                                    <div class="h4 mb-0"><?= $performance['machines_inactives'] ?? 0 ?></div>
                                                                    <div class="small">Inactives</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                    </div>
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
        <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
        <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
        <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
        <script>
            // Graphique d'évolution des interventions
            var ctx = document.getElementById('interventionsChart').getContext('2d');
            var interventionsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($months) ?>,
                    datasets: [{
                            label: 'Préventives',
                            data: <?= json_encode($preventiveData) ?>,
                            backgroundColor: 'rgba(102, 126, 234, 0.8)',
                            borderColor: 'rgba(102, 126, 234, 1)',
                            borderWidth: 2,
                            borderRadius: 4,
                            borderSkipped: false,
                        },
                        {
                            label: 'Curatives',
                            data: <?= json_encode($curativeData) ?>,
                            backgroundColor: 'rgba(246, 194, 62, 0.8)',
                            borderColor: 'rgba(246, 194, 62, 1)',
                            borderWidth: 2,
                            borderRadius: 4,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6e707e',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(234, 236, 244, 0.5)',
                                borderDash: [5, 5]
                            },
                            ticks: {
                                precision: 0,
                                color: '#6e707e',
                                callback: function(value) {
                                    return value + ' int.';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: "rgba(255,255,255,0.95)",
                            bodyColor: "#858796",
                            titleColor: "#6e707e",
                            titleMarginBottom: 10,
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            caretPadding: 10,
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                title: function(context) {
                                    return 'Mois de ' + context[0].label;
                                },
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + ' interventions';
                                }
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
            console.log(<?= json_encode(array_column($machinesByStatus, 'status_name')) ?>);
            console.log(<?= json_encode(array_column($machinesByStatus, 'count')) ?>);
            // Graphique de répartition des machines par statut
            var ctxPie = document.getElementById('machineStatusChart').getContext('2d');
            var machineStatusChart = new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($machinesByStatus, 'status_name')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($machinesByStatus, 'count')) ?>,
                        backgroundColor: [
                            'rgba(28, 200, 138, 0.8)', // Active - Vert
                            'rgba(108, 117, 125, 0.8)', // Inactive - Gris
                            'rgba(231, 74, 59, 0.8)', // En panne - Rouge
                            'rgba(220, 53, 69, 0.8)', // Ferraille - Rouge foncé
                            'rgba(102, 126, 234, 0.8)', // Fonctionnelle - Bleu
                            'rgba(173, 181, 189, 0.8)' // Non défini - Gris clair
                        ],
                        borderColor: [
                            'rgba(28, 200, 138, 1)',
                            'rgba(108, 117, 125, 1)',
                            'rgba(231, 74, 59, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(102, 126, 234, 1)',
                            'rgba(173, 181, 189, 1)'
                        ],
                        borderWidth: 2,
                        hoverBackgroundColor: [
                            'rgba(28, 200, 138, 1)',
                            'rgba(108, 117, 125, 1)',
                            'rgba(231, 74, 59, 1)',
                            'rgba(220, 53, 69, 1)',
                            'rgba(102, 126, 234, 1)',
                            'rgba(173, 181, 189, 1)'
                        ],
                        hoverBorderWidth: 3,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const dataset = data.datasets[0];
                                            const value = dataset.data[i];
                                            const total = dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);

                                            return {
                                                text: `${label} (${value} - ${percentage}%)`,
                                                fillStyle: dataset.backgroundColor[i],
                                                strokeStyle: dataset.borderColor[i],
                                                pointStyle: 'circle',
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: "rgba(255,255,255,0.95)",
                            bodyColor: "#858796",
                            titleColor: "#6e707e",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            caretPadding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} machines (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '65%',
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });

            // Graphique de répartition des équipements par statut
            var ctxEquipmentStatus = document.getElementById('equipmentStatusChart').getContext('2d');
            var equipmentStatusChart = new Chart(ctxEquipmentStatus, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($equipmentsByStatus, 'status_category')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($equipmentsByStatus, 'count')) ?>,
                        backgroundColor: [
                            'rgba(28, 200, 138, 0.8)', // Fonctionnel - Vert
                            'rgba(231, 74, 59, 0.8)', // Non fonctionnel - Rouge
                            'rgba(108, 117, 125, 0.8)' // Non défini - Gris
                        ],
                        borderColor: [
                            'rgba(28, 200, 138, 1)',
                            'rgba(231, 74, 59, 1)',
                            'rgba(108, 117, 125, 1)'
                        ],
                        borderWidth: 2,
                        hoverBackgroundColor: [
                            'rgba(28, 200, 138, 1)',
                            'rgba(231, 74, 59, 1)',
                            'rgba(108, 117, 125, 1)'
                        ],
                        hoverBorderWidth: 3,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const dataset = data.datasets[0];
                                            const value = dataset.data[i];
                                            const total = dataset.data.reduce((a, b) => a + b, 0);
                                            const percentage = ((value / total) * 100).toFixed(1);

                                            return {
                                                text: `${label} (${value} - ${percentage}%)`,
                                                fillStyle: dataset.backgroundColor[i],
                                                strokeStyle: dataset.borderColor[i],
                                                pointStyle: 'circle',
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: "rgba(255,255,255,0.95)",
                            bodyColor: "#858796",
                            titleColor: "#6e707e",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            caretPadding: 10,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${value} équipements (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '65%',
                    animation: {
                        animateRotate: true,
                        animateScale: true,
                        duration: 1000,
                        easing: 'easeOutQuart'
                    }
                }
            });
        </script>
    </div>
</body>

</html>