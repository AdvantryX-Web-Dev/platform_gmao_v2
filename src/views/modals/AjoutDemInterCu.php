 <?php
    $all_machines = \App\Models\Machine_model::findAllMachine();
    $machineStatuses = \App\Models\Machine_model::getMachineStatus();
    ?>
 <!--Modal Ajouter Demande d'intervention curative -->
 <div class="modal fade" id="ajouterDemandeInterventionModal" tabindex="-1" role="dialog" aria-labelledby="ajouterDemandeInterventionModalLabel" aria-hidden="true">
     <div class="modal-dialog" role="document">
         <div class="modal-content">
             <div class="modal-header bg-primary text-white">
                 <h5 class="modal-title" id="ajouterDemandeInterventionModalLabel">
                     <i class="fas fa-wrench"></i> Ajouter une intervention curative
                 </h5>
                 <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                     <span aria-hidden="true">&times;</span>
                 </button>
             </div>

             <form id="ajouterInterventionForm" action="?route=intervention_curative/ajouterDemande" method="post">
                 <div class="modal-body">
                     <div class="form-group">
                         <label for="maintenance_by"> Maintenancier :</label>
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

                             <select class="form-control" id="maintenancier" name="maintenanciers" required>
                                 <option value="">-- Sélectionner un maintenancier --</option>
                                 <?php
                                    $dispoMaints = $interventionController->maint_dispo();
                                    foreach ($dispoMaints as $dispoMaint) {
                                        echo '<option value="' . htmlspecialchars($dispoMaint['matricule']) . '">' .
                                            htmlspecialchars($dispoMaint['matricule']) . ' - ' .
                                            htmlspecialchars($dispoMaint['first_name']) . ' ' .
                                            htmlspecialchars($dispoMaint['last_name']) . '</option>';
                                    }
                                    ?>
                             </select>
                         <?php else: ?>
                             <input type="hidden" id="maintenancier" name="maintenanciers" value="<?= htmlspecialchars($connectedMaintainerId) ?>">
                             <input type="text" class="form-control" value="<?= htmlspecialchars($connectedMaintainerName) ?>" readonly>

                         <?php endif; ?>
                     </div>
                     <div class="form-group">
                         <label for="machine"> Machine :</label>
                         <select class="form-control" id="machine" name="machines" required>
                             <option value="">-- Sélectionner une machine --</option>
                             <?php
                                foreach ($all_machines as $machine) {
                                    echo '<option value="' . htmlspecialchars($machine['id']) . '" data-reference="' . htmlspecialchars($machine['reference']) . '">' .
                                        htmlspecialchars($machine['machine_id'] . ' - ' . $machine['reference']) .  '</option>';
                                }
                                ?>
                         </select>
                     </div>

                     <div class="form-group">
                         <label for="intervention_type_id"> Type d'intervention :</label>
                         <select class="form-control" id="intervention_type_id" name="intervention_type_id" required>
                             <option value="">-- Sélectionner un type --</option>
                             <?php
                                // Charger uniquement les types d'intervention curative
                                $interventionTypes = \App\Models\Intervention_type_model::findByType('curative');
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
                         <label for="intervention_date">Date d'intervention :</label>
                         <input type="date" class="form-control" id="intervention_date" name="intervention_date" required>
                     </div>

                     <div class="form-group">
                         <label for="machine_status">Statut de la machine après l'intervention :</label>
                         <select class="form-control" id="machine_status" name="machine_status" required>
                             <option value="">-- Sélectionner un statut --</option>
                             <?php foreach ($machineStatuses as $status): ?>
                                 <?php if ($status['status_name'] != 'non fonctionnelle' && $status['status_name'] != 'fonctionnelle'): ?>
                                     <option value="<?php echo htmlspecialchars($status['id']); ?>"><?php echo htmlspecialchars($status['status_name']); ?></option>
                                 <?php endif; ?>
                             <?php endforeach; ?>
                         </select>
                     </div>


                 </div>
                 <div class="modal-footer">
                     <button type="submit" name="Ajouter" class="btn btn-success">
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