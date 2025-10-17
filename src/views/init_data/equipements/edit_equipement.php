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
    <title>Editer un équipement</title>
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
                            <h6 class="m-0 font-weight-bold text-primary">Editer un equipement</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($_SESSION['flash_message'])): ?>
                                <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                                    <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                                </div>
                                <?php unset($_SESSION['flash_message']); ?>
                            <?php endif; ?>
                            <form method="post" action="/platform_gmao/public/index.php?route=equipement/edit&id=<?= urlencode($equipement['id']) ?>">

                                <div class="form-group">
                                    <label>Equipement ID</label>
                                    <input type="text" name="equipment_id" class="form-control" value="<?= htmlspecialchars($equipement['equipment_id'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Référence</label>
                                    <input type="text" name="reference" class="form-control" value="<?= htmlspecialchars($equipement['reference'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Designation</label>
                                    <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($equipement['designation'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Catégorie</label>
                                    <select name="equipment_category" class="form-control" value="<?= htmlspecialchars($equipement['equipment_category'] ?? '') ?>" required>
                                        <?php
                                        $categories = Equipement_model::AllCategorie();
                                        foreach ($categories as $category) {
                                            echo '<option value="' . $category['id'] . '" ' . (isset($equipement['equipment_category_id']) && $equipement['equipment_category_id'] == $category['id'] ? 'selected' : '') . '>' . $category['category_name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Localisation</label>
                                    <select name="location_id" class="form-control" value="<?= htmlspecialchars($equipement['location_id'] ?? '') ?>" required>
                                        <?php
                                        $locations = Equipement_model::AllLocations();
                                        foreach ($locations as $location) {
                                            if ($location['location_category'] === 'prodline' || $location['location_category'] === 'magasin') {
                                                echo '<option value="' . $location['id'] . '" ' . (isset($equipement['location_id']) && $equipement['location_id'] == $location['id'] ? 'selected' : '') . '>' . $location['location_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Statut</label>
                                    <select name="status_id" class="form-control" value="<?= htmlspecialchars($equipement['status_id'] ?? '') ?>" required>
                                        <?php
                                        $status = Equipement_model::AllStatus();
                                        foreach ($status as $status) {
                                            if ($status['status_name'] === 'disponible' || $status['status_name'] === 'implanté' || $status['status_name'] === 'ferraille') {
                                                echo '<option value="' . $status['id'] . '" ' . (isset($equipement['status_id']) && $equipement['status_id'] == $status['id'] ? 'selected' : '') . '>' . $status['status_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success">Modifier</button>
                                <a href="/platform_gmao/public/index.php?route=equipement/list" class="btn btn-secondary ml-2">Annuler</a>
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