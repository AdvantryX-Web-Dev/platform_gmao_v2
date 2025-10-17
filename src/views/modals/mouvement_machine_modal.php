<?php
// Variables requises:
// - $type_mouvement: string ('chaine_parc' | 'parc_chaine' | 'inter_chaine')
// - $location: string (ex: 'prodline' | 'parc')

use App\Models\Machine_model;

$machineOptions = Machine_model::machineByLocation($location);
?>
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
                    <input type="hidden" name="type_mouvement" value="<?= htmlspecialchars($type_mouvement ?? '') ?>">
                    <div class="form-group">
                        <label for="maintenancier">déplacé par :</label>
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
                        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
                        ?>

                        <?php if ($isAdmin): ?>
                            <select class="form-control" id="maintenancier" name="maintenancier" required>
                                <option value="">--Maintenancier--</option>
                                <?php
                                $controller = new \App\Controllers\Mouvement_machinesController();
                                $maintenanciers = $controller->getMaintainers();
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
                        <label for="idEmp_accepted">receptionné par :</label>
                        <select class="form-control" id="idEmp_accepted" name="idEmp_accepted" required>
                            <option value="">--Maintenancier--</option>
                            <?php
                            $controller = new \App\Controllers\Mouvement_machinesController();
                            $maintenanciers = $controller->getMaintainers();
                            foreach ($maintenanciers as $maintenancier) {
                                echo "<option value=\"{$maintenancier['id']}\">{$maintenancier['first_name']} {$maintenancier['last_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <!-- <div class="form-group">
                        <label for="typeMachine">Type de Machine :</label>
                        <select class="form-control" id="typeMachine" name="typeMachine" required>
                            <option value="">-- Sélectionner un type de machine --</option>
                           <?php
                            $controller = new \App\Controllers\Mouvement_machinesController();
                            $types = $controller->getTypes($location ?? '');
                            foreach ($types as $type) {
                                echo "<option value=\"{$type['type']}\" data-reference=\"{$type['type']}\">{$type['type']}</option>";
                            }
                            ?>
                        </select>
                    </div> -->

                    <!-- <div class="form-group">
                        <label for="machine">Machine :</label>
                        <select class="form-control" id="machine" name="machine" required>
                            <option value="">--Sélectionnez une machine--</option>
                        </select>
                    </div> -->



                    <label for="machine" class="mb-1">Machine</label>
                    <select class="form-control" id="machine" name="machine" required onfocus="this.size=10;" onblur="this.size=1;" onchange="this.size=1;">
                        <option value="">-- Sélectionner --</option>
                        <?php
                        if (!empty($machineOptions)) {
                            foreach ($machineOptions as $machine) {
                                echo "<option value=\"{$machine['machine_id']}\">{$machine['machine_id']}</option>";
                            }
                        }
                        ?>
                    </select>


                    <label for="raisonMouvement">Raison Mouvement Machine :</label>
                    <select class="form-control" id="raisonMouvement" name="raisonMouvement" required>
                        <option value="">--Raison Mouvement Machine--</option>
                        <?php
                        $raisons = $controller->getRaisons();
                        foreach ($raisons as $raison) {
                            echo "<option value=\"{$raison['id_Raison']}\">{$raison['raison_mouv_mach']}</option>";
                        }
                        ?>
                    </select>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Enregistrer</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</div>