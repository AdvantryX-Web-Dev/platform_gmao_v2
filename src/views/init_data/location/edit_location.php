<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Editer un emplacement</title>
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
                            <h6 class="m-0 font-weight-bold text-primary">Editer un emplacement </h6>
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
                                    <label>Nom </label>
                                    <input type="text" name="location_name" class="form-control" value="<?= htmlspecialchars($location['location_name'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Emplacement</label>
                                        <select name="location_category" class="form-control" required>
                                            <option value="magasin" <?= (isset($location['location_category']) && $location['location_category'] === 'magasin') ? 'selected' : '' ?>>magasin</option>
                                            <option value="prodline" <?= (isset($location['location_category']) && $location['location_category'] === 'prodline') ? 'selected' : '' ?>>prodline</option>
                                            <option value="parc" <?= (isset($location['location_category']) && $location['location_category'] === 'parc') ? 'selected' : '' ?>>parc</option>
                                        </select>
                                </div>
                                <!-- <div class="form-group">
                                    <label>Type</label>
                                    <select name="location_type" class="form-control" required>
                                        <option value="machine" <?= (isset($location['location_type']) && $location['location_type'] === 'machine') ? 'selected' : '' ?>>machine</option>
                                        <option value="equipment" <?= (isset($location['location_type']) && $location['location_type'] === 'equipment') ? 'selected' : '' ?>>equipment</option>
                                    </select>
                                </div> -->
                                <button type="submit" class="btn btn-success">Enregistrer</button>
                                <a href="/platform_gmao/public/index.php?route=location/list" class="btn btn-secondary ml-2">Annuler</a>
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
                if (el) { el.style.display = 'none'; }
            }, 4000);
        </script>
    </div>
</body>

</html>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Editer une catégorie</title>
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
                            <h6 class="m-0 font-weight-bold text-primary">Editer un motif</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($_SESSION['flash_message'])): ?>
                                <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                                    <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                                </div>
                                <?php unset($_SESSION['flash_message']); ?>
                            <?php endif; ?>
                            <form method="post" action="../../platform_gmao/public/index.php?route=category/edit&id=<?= urlencode($category['id_Raison']) ?>">
                                <div class="form-group">
                                    <label>Motif</label>
                                    <input type="text" name="raison_mouv_mach" class="form-control" value="<?= htmlspecialchars($category['raison_mouv_mach'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Code</label>
                                    <input type="text" name="typeR" class="form-control" value="<?= htmlspecialchars($category['typeR'] ?? '') ?>">
                                </div>



                                <button type="submit" class="btn btn-success">Enregistrer</button>
                                <a href="../../platform_gmao/public/index.php?route=categories" class="btn btn-secondary ml-2">Annuler</a>
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
                if (el) { el.style.display = 'none'; }
            }, 4000);
        </script>
    </div>
</body>

</html>