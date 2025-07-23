<?php
$all_machines = \App\Models\Machine_model::findAllMachine();

?>

<div class="modal fade" id="ajoutInterventionPreventiveModal" tabindex="-1" role="dialog" aria-labelledby="ajoutInterventionPreventiveModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="ajoutInterventionPreventiveModalLabel">
                    <i class="fas fa-tools"></i> Ajouter une intervention préventive
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="ajoutInterventionPreventiveForm" action="../../public/index.php?route=intervention/savePreventive" method="POST">
                <div class="modal-body">
                    <div class="form-group planning">
                        <label for="planning_id"> Planning (optionnel) :</label>
                        <select class="form-control" id="planning_id" name="planning_id">
                            <option value="">-- Aucun planning --</option>
                            <?php
                            // Load planning options
                            $planningController = new \App\Controllers\InterventionPlanningController();
                            $plannings = $planningController->getActivePlannings();

                            if (isset($plannings) && is_array($plannings)) {
                                foreach ($plannings as $planning) {
                                    echo '<option value="' . htmlspecialchars($planning['id']) . '">' .
                                        htmlspecialchars($planning['planned_date']) . ' - Machine: ' .
                                        htmlspecialchars($planning['machine_id']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group intervention-details">
                        <label for="intervention_type_id"> Type d'intervention :</label>
                        <select class="form-control" id="intervention_type_id" name="intervention_type_id">
                            <option value="">-- Sélectionner un type --</option>
                            <?php
                            // Load intervention types (only preventive)
                            $interventionTypes = \App\Models\Intervention_type_model::findByType('preventive');
                            if (isset($interventionTypes) && is_array($interventionTypes)) {
                                foreach ($interventionTypes as $type) {

                                    echo '<option value="' . htmlspecialchars($type['id']) . '">' .
                                        htmlspecialchars($type['designation']) . ' - ' .
                                        htmlspecialchars($type['id']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group intervention-details">
                        <label for="machine_id"> Machine :</label>
                        <select class="form-control" id="machine_id" name="machine_id" required>
                            <option value="">-- Sélectionner une machine --</option>
                            <?php
                            if (isset($all_machines) && is_array($all_machines)) {
                                foreach ($all_machines as $machine) {
                                    echo '<option value="' . htmlspecialchars($machine['id']) . '">' .
                                        htmlspecialchars($machine['machine_id']) . ' - ' .
                                        htmlspecialchars($machine['designation']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <?php

                    ?>
                    <div class="form-group intervention-details">
                        <label for="production_line_id"> Chaîne de production :</label>
                        <select class="form-control" id="production_line_id" name="production_line_id" required>
                            <option value="">-- Sélectionner une chaîne --</option>
                            <?php

                            if (isset($chaines) && is_array($chaines)) {
                                foreach ($chaines as $chaine) {
                                    echo '<option value="' . htmlspecialchars($chaine['id']) . '">' .
                                        htmlspecialchars($chaine['prod_line']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>


                    <div class="form-group">
                        <label for="maintenance_by"> Maintenancier :</label>
                        <select class="form-control" id="maintenance_by" name="maintenance_by" required>
                            <option value="">-- Sélectionner un maintenancier --</option>
                            <?php
                            // Load maintainers
                            $maintainers = \App\Models\Maintainer_model::findAll();

                            if (isset($maintainers) && is_array($maintainers)) {
                                foreach ($maintainers as $maintainer) {
                                    echo '<option value="' . htmlspecialchars($maintainer['id']) . '">' .
                                        htmlspecialchars($maintainer['first_name']) . ' ' .
                                        htmlspecialchars($maintainer['last_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="intervention_date"> Date d'intervention :</label>
                        <input type="date" class="form-control" id="intervention_date" name="intervention_date" required>
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


<script>
    $(document).ready(function() {
        console.log('Document ready - initializing...');

        // Variable globale pour stocker la valeur actuelle du type d'intervention
        var currentTypeValue = '';
        var currentPlanningValue = '';

        // Fonction pour mettre à jour la visibilité des champs basée sur les valeurs stockées
        function updateVisibility() {
            console.log('Mise à jour visibilité avec: Type=' + currentTypeValue + ', Planning=' + currentPlanningValue);

            if (currentTypeValue && currentTypeValue !== '') {
                console.log('→ Type sélectionné: cacher planning');
                $('.planning').hide();
                $('.intervention-details').show();
                // Vider planning car incompatible
                $('#planning_id').val('');
                currentPlanningValue = '';
            } else if (currentPlanningValue && currentPlanningValue !== '') {
                console.log('→ Planning sélectionné: cacher détails intervention');
                $('.planning').show();
                $('.intervention-details').hide();
                $('#machine_id, #production_line_id').attr('required', false);
            } else {
                console.log('→ Rien sélectionné: tout afficher');
                $('.planning').show();
                $('.intervention-details').show();
            }
        }

        // Capture directe de la valeur lors du changement
        $(document).on('change', '#intervention_type_id', function() {
            var selectedValue = $(this).val();
            console.log('Type intervention changé: ' + selectedValue);
            currentTypeValue = selectedValue;
            updateVisibility();
        });

        $(document).on('change', '#planning_id', function() {
            var selectedValue = $(this).val();
            console.log('Planning changé: ' + selectedValue);
            currentPlanningValue = selectedValue;
            updateVisibility();
        });

        // Au chargement du modal, lire les valeurs initiales
        $('#ajoutInterventionPreventiveModal').on('shown.bs.modal', function() {
            currentTypeValue = $('#intervention_type_id').val() || '';
            currentPlanningValue = $('#planning_id').val() || '';
            console.log('Modal ouvert, valeurs initiales: Type=' + currentTypeValue + ', Planning=' + currentPlanningValue);
            updateVisibility();
        });

        // Pour faciliter le test
        $('<button type="button" class="btn btn-info mt-2" id="testVisibility">Test Visibilité</button>')
            .insertAfter('#intervention_type_id')
            .on('click', function(e) {
                e.preventDefault();
                console.log('Test manuel - Valeurs actuelles: Type=' + currentTypeValue + ', Planning=' + currentPlanningValue);
                updateVisibility();
            });
    });
</script>
