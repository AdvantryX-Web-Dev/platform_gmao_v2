<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Models\Maintainer_model;
use App\Models\Equipement_model;
use App\Models\Machine_model;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Affectation équipement-Machine</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/init_Machine.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/select2.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../views/layout/navbar.php"); ?>
                <div class="container-fluid">
                    <button class="btn btn-primary mb-4" id="sidebarTo"><i class="fas fa-bars"></i></button>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Affectation équipement-Machine</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($_SESSION['equipement_success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show">
                                    <?= $_SESSION['equipement_success'];
                                    unset($_SESSION['equipement_success']); ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($_SESSION['equipement_error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <?= $_SESSION['equipement_error'];
                                    unset($_SESSION['equipement_error']); ?>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            <?php endif; ?>
                            <form method="post" action="/platform_gmao/public/index.php?route=equipement_machine/affecter">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="equipment_id">Equipement</label>
                                        <select name="equipment_id" id="equipment_id" class="form-control select2" required>
                                            <option value="">Sélectionnez un équipement</option>
                                            <?php
                                            $equipements = Equipement_model::findAll();
                                            foreach ($equipements as $equipement) {
                                                echo '<option value="' . htmlspecialchars($equipement['equipment_id']) . '">' .
                                                    htmlspecialchars($equipement['equipment_id'] . ' - ' . $equipement['designation']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="machine_id">Machine</label>
                                        <select name="machine_id" id="machine_id" class="form-control select2" required>
                                            <option value="">Sélectionnez une machine</option>
                                            <?php
                                            $machines = Machine_model::findAll();
                                            foreach ($machines as $machine) {
                                                echo '<option value="' . htmlspecialchars($machine['machine_id']) . '">' .
                                                    htmlspecialchars($machine['machine_id'] . ' - ' . $machine['designation']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="maintainer">Maintenicien</label>
                                        <select name="maintainer" id="maintainer" class="form-control select2" required>
                                            <option value="">Sélectionnez un maintenicien</option>
                                            <?php
                                            $maintainers = Maintainer_model::findAll();
                                            foreach ($maintainers as $maintainer) {
                                                echo '<option value="' . $maintainer['matricule'] . '">' .
                                                    htmlspecialchars($maintainer['first_name'] . ' ' . $maintainer['last_name']) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="mvt_state">Etat du mouvement</label>
                                        <select name="mvt_state" id="mvt_state" class="form-control" required>
                                            <option value="SS" selected>SS</option>
                                            <!-- <option value="ES">ES</option>
                                            <option value="MS">MS</option> -->
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="allocation_time">Heure d'affectation</label>
                                        <input type="time" name="allocation_time" id="allocation_time" class="form-control" value="<?= date('H:i') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="allocation_date">Date d'affectation</label>
                                        <input type="date" name="allocation_date" id="allocation_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success">Ajouter</button>
                                    <a href="/platform_gmao/public/index.php?route=equipement_machine" class="btn btn-secondary ml-2">Annuler</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../../views/layout/footer.php"); ?>

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
        <script src="/platform_gmao/public/js/select2.min.js"></script>
        <script>
            $(document).ready(function() {
                // Initialiser les alertes
                setTimeout(function() {
                    $(".alert").fadeOut("slow");
                }, 3000);

                // Initialiser Select2
                $('.select2').select2({
                    placeholder: "Rechercher...",
                    allowClear: true,
                    width: '100%'
                });
            });
        </script>
    </div>
</body>

</html>