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
    <style>
        .equipment-list-container {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6 !important;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .equipment-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .equipment-item {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }

        .equipment-item:hover {
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }

        .equipment-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .equipment-details {
            flex: 1;
            min-width: 0;
        }

        .equipment-ref {
            color: #495057;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .equipment-designation {
            color: #6c757d;
            font-size: 12px;
            margin-bottom: 2px;
        }

        .equipment-reference {
            font-size: 11px;
        }

        .loading-equipment {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }

        .loading-equipment i {
            margin-right: 8px;
            color: #007bff;
        }

        .no-equipment {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
        }

        .no-equipment i {
            margin-right: 8px;
            color: #ffc107;
        }

        .error-equipment {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc3545;
            font-style: italic;
            padding: 20px;
            text-align: center;
        }

        .error-equipment i {
            margin-right: 8px;
        }

        .equipment-list-container::-webkit-scrollbar {
            width: 6px;
        }

        .equipment-list-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .equipment-list-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .equipment-list-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Minimal modern action icons: small, no background; show label and tint on hover */
        .action-icon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 6px;
            border-radius: 8px;
            background: transparent;
            color: #6c757d;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color .15s ease, color .15s ease, border-color .15s ease, transform .15s ease;
            margin-right: 6px;
        }
        .action-icon i { font-size: 12px; }
        .action-icon .label { display: none; font-size: 12px; font-weight: 500; }
        .action-icon:hover .label { display: inline; }
        .action-icon.accept:hover { background: rgba(40, 167, 69, 0.08); color: #28a745; border-color: rgba(40, 167, 69, 0.25); }
        .action-icon.reject:hover { background: rgba(220, 53, 69, 0.08); color: #dc3545; border-color: rgba(220, 53, 69, 0.25); }
        .action-icon:focus { outline: none; box-shadow: 0 0 0 2px rgba(0,123,255,.2); }
    </style>
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
                                            <th>Statut</th>
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
                                                        <?php
                                                        if (!empty($mouvement['status'] == 'rejeté')) {
                                                            echo '<span class="badge badge-danger">Rejeté</span>';
                                                        } elseif (!empty($mouvement['status'] == 'accepté')) {
                                                            echo '<span class="badge badge-success">Accepté</span>';
                                                        } else {
                                                            echo '<span class="badge badge-warning">En attente</span>';
                                                        }


                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if (empty($mouvement['idEmp_accepted']) && empty($mouvement['status'])): ?>
                                                            <button class="action-icon accept  reception-btn" data-toggle="modal" data-target="#receptionModal"
                                                                title="Réceptionner"
                                                                data-id="<?= htmlspecialchars($mouvement['num_Mouv_Mach'] ?? '') ?>"
                                                                data-machine-id="<?= htmlspecialchars($mouvement['id_machine'] ?? '') ?>">
                                                                <i class="fas fa-check text-success"></i>
                                                               
                                                            </button>
                                                            <button class="action-icon reject reject-btn" data-toggle="modal" data-target="#rejectModal"
                                                                title="Rejeter"
                                                                data-id="<?= htmlspecialchars($mouvement['num_Mouv_Mach'] ?? '') ?>"
                                                                data-machine-id="<?= htmlspecialchars($mouvement['id_machine'] ?? '') ?>">
                                                                <i class="fas fa-times text-danger"></i>
                                                              
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="text-muted">Traité</span>
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
                            <input type="hidden" name="equipment_ids" id="equipment_ids" value="">
                            <input type="hidden" name="type_mouvement" value="chaine_parc">

                            <div class="form-group">
                                <label for="recepteur"> Sélectionner un maintenancier :</label>
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
                                <div class="form-group">
                                    <label><i class="fas fa-tools"></i> Equipements associés à la machine :</label>
                                    <div id="equipementsList" class="equipment-list-container" style="min-height: 60px; max-height: 200px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 0.25rem; padding: 10px; background: #f8f9fa;"></div>
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

        <!-- Modal pour le rejet -->
        <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectModalLabel">
                            <i class="fas fa-times-circle"></i> Rejet de Machine
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="rejectForm" action="../../platform_gmao/public/index.php?route=mouvement_machines/reject" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="mouvement_id" id="reject_mouvement_id" value="">
                            <input type="hidden" name="machine_id" id="reject_machine_id" value="">
                            <input type="hidden" name="equipment_ids" id="reject_equipment_ids" value="">
                            <input type="hidden" name="type_mouvement" value="chaine_parc">

                            <div class="form-group">
                                <label for="rejecteur">Sélectionner un maintenancier :</label>
                                <select class="form-control" id="rejecteur" name="rejecteur" required>
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
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-tools"></i> Équipements associés à la machine :</label>
                                <div id="rejectEquipementsList" class="equipment-list-container" style="min-height: 60px; max-height: 200px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 0.25rem; padding: 10px; background: #f8f9fa;"></div>
                            </div>
                            <div class="text-right mt-2">
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Confirmer le rejet
                                </button>
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
                console.log(machineId);
                $('#mouvement_id').val(mouvementId);
                $('#machine_id').val(machineId);
                // Charger dynamiquement les équipements de la machine
                $('#equipementsList').html('<div class="loading-equipment"><i class="fas fa-spinner fa-spin"></i> <em>Chargement des équipements...</em></div>');
                $.ajax({
                    url: '../../platform_gmao/public/index.php?route=mouvement_machines/getEquipementsByMachine',
                    type: 'GET',
                    data: {
                        machine_id: machineId
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.length > 0) {
                            var html = '<div class="equipment-grid">';
                            var equipmentIds = [];
                            data.forEach(function(equipement) {
                                equipmentIds.push(equipement.accessory_ref);
                                html += '<div class="equipment-item">';
                                html += '<div class="equipment-icon"><i class="fas fa-cog"></i></div>';
                                html += '<div class="equipment-details">';
                                html += '<div class="equipment-ref"><strong>' + (equipement.accessory_ref || 'N/A') + '</strong></div>';
                                if (equipement.designation) {
                                    html += '<div class="equipment-designation">' + equipement.designation + '</div>';
                                }
                                if (equipement.reference && equipement.reference !== equipement.accessory_ref) {
                                    html += '<div class="equipment-reference"><small class="text-muted">Réf: ' + equipement.reference + '</small></div>';
                                }
                                html += '</div>';
                                html += '</div>';
                            });
                            html += '</div>';
                            $('#equipementsList').html(html);
                            // Sauvegarder les equipment_id en JSON
                            $('#equipment_ids').val(JSON.stringify(equipmentIds));
                        } else {
                            $('#equipementsList').html('<div class="no-equipment"><i class="fas fa-info-circle"></i> <em>Aucun équipement trouvé pour cette machine</em></div>');
                            $('#equipment_ids').val(JSON.stringify([]));
                        }
                    },
                    error: function(xhr) {
                        let msg = '<div class="error-equipment"><i class="fas fa-exclamation-triangle"></i> <em>Erreur lors du chargement des équipements</em>';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            msg += '<br><span style="color:red">' + xhr.responseJSON.error + '</span>';
                        }
                        msg += '</div>';
                        $('#equipementsList').html(msg);
                        $('#equipment_ids').val(JSON.stringify([]));
                    }
                });
            });

            // Mettre à jour l'ID du mouvement dans le modal de rejet
            $('.reject-btn').click(function() {
                var mouvementId = $(this).data('id');
                var machineId = $(this).data('machine-id');
                $('#reject_mouvement_id').val(mouvementId);
                $('#reject_machine_id').val(machineId);

                // Charger dynamiquement les équipements de la machine pour le rejet
                $('#rejectEquipementsList').html('<div class="loading-equipment"><i class="fas fa-spinner fa-spin"></i> <em>Chargement des équipements...</em></div>');
                $.ajax({
                    url: '../../platform_gmao/public/index.php?route=mouvement_machines/getEquipementsByMachine',
                    type: 'GET',
                    data: {
                        machine_id: machineId
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.length > 0) {
                            var html = '<div class="equipment-grid">';
                            var equipmentIds = [];
                            data.forEach(function(equipement) {
                                equipmentIds.push(equipement.accessory_ref);
                                html += '<div class="equipment-item">';
                                html += '<div class="equipment-icon"><i class="fas fa-cog"></i></div>';
                                html += '<div class="equipment-details">';
                                html += '<div class="equipment-ref"><strong>' + (equipement.accessory_ref || 'N/A') + '</strong></div>';
                                if (equipement.designation) {
                                    html += '<div class="equipment-designation">' + equipement.designation + '</div>';
                                }
                                if (equipement.reference && equipement.reference !== equipement.accessory_ref) {
                                    html += '<div class="equipment-reference"><small class="text-muted">Réf: ' + equipement.reference + '</small></div>';
                                }
                                html += '</div>';
                                html += '</div>';
                            });
                            html += '</div>';
                            $('#rejectEquipementsList').html(html);
                            // Sauvegarder les equipment_id en JSON
                            $('#reject_equipment_ids').val(JSON.stringify(equipmentIds));
                        } else {
                            $('#rejectEquipementsList').html('<div class="no-equipment"><i class="fas fa-info-circle"></i> <em>Aucun équipement trouvé pour cette machine</em></div>');
                            $('#reject_equipment_ids').val(JSON.stringify([]));
                        }
                    },
                    error: function(xhr) {
                        let msg = '<div class="error-equipment"><i class="fas fa-exclamation-triangle"></i> <em>Erreur lors du chargement des équipements</em>';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            msg += '<br><span style="color:red">' + xhr.responseJSON.error + '</span>';
                        }
                        msg += '</div>';
                        $('#rejectEquipementsList').html(msg);
                        $('#reject_equipment_ids').val(JSON.stringify([]));
                    }
                });
            });
        });
    </script>
</body>

</html>