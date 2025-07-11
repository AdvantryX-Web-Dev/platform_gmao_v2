<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class Intervention_model
{

    public function __construct($maintainer_matricule, $prodline_id, $machine_id, $duration, $intervention_type, $etatMachine, $actionPrev, $intervention_date, $intervention_time, $codepannes, $articles)
    {
        $this->maintainer_matricule = $maintainer_matricule;
        $this->prodline_id = $prodline_id;
        $this->machine_id = $machine_id;
        $this->duration = $duration;


        $this->intervention_type  = $intervention_type;
        $this->etatMachine  = $etatMachine;
        $this->actionPrev  = $actionPrev;
        $this->intervention_date = $intervention_date;
        $this->intervention_time = $intervention_time;
        $this->codepannes = $codepannes;
        $this->articles = $articles;
    }
    public function __get($attr)
    {
        if (!isset($this->$attr)) return "erreur";
        else return ($this->$attr);
    }
    public function __set($attr, $value)
    {
        $this->$attr = $value;
    }

    public static function findByChaine($nomCh)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT m.designation, pi.*
        FROM prod__implantation pi
         JOIN init__machine m on (pi.machine_id=m.machine_id)
         where pi.prod_line ='$nomCh'
          and pi.machine_id NOT IN (
          select id_machine from `gmao__mouvement_machine`
            WHERE (type_Mouv = 'chaine_parc' ) 
            OR (type_Mouv = 'parc_chaine')
            )
         ORDER BY pi.cur_date, pi.cur_time

         ");
        $machines = $req->fetchAll();
        return $machines;
    }

    public static function nbInterPanneMachine($machine_id)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $year = date('Y');
        $req = $conn->query("SELECT p.codePanne, COUNT(*) as nbInter FROM `gmao__interventions` i JOIN `gmao__interv_panne` p ON(i.id= p.numInter )  WHERE machine_id = '" . $machine_id . "' AND YEAR(intervention_date) = '$year' GROUP BY codePanne");
        $resultats = $req->fetchAll();

        return $resultats;
    }


    public static function findByMachine($id_machine)
    {

        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT * FROM `gmao__interventions`   WHERE  machine_id  = '" . $id_machine . "' ORDER BY intervention_date DESC");
        $inters = $req->fetchAll();
        return $inters;
    }
    public static function maint_dispo()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT last_name, first_name, matricule
        FROM init__employee
        WHERE matricule NOT IN (SELECT maintainer FROM `aleas__maint_dispo`   where  req_interv_id NOT IN (select req_interv_id from aleas__end_maint_interv) ) AND lower(qualification)= 'MAINTAINER'");


        $req->execute();
        $maintDispos = $req->fetchAll();
        return $maintDispos;
    }
    public static function planningSave(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if the request is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = "Méthode non autorisée";
            header('Location: ../../public/index.php?route=intervention_preventive');
            return;
        }
        
        // Validate required fields
        $requiredFields = ['machine_id', 'intervention_type_id', 'planned_date'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Le champ '$field' est requis";
                header('Location: ../../public/index.php?route=intervention_preventive');
                return;
            }
        }
        
        // Get form data
        $machine_id = $_POST['machine_id'];
        $intervention_type_id = $_POST['intervention_type_id'];
        $planned_date = $_POST['planned_date'];
        $comments = $_POST['comments'] ?? '';
        
        // Create database connection
        $db = new Database();
        $conn = $db->getConnection();
        
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Insert into planning table
            $stmt = $conn->prepare("
                INSERT INTO gmao_planning 
                (machine_id, intervention_type_id, planned_date, comments, created_at,updated_at) 
                VALUES (?, ?, ?, ?, NOW(),NOW())
            ");
           
            $stmt->bindParam(1, $machine_id);
            $stmt->bindParam(2, $intervention_type_id);
            $stmt->bindParam(3, $planned_date);
            $stmt->bindParam(4, $comments);
            
            $result = $stmt->execute();
            
            if ($result) {
                // Commit the transaction
                $conn->commit();
                $_SESSION['success'] = "Intervention planifiée ajoutée avec succès";
            } else {
                // Rollback the transaction
                $conn->rollBack();
                $_SESSION['error'] = "Erreur lors de l'ajout de l'intervention planifiée";
            }
            
        } catch (PDOException $e) {
            // Rollback the transaction
            $conn->rollBack();
            $_SESSION['error'] = "Erreur de base de données: " . $e->getMessage();
        }
        
        header('Location: ../../public/index.php?route=intervention_preventive');
    }
   
}
