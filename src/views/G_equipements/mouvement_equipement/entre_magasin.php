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
    <title>Mouvement Entrée au magasin</title>
    <link rel="icon" type="image/x-icon" href="/public/images/images.png" />
    <link rel="stylesheet" href="/platform_gmao/public/css/all.min.css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link rel="stylesheet" href="/platform_gmao/public/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/table.css">
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* CSS optimisé pour Select2 */
        .select2-search__field {
            width: 100% !important;
            height: 32px !important;
            padding: 6px 12px !important;
            border: 1px solid #ccc !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            background-color: white !important;
            color: #333 !important;
            cursor: text !important;
            pointer-events: auto !important;
            display: block !important;
            visibility: visible !important;
        }

        .select2-search__field:focus {
            border-color: #007bff !important;
            outline: none !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }

        .select2-search {
            display: block !important;
        }
    </style>
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
                            <h6 class="m-0 font-weight-bold text-primary">Mouvement équipements : Entrée magasin </h6>
                            <div class="d-flex">
                                <button type="button" class="btn btn-primary mr-2" data-toggle="modal" data-target="#mouvementModal">
                                    <i class="fas fa-plus"></i> mouvement
                                </button>
                                <a href="#" class="btn btn-warning d-flex align-items-center">
                                    <i class="fas fa-bell mr-1"></i> Réception
                                    <?php
                                    // Fonction pour compter les mouvements en attente
                                    function getPendingMovementsCount($type)
                                    {
                                        $db = new App\Models\Database();
                                        $conn = $db->getConnection();
                                        $stmt = $conn->prepare("SELECT COUNT(*) FROM gmao__mouvement_equipment WHERE type_Mouv = ? AND idEmp_accepted IS NULL");
                                        $stmt->execute([$type]);
                                        return $stmt->fetchColumn();
                                    }

                                    $count = getPendingMovementsCount('entre_magasin');
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
                            <input type="hidden" name="type_mouvement" value="entre_magasin">
                            <div class="form-group">

                                <label for="equipment">Equipement ID :</label>
                                <select class="form-control select2-searchable" id="equipment" name="equipment" required>
                                    <option value="">--Sélectionnez un équipement--</option>
                                    <?php
                                    $controller = new Mouvement_equipmentController();
                                    $location = "prodline";
                                    $equipements = $controller->getEquipements($location);

                                    foreach ($equipements as $equipement) {
                                        echo "<option value=\"{$equipement['id']}\">{$equipement['equipment_id']}-{$equipement['reference']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>


                            <div class="form-group">
                                <label for="maintenance_by">Maintenancier :</label>
                                <?php
                                // Fonction pour récupérer les infos du maintenancier connecté
                                function getConnectedMaintainer()
                                {
                                    $connectedMatricule = $_SESSION['user']['matricule'] ?? null;
                                    if (!$connectedMatricule) return [null, null, ''];

                                    $db = \App\Models\Database::getInstance('db_digitex');
                                    $conn = $db->getConnection();
                                    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM init__employee WHERE matricule = ?");
                                    $stmt->execute([$connectedMatricule]);
                                    $maintainer = $stmt->fetch(\PDO::FETCH_ASSOC);

                                    if ($maintainer) {
                                        return [
                                            $maintainer['id'],
                                            $maintainer['id'],
                                            trim($maintainer['first_name'] . ' ' . $maintainer['last_name'])
                                        ];
                                    }
                                    return [null, null, ''];
                                }

                                [$connectedMaintainerId, $connectedMaintainerId2, $connectedMaintainerName] = getConnectedMaintainer();
                                ?>
                                <?php if ($isAdmin): ?>

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
                                <?php else: ?>
                                    <input type="hidden" name="maintenancier" value="<?= htmlspecialchars($connectedMaintainerId) ?>">
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($connectedMaintainerName) ?>" readonly>

                                <?php endif; ?>
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
                            <input type="hidden" name="type_mouvement" value="entre_magasin">

                            <div class="form-group">
                                <label for="recepteur">Sélectionner un maintenancier :</label>
                                <?php
                                // Réutiliser la fonction getConnectedMaintainer()
                                [$connectedMaintainerId, $connectedMaintainerId2, $connectedMaintainerName] = getConnectedMaintainer();
                                ?>
                                <?php if ($isAdmin): ?>
                                    <select class="form-control" id="recepteur" name="recepteur">
                                        <option value="">--Sélectionner un maintenancier--</option>
                                        <?php
                                        $controller = new Mouvement_equipmentController();
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
                                <?php else: ?>
                                    <input type="hidden" name="recepteur" value="<?= htmlspecialchars($connectedMaintainerId) ?>">
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($connectedMaintainerName) ?>" readonly>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="etat_equipement">Etat de l'équipement :</label>
                                <select class="form-control" id="etat_equipement" name="etat_equipement" required>
                                    <option value="">--Etat de l'Equipement--</option>
                                    <?php
                                    $controller = new Mouvement_equipmentController();
                                    $etat_equipement = $controller->getEquipementStatus();
                                    foreach ($etat_equipement as $etat) {
                                        if (in_array($etat['status_name'], ['fonctionnelle', 'non fonctionnelle'])) {
                                            echo "<option value=\"{$etat['id']}\">{$etat['status_name']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="text-right mt-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Confirmer
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

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Fonction générique pour initialiser Select2 dans un modal
            function initSelect2InModal(selectId, modalId, placeholder) {
                function initSelect2() {
                    if ($(selectId).hasClass('select2-hidden-accessible')) {
                        $(selectId).select2('destroy');
                    }

                    $(selectId).select2({
                        placeholder: placeholder,
                        allowClear: true,
                        width: '100%',
                        minimumResultsForSearch: 0,
                        dropdownAutoWidth: true,
                        dropdownParent: $(modalId),
                        language: {
                            noResults: function() {
                                return "Aucun résultat trouvé";
                            },
                            searching: function() {
                                return "Recherche en cours...";
                            }
                        }
                    });

                    // Gestion du focus et de la saisie
                    $(selectId).on('select2:open', function() {
                        setTimeout(function() {
                            $('.select2-search__field').focus().attr('readonly', false);
                        }, 100);
                    });
                }

                // Gestion du cycle de vie du modal
                $(modalId).on('shown.bs.modal', function() {
                    setTimeout(initSelect2, 100);
                }).on('hidden.bs.modal', function() {
                    if ($(selectId).hasClass('select2-hidden-accessible')) {
                        $(selectId).select2('destroy');
                    }
                });

                initSelect2(); // Initialisation au chargement
            }

            // Initialiser Select2 pour le modal de mouvement
            initSelect2InModal('#equipment', '#mouvementModal', '--Sélectionnez un équipement--');

            // Configuration DataTable
            $('#dataTable').DataTable({
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

            // Gestion des messages flash
            setTimeout(() => $("#flash-message").fadeOut("slow"), 4000);

            // Gestion des boutons de réception
            $('.reception-btn').click(function() {
                const mouvementId = $(this).data('id');
                const equipmentId = $(this).data('equipment-id');
                $('#mouvement_id').val(mouvementId);
                $('#equipment_id').val(equipmentId);
            });
        });
    </script>
</body>

</html>