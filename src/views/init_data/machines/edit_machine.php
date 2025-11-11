<?php

use App\Models\Equipement_model;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Éditer une machine</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/init_Machine.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow"></nav>
                <div class="container-fluid">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Editer une machine</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($_SESSION['flash_message'])): ?>
                                <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                                    <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                                </div>
                                <?php unset($_SESSION['flash_message']); ?>
                            <?php endif; ?>
                            <form method="post" action="">
                                <div class="form-group">
                                    <label>Machine ID</label>
                                    <input type="text" name="machine_id" class="form-control" value="<?= htmlspecialchars($machine['machine_id'] ?? '') ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Référence</label>
                                    <input type="text" name="reference" class="form-control" value="<?= htmlspecialchars($machine['reference'] ?? '') ?>" maxlength="16">
                                </div>
                                <div class="form-group">
                                    <label>Marque</label>
                                    <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($machine['brand'] ?? '') ?>" maxlength="24">
                                </div>
                                <div class="form-group">
                                    <label>Type</label>
                                    <input type="text" name="type" class="form-control" value="<?= htmlspecialchars($machine['type'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Désignation</label>
                                    <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($machine['designation'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Numéro Facture</label>
                                    <input type="text" name="billing_num" class="form-control" value="<?= htmlspecialchars($machine['billing_num'] ?? '') ?>" maxlength="10">
                                </div>
                                <div class="form-group">
                                    <label>Date Facture</label>
                                    <input type="date" name="bill_date" class="form-control" value="<?= htmlspecialchars($machine['bill_date'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Prix</label>
                                    <input type="text" name="price" class="form-control" value="<?= htmlspecialchars($machine['price'] ?? '') ?>" maxlength="11">
                                </div>
                                <div class="form-group">
                                    <label>Localisation</label>
                                    <select name="location_id" class="form-control" value="<?= htmlspecialchars($equipement['location_id'] ?? '') ?>" required>
                                        <?php
                                        $locations = Equipement_model::AllLocations();
                                        foreach ($locations as $location) {
                                            if ($location['location_category'] === 'prodline' || $location['location_category'] === 'parc') {
                                                echo '<option value="' . $location['id'] . '" ' . (isset($machine['machines_location_id']) && $machine['machines_location_id'] == $location['id'] ? 'selected' : '') . '>' . $location['location_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Status</label>

                                    <select name="status_id" class="form-control" value="<?= htmlspecialchars($ma['status_id'] ?? '') ?>" required>
                                        <?php
                                        $status = Equipement_model::AllStatus();
                                        foreach ($status as $status) {
                                            if (($status['status_name'] == 'active') || ($status['status_name'] == 'inactive') || ($status['status_name'] == 'ferraille') || ($status['status_name'] == 'en panne')) {
                                                echo '<option value="' . $status['id'] . '" ' . (isset($machine['machines_status_id']) && $machine['machines_status_id'] == $status['id'] ? 'selected' : '') . '>' . $status['status_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">Enregistrer</button>
                                <a href="../../platform_gmao/public/index.php?route=machines" class="btn btn-secondary ml-2">Annuler</a>
                            </form>
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
            setTimeout(function() {
                var el = document.getElementById('flash-message');
                if (el) {
                    el.style.display = 'none';
                }
            }, 4000);
        </script>
    </div>
</body>

</html>