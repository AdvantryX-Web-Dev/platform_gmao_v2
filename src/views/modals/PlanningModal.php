<?php
$all_machines = \App\Models\Machine_model::findAllMachine();

?>
<div class="modal fade" id="planningModal" tabindex="-1" role="dialog" aria-labelledby="planningModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="planningModalLabel">
                    <i class="fas fa-calendar-plus"></i> Ajouter une intervention planifiée
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="planningForm" action="../../platform_gmao/public/index.php?route=intervention_planning/save" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="machine_id"> Machine :</label>
                        <select class="form-control" id="machine_id" name="machine_id" required>
                            <option value="">-- Sélectionner une machine --</option>
                            <?php

                            if (isset($all_machines) && is_array($all_machines)) {
                                foreach ($all_machines as $machine) {
                                    echo '<option value="' . htmlspecialchars($machine['id']) . '">' .
                                        htmlspecialchars($machine['machine_id']) .  '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="intervention_type_id"> Type d'intervention :</label>
                        <select class="form-control" id="intervention_type_id" name="intervention_type_id" required>
                            <option value="">-- Sélectionner un type --</option>
                            <?php
                            // Load intervention types (only preventive)
                            $interventionTypes = \App\Models\Intervention_type_model::findByType('preventive');
                            if (isset($interventionTypes) && is_array($interventionTypes)) {
                                foreach ($interventionTypes as $type) {
                                    echo '<option value="' . htmlspecialchars($type['id']) . '">' .
                                        htmlspecialchars($type['designation']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="planned_date"> Date planifiée :</label>
                        <input type="date" class="form-control" id="planned_date" name="planned_date" required>
                    </div>
                    <div class="form-group">
                        <label for="comments"><i class="fas fa-comment"></i> Commentaires :</label>
                        <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        Enregistrer
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>