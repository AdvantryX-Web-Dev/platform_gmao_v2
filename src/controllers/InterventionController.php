<?php

namespace App\Controllers;

use App\Models\Database;
use App\Models\Intervention_model;
use App\Models\Demande_maintenancier;

class InterventionController
{
    /**
     * Display the preventive intervention page
     */
    public function index()
    {
        include(__DIR__ . '/../views/InterventionPreventive.php');
    }
    public function preventiveByChaine($prodline_id, $nomCh)
    {
        $machines = Intervention_model::preventiveByChaine($prodline_id, $nomCh);

        return $machines;
    }
    /**
     * Display the preventive curative page
     */
    public function indexCorrective()
    {
        include(__DIR__ . '/../views/InterventionCurative.php');
    }
    /**
     * Display the curative by chaine
     */
    public function curativeByChaine($prodline_id, $nomCh)
    {

        $machines = Intervention_model::curativeByChaine($prodline_id, $nomCh);

        return $machines;
    }

    /**
     * Display the number of intervention by machine
     */
    public function getNbInterPannMach($id_machine)
    {
        return Intervention_model::nbInterPanneMachine($id_machine);
    }


    public function ajouterDemande()
    {
        echo "<pre>";
        print_r($_POST);
        echo "</pre>";
        die;
        try {
            if (isset($_POST['Ajouter'])) {
                if (isset($_POST['machines']) && !empty($_POST['chaine']) && isset($_POST['maintenanciers']) && !empty($_POST['intervention_type_id'])) {
                    $chaine = $_POST['chaine'];
                    $machine = $_POST['machines'];
                    $maintenancier = $_POST['maintenanciers'];
                    $intervention_type_id = $_POST['intervention_type_id'];

                    // Récupérer l'ID de la ligne de production
                    $production_line_id = !empty($_POST['production_line_id']) ? intval($_POST['production_line_id']) : null;

                    // Si production_line_id n'est pas fourni ou est invalide, chercher dans la base de données
                    if (empty($production_line_id)) {
                        $db = new \App\Models\Database();
                        $conn = $db->getConnection();
                        $stmtProd = $conn->prepare("SELECT id FROM prod__implantation WHERE prod_line = :chaine LIMIT 1");
                        $stmtProd->bindParam(':chaine', $chaine);
                        $stmtProd->execute();
                        $production_line_id = $stmtProd->fetchColumn();

                        if (!$production_line_id) {
                            // Si on ne trouve pas la ligne de production, essayons de chercher par machine
                            $stmtProdMach = $conn->prepare("SELECT id FROM prod__implantation WHERE machine_id = :machine_id LIMIT 1");
                            $stmtProdMach->bindParam(':machine_id', $machine);
                            $stmtProdMach->execute();
                            $production_line_id = $stmtProdMach->fetchColumn();

                            if (!$production_line_id) {
                                throw new \Exception("Impossible de trouver la ligne de production pour cette machine.");
                            }
                        }
                    }

                    // Récupérer l'ID d'intervention
                    $id_interv = \App\Models\Demande_maintenancier::id_interv($chaine, $machine);

                    // Ajouter la demande
                    $result = \App\Models\Demande_maintenancier::AjouterDemandeMain(
                        $machine,
                        $id_interv,
                        $maintenancier,
                        $intervention_type_id,
                        $production_line_id
                    );

                    if ($result) {
                        $msg = 'status=succ';
                    } else {
                        $msg = 'status=error';
                    }
                    header("Location: ?route=intervention_curative&chaine=" . urlencode($chaine) . '&' . $msg);
                    exit;
                }
            }
        } catch (\Exception $e) {
            // Stocke le message d'erreur dans la session
            $_SESSION['error'] = $e->getMessage();
            header("Location: ?route=intervention_curative&chaine=" . urlencode($_POST['chaine']) . '&status=error');
            exit;
        }
    }
    function maint_dispo()
    {
        return Intervention_model::maint_dispo();
    }
    /**
     * Display the historique of intervention by machine
     */
    public function historiqueIntervsMach()
    {
        include(__DIR__ . '/../views/HistoriqueIntervsMach.php');
    }


    public function planningSave()
    {
        return Intervention_model::planningSave();
    }

    /**
     * Save a preventive intervention
     */
    public function savePreventive()
    {

        return Intervention_model::savePreventive();
    }
}
