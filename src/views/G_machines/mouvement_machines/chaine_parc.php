<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Controllers\Mouvement_machinesController;

// Vérifie si l'utilisateur est un administrateur
$isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';

// Si $mouvements n'est pas défini dans le contrôleur, créez une instance et récupérez les données
if (!isset($mouvements)) {
    $controller = new Mouvement_machinesController();
    $mouvements = $controller->getMouvementsByType('chaine_parc');
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Mouvement Entrée au parc</title>
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
                    <button class="btn btn-primary" id="sidebarTo"><i class="fas fa-bars"></i></button>

                    <?php if (!empty($_SESSION['flash_message'])): ?>
                        <div id="flash-message" class="alert alert-<?= $_SESSION['flash_message']['type'] === 'success' ? 'success' : 'danger' ?> mb-4">
                            <?= htmlspecialchars($_SESSION['flash_message']['text']) ?>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Mouvement des machines Entrée au parc :</h6>
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
                                    $stmt = $conn->query("SELECT COUNT(*) FROM gmao__mouvement_machine WHERE type_Mouv = 'chaine_parc' AND idEmp_accepted IS NULL");
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
                                            <th>Machine ID</th>
                                            <th>Référence</th>
                                            <th>Désignation</th>
                                            <th>Raisons</th>
                                            <th>Date mouvement</th>
                                            <th>Initiateur</th>
                                            <th>Réceptionneur</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($mouvements) && count($mouvements) > 0): ?>
                                            <?php foreach ($mouvements as $mouvement): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($mouvement['id_machine'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($mouvement['reference'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($mouvement['designation'] ?? '') ?></td>
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
                                                                data-id="<?= htmlspecialchars($mouvement['num_Mouv_Mach'] ?? '') ?>"
                                                                data-machine-id="<?= htmlspecialchars($mouvement['id_machine'] ?? '') ?>">
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
                            <i class="fas fa-exchange-alt"></i> Mouvement Machine
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="mouvementForm" action="../../platform_gmao/public/index.php?route=mouvement_machines/saveMouvement" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="type_mouvement" value="chaine_parc">
                            <div class="form-group">
                                <label for="typeMachine">Type de Machine :</label>
                                <select class="form-control" id="typeMachine" name="typeMachine" required>
                                    <option value="">--Type de Machine--</option>
                                    <?php
                                    // Charger les types de machines ici
                                    $controller = new Mouvement_machinesController();
                                    $location = "prodline";
                                    $types = $controller->getTypes($location);
                                    foreach ($types as $type) {
                                        echo "<option value=\"{$type['type']}\">{$type['type']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="machine">Machine :</label>
                                <select class="form-control" id="machine" name="machine" required>
                                    <option value="">--Sélectionnez une machine--</option>

                                </select>
                            </div>
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
                            <i class="fas fa-check-circle"></i> Réception de Machine
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="receptionForm" action="../../platform_gmao/public/index.php?route=mouvement_machines/accept" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="mouvement_id" id="mouvement_id" value="">
                            <input type="hidden" name="machine_id" id="machine_id" value="">
                            <input type="hidden" name="type_mouvement" value="chaine_parc">

                            <div class="form-group">
                                <label for="recepteur"> sélectionner un maintenancier :</label>
                                <select class="form-control" id="recepteur" name="recepteur">
                                    <option value="">--Sélectionner un maintenancier--</option>
                                    <?php
                                    $controller = new Mouvement_machinesController();
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
                                    <label for="etat_machine">Etat de la Machine :</label>
                                    <select class="form-control" id="etat_machine" name="etat_machine" required>
                                        <option value="">--Etat de la Machine--</option>
                                        <?php
                                        // Charger les raisons de mouvement ici
                                        $etat_machine = []; // Remplacer par les données réelles
                                        if (isset($controller)) {
                                            $etat_machine = $controller->getMachineStatus();
                                        }
                                        foreach ($etat_machine as $etat) {
                                            echo "<option value=\"{$etat['id']}\">{$etat['status_name']}</option>";
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

            // Gestion des sélections dans le modal
            $('#typeMachine').change(function() {
                var location = "prodline";
                var machineType = $(this).val();
                console.log(machineType);
                if (machineType) {
                    // Récupérer les machines du type sélectionné
                    $.ajax({
                        url: '../../platform_gmao/public/index.php?route=mouvement_machines/getMachinesByType',
                        type: 'GET',
                        data: {
                            type: machineType,
                            location: location
                        },
                        dataType: 'json',
                        success: function(data) {
                            console.log(data);
                            var options = '<option value="">--Sélectionnez une machine--</option>';
                            $.each(data, function(index, machine) {
                                options += '<option value="' + machine.machine_id + '">' + machine.machine_id + ' </option>';
                            });
                            $('#machine').html(options);
                        },
                        error: function() {
                            alert('Une erreur est survenue lors de la récupération des machines.');
                        }
                    });
                } else {
                    $('#machine').html('<option value="">--Sélectionnez une machine--</option>');
                }
            });

            // Mettre à jour l'ID du mouvement dans le modal de réception
            $('.reception-btn').click(function() {
                var mouvementId = $(this).data('id');
                var machineId = $(this).data('machine-id');
                $('#mouvement_id').val(mouvementId);
                $('#machine_id').val(machineId);
            });
        });
    </script>
</body>

</html>