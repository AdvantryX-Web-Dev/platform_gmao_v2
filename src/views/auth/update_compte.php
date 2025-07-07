<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// $compte doit être fourni par le contrôleur (email, matricule)
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier mon compte</title>
    <link rel="icon" type="image/x-icon" href="/public/images/favicon.png" />
    <link rel="stylesheet" href="/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/public/css/table.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column ">
            <div id="content">
                <?php include(__DIR__ . "/../layout/navbar.php"); ?>
                <div class="container-fluid">
                    <?php if (!empty($_SESSION['compte_update_success'])): ?>
                        <div class="alert alert-success"> <?= $_SESSION['compte_update_success']; unset($_SESSION['compte_update_success']); ?> </div>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['compte_update_error'])): ?>
                        <div class="alert alert-danger"> <?= $_SESSION['compte_update_error']; unset($_SESSION['compte_update_error']); ?> </div>
                    <?php endif; ?>
                    <div class="card shadow mb-4 mt-5" >
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Modifier mon compte</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label>Matricule</label>
                                    <input type="text" name="matricule" class="form-control" value="<?= htmlspecialchars($compte['matricule'] ?? '') ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($compte['email'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Nouveau mot de passe</label>
                                    <div class="input-group">
                                        <input type="password" name="motDePasse" id="motDePasse" class="form-control" placeholder="Nouveau mot de passe" required>
                                        <div class="input-group-append">
                                            <span class="input-group-text" id="togglePassword" style="cursor:pointer;">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <button type="submit" class="btn btn-primary mr-2">Enregistrer</button>
                                    <a href="../../public/index.php?route=dashboard" class="btn btn-secondary">Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../layout/footer.php"); ?>
        </div>
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>
        <script src="/public/js/jquery-3.6.4.min.js"></script>
        <script src="/public/js/bootstrap.bundle.min.js"></script>
        <script src="/public/js/sb-admin-2.min.js"></script>
        <script>
            // Masquer les alertes après 3 secondes
            $(document).ready(function() {
                setTimeout(function() {
                    $(".alert").fadeOut("slow");
                }, 3000);
                // Afficher/masquer le mot de passe
                $('#togglePassword').on('click', function() {
                    const input = $('#motDePasse');
                    const icon = $(this).find('i');
                    if (input.attr('type') === 'password') {
                        input.attr('type', 'text');
                        icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    } else {
                        input.attr('type', 'password');
                        icon.removeClass('fa-eye-slash').addClass('fa-eye');
                    }
                });
            });
        </script>
    </div>
</body>
</html> 