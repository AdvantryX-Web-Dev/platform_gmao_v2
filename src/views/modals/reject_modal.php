<?php
// Variables requises:
// - $type_mouvement: string ('chaine_parc' | 'parc_chaine' | 'inter_chaine')
?>
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">
                    <i class="fas fa-times-circle"></i> Rejeter le mouvement de la machine
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
                    <input type="hidden" name="type_mouvement" value="<?= htmlspecialchars($type_mouvement ?? '') ?>">

                    <div class="form-group">
                        <label for="rejecteur">Sélectionner un maintenancier :</label>
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
                            <select class="form-control" id="rejecteur" name="rejecteur" required>
                                <option value="">--Sélectionner un maintenancier--</option>
                                <?php
                                $controller = new \App\Controllers\Mouvement_machinesController();
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
                            <input type="hidden" name="rejecteur" value="<?= htmlspecialchars($connectedMaintainerId) ?>">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($connectedMaintainerName) ?>" readonly>

                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-tools"></i> Équipements associés à la machine :</label>
                        <div id="rejectEquipementsList" class="equipment-list-container" style="min-height: 60px; max-height: 200px; overflow-y: auto; border: 1px solid #ced4da; border-radius: 0.25rem; padding: 10px; background: #f8f9fa;"></div>
                    </div>
                    <div class="form-group">
                        <label for="reject_reason">Raison du rejet :</label>
                        <select class="form-control" id="reject_reason" name="reject_reason" required>
                            <option value="">--Sélectionner une raison de rejet--</option>
                            <?php
                            // Récupérer les raisons de rejet depuis la base de données

                            $rejectionReasons = \App\Models\RejectionReasons_model::getAllRejectionReasons();

                            if (!empty($rejectionReasons)) {
                                foreach ($rejectionReasons as $reason) {
                                    echo "<option value=\"{$reason['reason_name']}\" >{$reason['reason_name']}</option>";
                                }
                            } else {
                                echo "<option value=\"\" disabled>Aucune raison de rejet trouvée</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="text-right mt-2">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times"></i> Confirmer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>