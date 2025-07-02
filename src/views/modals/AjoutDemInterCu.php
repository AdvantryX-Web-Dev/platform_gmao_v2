 <!--Modal Ajouter Demande d'intervention curative -->
 <div class="modal" id="ajouterDemandeInterventionModal">
     <div class="modal-dialog">
         <div class="modal-content">
             <div class="modal-header">
                 <h4 class="modal-title" style="color: black;">Ajouter une Demande d'intervention curative</h4>
             </div>

             <div class="modal-body">
                 <form id="ajouterInterventionForm" action="?route=intervention_curative/ajouterDemande" method="post">
                     <div class="form-group">
                         <label for="dateIntervention">Chaine</label>
                         <input type="text" id="chaine" class="form-control" name="chaine" value="<?php echo $selectedChaine; ?>" readonly>

                     </div>


                     <div class="form-group">
                         <label for="machine" class="form-label">Machine</label>
                         <select class="form-control" id="machine" name="machines" required>
                             <?php


                                foreach ($machines as $machine) {
                                    echo "<option value='" .  $machine['machine_id'] . "'>" .  $machine['machine_id'] .  "</option>";
                                }
                                ?>

                         </select>
                     </div>


                     <div class="form-group">
                         <label for="maintenancier">maintenancier:</label>
                         <select class="form-control" id="maintenancier" name="maintenanciers" required>
                             <option value="">--maintenancier--</option>
                             <?php

                                $dispoMaints = $interventionController->maint_dispo();
                                foreach ($dispoMaints as $dispoMaint) {
                                    echo "<option value='" . $dispoMaint['matricule'] . "'>" . $dispoMaint['matricule'] . "   " . $dispoMaint['first_name'] . "" . $dispoMaint['last_name'] . "</option>";
                                }
                                ?>

                         </select>

                     </div>

                     <button class="btn btn-outline-primary" type="submit" name="Ajouter">Ajouter</button>
                     <button class="btn btn-outline-dark" data-dismiss="modal">Annuler</button>
                 </form>
             </div>
         </div>
     </div>
 </div>