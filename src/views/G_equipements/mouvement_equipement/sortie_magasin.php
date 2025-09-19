<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Controllers\Mouvement_equipmentController;

// Vérifie si l'utilisateur est un administrateur
$isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';


?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Mouvement Sortie de magasin</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/mouvementMachines.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
</head>

<body id="page-top">
    <div id="wrapper">
        <?php include(__DIR__ . "/../../../views/layout/sidebar.php") ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include(__DIR__ . "/../../../views/layout/navbar.php"); ?>
                <div class="container-fluid">


                    <?php if (!empty($_SESSION['flash_message'])): ?>
                        <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                            <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Mouvement équipements : Sortie magasin</h6>
                            <div class="d-flex">
                                <button type="button" class="btn btn-primary mr-2" data-toggle="modal" data-target="#mouvementModal">
                                    <i class="fas fa-plus"></i> mouvement
                                </button>
                                <a href="#" class="btn btn-warning d-flex align-items-center">
                                    <i class="fas fa-bell mr-1"></i> Réception
                                    <?php
                                    // Afficher le nombre de machines en attente de réception
                                    $db = new App\Models\Database();
                                    $conn = $db->getConnection();
                                    $stmt = $conn->query("SELECT COUNT(*) FROM gmao__mouvement_equipment WHERE type_Mouv = 'sortie_magasin' AND idEmp_accepted IS NULL");
                                    $count = $stmt->fetchColumn();
                                    if ($count > 0) {
                                        echo '<span class="badge badge-danger ml-1">' . $count . '</span>';
                                    }
                                    ?>
                                </a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Equipement ID</th>
                                            <th>Référence</th>
                                            <!-- <th>Machine ID</th> -->
                                            <th>Raisons</th>
                                            <th>Date mouvement</th>
                                            <th>Initiateur</th>
                                            <th>Réceptionneur </th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($mouvements) && count($mouvements) > 0): ?>
                                            <?php foreach ($mouvements as $mouvement): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($mouvement['equipment_id']  ?? '') ?></td>

                                                    <td><?= htmlspecialchars($mouvement['equipment_reference'] ?? '') ?></td>
                                                    <!-- <td><?= htmlspecialchars($mouvement['machine_id'] . '-' . $mouvement['machine_reference'] ?? '') ?></td> -->
                                                    <td><?= htmlspecialchars($mouvement['raison_mouv'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($mouvement['date_mouvement'] ?? '') ?></td>
                                                    <td>
                                                        <?php
                                                        if (!empty($mouvement['idEmp_moved'])) {
                                                            echo htmlspecialchars($mouvement['emp_initiator_name'] ?? $mouvement['idEmp_moved']);
                                                        } else {
                                                            echo '<span class="text-muted">Non défini</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if (!empty($mouvement['idEmp_accepted'])) {
                                                            echo htmlspecialchars($mouvement['emp_acceptor_name'] ?? $mouvement['idEmp_accepted']);
                                                        } else {
                                                            echo '<span class="badge badge-warning">En attente</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if (empty($mouvement['idEmp_accepted'])): ?>
                                                            <button class="btn btn-success btn-sm reception-btn" data-toggle="modal" data-target="#receptionModal"
                                                                data-id="<?= htmlspecialchars($mouvement['id'] ?? '') ?>"
                                                                data-equipment-id="<?= htmlspecialchars($mouvement['equipment_id'] ?? '') ?>">
                                                                <i class="fas fa-check"></i> Réceptionner
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>

                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include(__DIR__ . "/../../../views/layout/footer.php"); ?>

        </div>

        <!-- Modal pour ajouter un nouveau mouvement -->
        <div class="modal fade" id="mouvementModal" tabindex="-1" role="dialog" aria-labelledby="mouvementModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mouvementModalLabel">
                            <i class="fas fa-exchange-alt"></i> Mouvement Equipement
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="mouvementForm" action="../../platform_gmao/public/index.php?route=mouvement_equipements/saveMouvement" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="type_mouvement" value="sortie_magasin">
                            <div class="form-group">
                                <label for="equipment">Equipement ID :</label>
                                <select class="form-control" id="equipment" name="equipment" required>
                                    <option value="">--Sélectionnez un équipement--</option>
                                    <?php
                                    $controller = new Mouvement_equipmentController();
                                    $location = "magasin";
                                    $equipements = $controller->getEquipements($location);

                                    foreach ($equipements as $equipement) {
                                        echo "<option value=\"{$equipement['id']}\">{$equipement['equipment_id']}-{$equipement['reference']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- <div class="form-group">
                                <label for="machine">Machine :</label>
                                <select class="form-control" id="machine" name="machine" required>
                                    <option value="">--Sélectionnez une machine--</option>
                                    <?php
                                    $controller = new Mouvement_equipmentController();
                                    $machines = $controller->getMachines();
                                    foreach ($machines as $machine) {
                                        echo "<option value=\"{$machine['id']}\">{$machine['machine_id']}-{$machine['reference']}</option>";
                                    }
                                    ?>
                                </select>
                            </div> -->
                            <div class="form-group">
                                <label for="maintenancier">Maintenancier :</label>
                                <select class="form-control" id="maintenancier" name="maintenancier" required>
                                    <option value="">--Maintenancier--</option>
                                    <?php
                                    // Charger les maintenanciers ici
                                    $maintenanciers = []; // Remplacer par les données réelles
                                    if (isset($controller)) {
                                        $maintenanciers = $controller->getMaintainers();
                                    }
                                    foreach ($maintenanciers as $maintenancier) {
                                        echo "<option value=\"{$maintenancier['id']}\">{$maintenancier['first_name']} {$maintenancier['last_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="raisonMouvement">Raison Mouvement Machine :</label>
                                <select class="form-control" id="raisonMouvement" name="raisonMouvement" required>
                                    <option value="">--Raison Mouvement Machine--</option>
                                    <?php
                                    // Charger les raisons de mouvement ici
                                    $raisons = []; // Remplacer par les données réelles
                                    if (isset($controller)) {
                                        $raisons = $controller->getRaisons();
                                    }
                                    foreach ($raisons as $raison) {
                                        echo "<option value=\"{$raison['id_Raison']}\">{$raison['raison_mouv_mach']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Enregistrer</button>

                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal pour la réception -->
        <div class="modal fade" id="receptionModal" tabindex="-1" role="dialog" aria-labelledby="receptionModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="receptionModalLabel">
                            <i class="fas fa-check-circle"></i> Réception d'équipement
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="receptionForm" action="../../platform_gmao/public/index.php?route=mouvement_equipements/accept" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="mouvement_id" id="mouvement_id" value="">
                            <input type="hidden" name="equipment_id" id="equipment_id" value="">
                            <input type="hidden" name="type_mouvement" value="sortie_magasin">

                            <div class="form-group">
                                <label for="recepteur"> Sélectionner un maintenancier :</label>
                                <select class="form-control" id="recepteur" name="recepteur">
                                    <option value="">--Sélectionner un maintenancier--</option>
                                    <?php
                                    $controller = new Mouvement_equipmentController();
                                    // Récupérer les maintenanciers depuis la base de données
                                    $maintainers = $controller->getMaintainers();

                                    if (!empty($maintainers)) {
                                        foreach ($maintainers as $maintainer) {
                                            echo "<option value=\"{$maintainer['id']}\">{$maintainer['first_name']} {$maintainer['last_name']}</option>";
                                        }
                                    } else {
                                        echo "<option value=\"\" disabled>Aucun maintenancier trouvé</option>";
                                    }
                                    ?>
                                </select>
                                <div class="form-group">
                                    <label for="etat_equipement">Etat de l'équipement :</label>
                                    <select class="form-control" id="etat_equipement" name="etat_equipement" required>
                                        <option value="">--Etat de l'Equipement--</option>
                                        <?php
                                        // Charger les raisons de mouvement ici
                                        $etat_equipement = []; // Remplacer par les données réelles
                                        if (isset($controller)) {
                                            $etat_equipement = $controller->getEquipementStatus();
                                        }
                                        foreach ($etat_equipement as $etat) {
                                            if ($etat['status_name'] === 'fonctionnelle' || $etat['status_name'] === 'non fonctionnelle') {
                                                echo "<option value=\"{$etat['id']}\">{$etat['status_name']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="text-right mt-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Confirmer avec ce maintenancier
                                    </button>
                                </div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts JavaScript -->
    <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
    <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
    <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
    <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
    <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
    <script src="/platform_gmao/public/js/sideBare.js"></script>

    <script>
        // Document ready function
        $(document).ready(function() {

            var table = $('#dataTable').DataTable({
                language: {
                    search: "Rechercher:",
                    lengthMenu: "Afficher _MENU_ éléments par page",
                    info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
                    infoEmpty: "Aucun élément à afficher",
                    infoFiltered: "(filtré de _MAX_ éléments au total)",
                    zeroRecords: "Aucun enregistrement correspondant trouvé",
                    paginate: {
                        first: "Premier",
                        previous: "Précédent",
                        next: "Suivant",
                        last: "Dernier"
                    }
                },
                pageLength: 10,
                order: [
                    [0, 'asc']
                ]
            });



            // Faire disparaître les messages flash après 3 secondes
            setTimeout(function() {
                $("#flash-message").fadeOut("slow");
            }, 4000);
            console.log("avon changement");

            // Mettre à jour l'ID du mouvement dans le modal de réception
            $('.reception-btn').click(function() {
                var mouvementId = $(this).data('id');
                var equipmentId = $(this).data('equipment-id');
                $('#mouvement_id').val(mouvementId);
                $('#equipment_id').val(equipmentId);
            });
        });
    </script>
</body>

</html>