<?php

namespace App\Controllers;

use App\Models\Intervention_model;
use App\Models\Demande_maintenancier;

class InterventionController
{
    public function index()
    {
        include(__DIR__ . '/../views/InterventionPreventive.php');
    }
    public function indexCorrective()
    {
        include(__DIR__ . '/../views/InterventionCurative.php');
    }
    public function getByChaine($nomCh)
    {
        $machines = Intervention_model::findByChaine($nomCh);

        return $machines;
    }
    public function getInterventionByMachine($id_machine)
    {

        return Intervention_model::findByMachine($id_machine);
    }
    public function getNbInterPannMach($id_machine)
    {
        return Intervention_model::nbInterPanneMachine($id_machine);
    }
    function maint_dispo()
    {
        return Intervention_model::maint_dispo();
    }

    public function ajouterDemande()
    {
        try {
            if (isset($_POST['Ajouter'])) {
                if (isset($_POST['machines']) && !empty($_POST['chaine']) && isset($_POST['maintenanciers'])) {
                    $chaine = $_POST['chaine'];
                    $machine = $_POST['machines'];
                    $maintenancier = $_POST['maintenanciers'];
                    $id_interv = \App\Models\Demande_maintenancier::id_interv($chaine, $machine);
                    $result = \App\Models\Demande_maintenancier::AjouterDemandeMain($machine, $id_interv, $maintenancier);
                    if ($result) {
                        $msg = 'status=succ';
                    } else {
                        $msg = 'status=error';
                    }
                    header("Location: ?route=intervention_preventive&chaine=" . urlencode($chaine) . '&' . $msg);
                    exit;
                }
            }
        } catch (\Exception $e) {
            // Stocke le message d'erreur dans la session
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?route=intervention_preventive&chaine=" . urlencode($_POST['chaine']) . '&status=error');
            exit;
        }
    }

    public function historiqueIntervsMach()
    {
        include(__DIR__ . '/../views/HistoriqueIntervsMach.php');
    }
}
