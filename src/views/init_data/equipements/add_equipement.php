<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Models\Categories_model;
use App\Models\Equipement_model;

?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Ajouter un équipement</title>
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
                            <h6 class="m-0 font-weight-bold text-primary">Ajouter un équipement</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($_SESSION['flash_message'])): ?>
                                <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                                    <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                                </div>
                                <?php unset($_SESSION['flash_message']); ?>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['equipement_success'])): ?>
                                <div class="alert alert-success"><?= $_SESSION['equipement_success'];
                                                                    unset($_SESSION['equipement_success']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['equipement_error'])): ?>
                                <div class="alert alert-danger"><?= $_SESSION['equipement_error'];
                                                                unset($_SESSION['equipement_error']); ?></div>

                            <?php endif; ?>
                            <form method="post" action="">
                                <div class="form-group">
                                    <label>Equipement ID</label>
                                    <input type="text" name="equipment_id" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Référence</label>
                                    <input type="text" name="reference" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Désignation</label>
                                    <input type="text" name="designation" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Catégorie</label>
                                    <select name="equipment_category" class="form-control" required>
                                        <?php
                                        $categories = Equipement_model::AllCategorie();
                                        foreach ($categories as $category) {
                                            echo '<option value="' . $category['id'] . '">' . $category['category_name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Localisation</label>
                                    <select name="location_id" class="form-control" required>
                                        <?php
                                        $locations = Equipement_model::AllLocations();
                                        foreach ($locations as $location) {
                                            if ($location['location_category'] === 'prodline' || $location['location_category'] === 'magasin') {
                                                echo '<option value="' . $location['id'] . '">' . $location['location_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Statut</label>
                                    <select name="status_id" class="form-control" required>
                                        <?php
                                        $statuses = Equipement_model::AllStatus();
                                        foreach ($statuses as $status) {
                                            if ($status['status_name'] === 'disponible' || $status['status_name'] === 'non disponible'|| $status['status_name'] === 'ferraille') {
                                                echo '<option value="' . $status['id'] . '">' . $status['status_name'] . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success">Ajouter</button>
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
            $(document).ready(function() {
                setTimeout(function() {
                    $(".alert").fadeOut("slow");
                }, 3000);
            });
        </script>
    </div>
</body>

</html>