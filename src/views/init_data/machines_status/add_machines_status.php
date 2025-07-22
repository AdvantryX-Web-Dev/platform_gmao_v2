<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Ajouter un statut de machine</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/public/css/table.css">
    <link rel="stylesheet" href="/public/css/init_Machine.css">
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
                            <h6 class="m-0 font-weight-bold text-primary">Ajouter un statut de machine</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($_SESSION['maintainer_success'])): ?>
                                <div class="alert alert-success"><?= $_SESSION['maintainer_success'];
                                                                    unset($_SESSION['maintainer_success']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['maintainer_error'])): ?>
                                <div class="alert alert-danger"><?= $_SESSION['maintainer_error'];
                                                                unset($_SESSION['maintainer_error']); ?></div>
                            <?php endif; ?>
                            <form method="post" action="">
                                <div class="form-group">
                                    <label>Statut de machine</label>
                                    <input type="text" name="status_name" class="form-control" required>
                                </div>

                                <button type="submit" class="btn btn-success">Ajouter</button>
                                <a href="/public/index.php?route=machines_status/list" class="btn btn-secondary ml-2">Annuler</a>

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
        <script src="/public/js/jquery-3.6.4.min.js"></script>
        <script src="/public/js/jquery.dataTables.min.js"></script>
        <script src="/public/js/sideBare.js"></script>
        <script src="/public/js/bootstrap.bundle.min.js"></script>
        <script src="/public/js/sb-admin-2.min.js"></script>
        <script src="/public/js/dataTables.bootstrap4.min.js"></script>
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