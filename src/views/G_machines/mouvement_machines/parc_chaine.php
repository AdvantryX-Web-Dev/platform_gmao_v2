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
    $mouvements = $controller->getMouvementsByType('inter_chaine');
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Mouvement sortie de Parc</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <!-- <link rel="stylesheet" href="/platform_gmao/public/css/mouvementMachines.css"> -->
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

</head>
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

    .action-icon i {
        font-size: 12px;
    }

    .action-icon .label {
        display: none;
        font-size: 12px;
        font-weight: 500;
    }

    .action-icon:hover .label {
        display: inline;
    }

    .action-icon.accept:hover {
        background: rgba(40, 167, 69, 0.08);
        color: #28a745;
        border-color: rgba(40, 167, 69, 0.25);
    }

    .action-icon.reject:hover {
        background: rgba(220, 53, 69, 0.08);
        color: #dc3545;
        border-color: rgba(220, 53, 69, 0.25);
    }

    .action-icon:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, .2);
    }
</style>

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
                            <h6 class="m-0 font-weight-bold text-primary">Mouvements machines: Sortie parc</h6>
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
                                    $stmt = $conn->query("SELECT COUNT(*) FROM gmao__mouvement_machine WHERE type_Mouv = 'parc_chaine' AND idEmp_accepted IS NULL");
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
                                            <th>Action</th>
                                            <th>Statut</th>

                                            <th>Machine ID</th>
                                            <th>Référence</th>
                                            <th>Désignation</th>
                                            <th>Raisons</th>
                                            <th>Date mouvement</th>
                                            <th>Initiateur</th>
                                            <th>Réceptionneur</th>
                                            <th>Equipements</th>
                                            <th>Commentaire</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (is_array($mouvements) && count($mouvements) > 0): ?>
                                            <?php foreach ($mouvements as $mouvement): ?>
                                                <tr>
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
                                                    <td><?= htmlspecialchars($mouvement['id_machine'] ?? "") ?></td>
                                                    <td><?= htmlspecialchars($mouvement['reference'] ?? "") ?></td>
                                                    <td><?= htmlspecialchars($mouvement['designation'] ?? "") ?></td>
                                                    <td><?= htmlspecialchars($mouvement['raison_mouv'] ?? "") ?></td>
                                                    <td><?= htmlspecialchars($mouvement['date_mouvement'] ?? "") ?></td>
                                                    <td>
                                                        <?php
                                                        if (!empty($mouvement['idEmp_moved'])) {
                                                            echo htmlspecialchars($mouvement['emp_initiator_name'] ?? $mouvement['idEmp_moved']);
                                                        } else {
                                                            echo '<span class="text-muted"></span>';
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
                                                        $eq = $mouvement['equipement'] ?? null;
                                                        if ($eq) {
                                                            $arr = json_decode($eq, true);
                                                            if (is_array($arr) && count($arr) > 0) {
                                                                echo htmlspecialchars(implode(', ', $arr));
                                                            } else {
                                                                echo ' ';
                                                            }
                                                        } else {
                                                            echo ' ';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($mouvement['Comment'] ?? '') ?></td>




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

        <?php $type_mouvement = 'parc_chaine';
        $location = 'parc';
        include(__DIR__ . '/../../modals/mouvement_machine_modal.php'); ?>
        <?php $type_mouvement = 'parc_chaine';
        include(__DIR__ . '/../../modals/reception_modal.php'); ?>
        <?php $type_mouvement = 'parc_chaine';
        include(__DIR__ . '/../../modals/reject_modal.php'); ?>

        <!-- Scripts JavaScript -->
        <script src="/platform_gmao/public/js/jquery-3.6.4.min.js"></script>
        <script src="/platform_gmao/public/js/bootstrap.bundle.min.js"></script>
        <script src="/platform_gmao/public/js/jquery.dataTables.min.js"></script>
        <script src="/platform_gmao/public/js/dataTables.bootstrap4.min.js"></script>
        <script src="/platform_gmao/public/js/sb-admin-2.min.js"></script>
        <script src="/platform_gmao/public/js/sideBare.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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
                // $('#typeMachine').change(function() {
                //     var location = "parc";
                //     console.log("test");
                //     var machineType = $(this).val();
                //     console.log(machineType);
                //     if (machineType) {
                //         // Récupérer les machines du type sélectionné
                //         $.ajax({
                //             url: '../../platform_gmao/public/index.php?route=mouvement_machines/getMachinesByType',
                //             type: 'GET',
                //             data: {
                //                 type: machineType,
                //                 location: location
                //             },
                //             dataType: 'json',
                //             success: function(data) {
                //                 console.log(data);
                //                 var options = '<option value="">--Sélectionnez une machine--</option>';
                //                 $.each(data, function(index, machine) {
                //                     options += '<option value="' + machine.machine_id + '">' + machine.machine_id + ' - ' + machine.reference + ' </option>';
                //                 });
                //                 $('#machine').html(options);
                //             },
                //             error: function() {
                //                 alert('Une erreur est survenue lors de la récupération des machines.');
                //             }
                //         });
                //     } else {
                //         $('#machine').html('<option value="">--Sélectionnez une machine--</option>');
                //     }
                // });

                // Mettre à jour l'ID du mouvement dans le modal de réception
                $('.reception-btn').click(function() {
                    var mouvementId = $(this).data('id');
                    var machineId = $(this).data('machine-id');
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

                function initSelect2AlwaysSearch(sel) {
               if (typeof $ !== 'undefined' && $.fn.select2) {
                   $(sel).select2({
                       width: '100%',
                       placeholder: '-- Selectionner --',
                       allowClear: true,
                       minimumResultsForSearch: 0,
                       dropdownParent: $('#mouvementModal')
                   });
                   console.log('Select2 initialisé pour:', sel);
               } else {
                   console.log('Select2 non disponible pour:', sel);
                   // Fallback: activer la recherche native
                   $(sel).attr('onfocus', 'this.size=10;');
                   $(sel).attr('onblur', 'this.size=1;');
                   $(sel).attr('onchange', 'this.size=1;');
               }
           }

           // Initialiser Select2 avec un délai pour s'assurer que tout est chargé
           setTimeout(function() {
               initSelect2AlwaysSearch('#machine');
           }, 500);
            });
        </script>
    </div>
</body>

</html>
</html>