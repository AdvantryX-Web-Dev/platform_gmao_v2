<?php
// Variables requises:
// - $type_mouvement: string ('chaine_parc' | 'parc_chaine' | 'inter_chaine')
?>
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
                    <input type="hidden" name="type_mouvement" value="<?= htmlspecialchars($type_mouvement ?? '') ?>">

                    <div class="form-group">
                        <label for="recepteur"> Sélectionner un maintenancier :</label>
                        <select class="form-control" id="recepteur" name="recepteur">
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
                        <div class="form-group">
                            <label for="etat_machine">Etat de la Machine :</label>
                            <select class="form-control" id="etat_machine" name="etat_machine" required>
                                <option value="">--Etat de la Machine--</option>
                                <?php
                                $etat_machine = $controller->getMachineStatus();
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


