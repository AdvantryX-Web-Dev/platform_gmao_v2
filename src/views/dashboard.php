<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
use App\Models\Machine_model;
// Ajoute ici les autres modèles nécessaires pour les stats (Intervention, Equipement, etc.)
// Exemple fictif pour les stats :
$totalMachines = is_array(Machine_model::findAll()) ? count(Machine_model::findAll()) : 0;
$totalPrev = 12; // À remplacer par une vraie requête
$totalCur = 7;   // À remplacer par une vraie requête
$totalEquip = 25; // À remplacer par une vraie requête
$dernieresInterventions = [
    ['machine_id' => 'ATE00123', 'type' => 'Préventive', 'date' => '2024-07-10', 'statut' => 'Terminée'],
    ['machine_id' => 'ATE00145', 'type' => 'Curative', 'date' => '2024-07-09', 'statut' => 'En cours'],
    ['machine_id' => 'ATE00117', 'type' => 'Préventive', 'date' => '2024-07-08', 'statut' => 'Terminée'],
];
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
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/layout/navbar.php"); ?>
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow"></nav>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>
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
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Interventions Préventives</div>
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
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Interventions Curatives</div>
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
                                            <th>Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dernieresInterventions as $interv): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($interv['machine_id']) ?></td>
                                                <td><?= htmlspecialchars($interv['type']) ?></td>
                                                <td><?= htmlspecialchars($interv['date']) ?></td>
                                                <td><?= htmlspecialchars($interv['statut']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
    </div>
</body>
</html>
