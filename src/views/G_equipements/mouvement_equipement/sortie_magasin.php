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
    <link rel="stylesheet" href="/platform_gmao/public/css/datatables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* CSS personnalisé pour Select2 */
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
        }

        .select2-search__field:focus {
            border-color: #007bff !important;
            outline: none !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 1px solid #aaa !important;
        }

        .select2-search {
            display: block !important;
        }

        .select2-search__field {
            display: block !important;
            visibility: visible !important;
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

                            <div class="form-group">
                                <label for="maintenancier">Maintenancier :</label>
                                <?php
                                // Récupérer l'ID du maintenancier connecté
                                $connectedMatricule = $_SESSION['user']['matricule'] ?? null;
                                $connectedMaintainerId = null;
                                $connectedMaintainerName = '';

                                if ($connectedMatricule) {
                                    $db = \App\Models\Database::getInstance('db_digitex');
                                    $conn = $db->getConnection();
                                    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM init__employee WHERE matricule = ?");
                                    $stmt->execute([$connectedMatricule]);
                                    $maintainer = $stmt->fetch(\PDO::FETCH_ASSOC);

                                    if ($maintainer) {
                                        $connectedMaintainerId = $maintainer['id'];
                                        $connectedMaintainerName = trim($maintainer['first_name'] . ' ' . $maintainer['last_name']);
                                    }
                                }
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
                            <input type="hidden" name="type_mouvement" value="sortie_magasin">

                            <div class="form-group">
                                <label for="recepteur"> Maintenancier :</label>
                                <?php
                                // Récupérer l'ID du maintenancier connecté
                                $connectedMatricule = $_SESSION['user']['matricule'] ?? null;
                                $connectedMaintainerId = null;
                                $connectedMaintainerName = '';

                                if ($connectedMatricule) {
                                    $db = \App\Models\Database::getInstance('db_digitex');
                                    $conn = $db->getConnection();
                                    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM init__employee WHERE matricule = ?");
                                    $stmt->execute([$connectedMatricule]);
                                    $maintainer = $stmt->fetch(\PDO::FETCH_ASSOC);

                                    if ($maintainer) {
                                        $connectedMaintainerId = $maintainer['id'];
                                        $connectedMaintainerName = trim($maintainer['first_name'] . ' ' . $maintainer['last_name']);
                                    }
                                }
                                ?>
                                <?php if ($isAdmin): ?>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


    <script>
        // Document ready function
        $(document).ready(function() {
            // Fonction pour initialiser Select2
            function initSelect2() {
                // Détruire toute instance existante de Select2
                if ($('#equipment').hasClass('select2-hidden-accessible')) {
                    $('#equipment').select2('destroy');
                }

                // Initialiser Select2 pour le select d'équipement
                $('#equipment').select2({
                    placeholder: '--Sélectionnez un équipement--',
                    allowClear: true,
                    width: '100%',
                    minimumResultsForSearch: 0, // Toujours afficher la zone de recherche
                    dropdownAutoWidth: true,
                    dropdownParent: $('#mouvementModal'), // Important pour les modals
                    language: {
                        noResults: function() {
                            return "Aucun équipement trouvé";
                        },
                        searching: function() {
                            return "Recherche en cours...";
                        }
                    }
                });

                // Forcer le focus sur la zone de recherche quand le dropdown s'ouvre
                $('#equipment').on('select2:open', function() {
                    setTimeout(function() {
                        $('.select2-search__field').focus();
                        $('.select2-search__field').attr('readonly', false);
                    }, 100);
                });

                // S'assurer que la zone de recherche est cliquable
                $(document).on('click', '.select2-search__field', function() {
                    $(this).focus();
                    $(this).attr('readonly', false);
                });
            }

            // Initialiser Select2 quand le modal s'ouvre
            $('#mouvementModal').on('shown.bs.modal', function() {
                setTimeout(function() {
                    initSelect2();
                }, 100);
            });

            // Détruire Select2 quand le modal se ferme
            $('#mouvementModal').on('hidden.bs.modal', function() {
                if ($('#equipment').hasClass('select2-hidden-accessible')) {
                    $('#equipment').select2('destroy');
                }
            });

            // Initialiser Select2 au chargement de la page (au cas où)
            initSelect2();


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