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

    // public static function findByChaine($nomCh)
    // {
    //     $db = new Database();
    //     $conn = $db->getConnection();
    //     $req = $conn->query("SELECT m.designation, pi.*
    //     FROM prod__implantation pi
    //      JOIN init__machine m on (pi.machine_id=m.machine_id)
    //      where pi.prod_line ='$nomCh'
    //       and pi.machine_id NOT IN (
    //       select id_machine from `gmao__mouvement_machine`
    //         WHERE (type_Mouv = 'chaine_parc' ) 
    //         OR (type_Mouv = 'parc_chaine')
    //         )
    //      ORDER BY pi.cur_date, pi.cur_time

    //      ");
    //     $machines = $req->fetchAll();
    //     return $machines;
    // }
    public static function preventiveByChaine($prodline_id, $nomCh)
    {

        $db = new Database();
        $conn = $db->getConnection();

        // Récupérer toutes les interventions préventives
        $req = $conn->query("
            SELECT 
                a.*, 
                t.type, 
                t.designation as intervention_type,
                m.id as idmachine,
                m.machine_id,
                m.designation,
                (
                    SELECT smartbox 
                    FROM prod__implantation imp 
                    WHERE imp.prod_line = '" . $nomCh . "' 
                    LIMIT 1
                ) as smartbox
            FROM 
                gmao_intervention_action a
            LEFT JOIN 
                gmao_type_intervention t ON a.intervention_type_id = t.id
            LEFT JOIN 
                init__machine m ON a.machine_id = m.id
            WHERE 
                a.production_line_id = '" . $prodline_id . "' 
                AND (
                    a.planning_id IS NOT NULL 
                    OR (a.planning_id IS NULL AND t.type = 'preventive')
                )
            ORDER BY 
                a.created_at DESC
        ");

        $interventionspreventive = $req->fetchAll();

        // Compter le nombre d'interventions par machine et récupérer la dernière date
        $machinesData = [];
        foreach ($interventionspreventive as $intervention) {

            $machine_id = $intervention['machine_id'];

            if (!isset($machinesData[$machine_id])) {
                $machinesData[$machine_id] = [
                    'id' => $intervention['idmachine'],
                    'machine_id' => $machine_id,
                    'designation' => $intervention['designation'],
                    'smartbox' => $intervention['smartbox'],
                    'nb_interventions' => 0,
                    'last_date' => null
                ];
            }

            $machinesData[$machine_id]['nb_interventions']++;

            // Garder la date la plus récente (la première intervention dans le tableau trié)
            if ($machinesData[$machine_id]['last_date'] === null) {
                $machinesData[$machine_id]['last_date'] = $intervention['intervention_date'];
            }
        }

        return array_values($machinesData); // Convertir en tableau indexé

    }

    public static function curativeByChaine($prodline_id, $nomCh)
    {

        $db = new Database();
        $conn = $db->getConnection();

        // Récupérer toutes les interventions préventives
        $req = $conn->query("
            SELECT 
                a.*, 
                t.type, 
                t.designation as intervention_type,
                m.machine_id,
                m.id as idmachine,
                m.designation,
                (
                    SELECT smartbox 
                    FROM prod__implantation imp 
                    WHERE imp.prod_line = '" . $nomCh . "' 
                    LIMIT 1
                ) as smartbox
            FROM 
                gmao_intervention_action a
            LEFT JOIN 
                gmao_type_intervention t ON a.intervention_type_id = t.id
            LEFT JOIN 
                init__machine m ON a.machine_id = m.id
            WHERE 
                a.production_line_id = '" . $prodline_id . "' 
                AND t.type = 'curative'
               
            ORDER BY 
                a.created_at DESC
        ");

        $interventionspreventive = $req->fetchAll();

        // Compter le nombre d'interventions par machine et récupérer la dernière date
        $machinesData = [];
        foreach ($interventionspreventive as $intervention) {
            $machine_id = $intervention['machine_id'];

            if (!isset($machinesData[$machine_id])) {
                $machinesData[$machine_id] = [
                    'id' => $intervention['idmachine'],
                    'machine_id' => $machine_id,
                    'designation' => $intervention['designation'],
                    'smartbox' => $intervention['smartbox'],
                    'nb_interventions' => 0,
                    'last_date' => null
                ];
            }

            $machinesData[$machine_id]['nb_interventions']++;

            // Garder la date la plus récente (la première intervention dans le tableau trié)
            if ($machinesData[$machine_id]['last_date'] === null) {
                $machinesData[$machine_id]['last_date'] = $intervention['intervention_date'];
            }
        }

        return array_values($machinesData); // Convertir en tableau indexé

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
        $req = $conn->query("SELECT a.*, t.type as intervention_type,
                            m.machine_id as machine,
                            p.prod_line as prodline,
                            t.designation as intervention_type_designation,
                            e.last_name as maintainer_last_name,
                            e.first_name as maintainer_first_name,
                            pl.planned_date as planning_date

                            FROM `gmao_intervention_action` a
                            LEFT JOIN `gmao_type_intervention` t ON a.intervention_type_id = t.id
                            LEFT JOIN `init__machine` m ON a.machine_id = m.id
                            LEFT JOIN `init__prod_line` p ON a.production_line_id = p.id
                            LEFT JOIN `init__employee` e ON a.maintenance_by = e.id

                            LEFT JOIN `gmao_planning` pl ON a.planning_id = pl.id
                            WHERE a.machine_id = '" . $id_machine . "' 
                            ORDER BY a.created_at DESC");
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
    public static function planningSave()
    {
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
    public static function savePreventive()
    {
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
        $requiredFields = ['machine_id', 'production_line_id', 'intervention_date', 'maintenance_by'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Le champ '$field' est requis";
                header('Location: ../../public/index.php?route=intervention_preventive');
                return;
            }
        }

        // Validation spécifique: soit planning_id soit intervention_type_id doit être fourni
        if (empty($_POST['planning_id']) && empty($_POST['intervention_type_id'])) {
            $_SESSION['error'] = "Vous devez sélectionner soit un planning, soit un type d'intervention";
            header('Location: ../../public/index.php?route=intervention_preventive');
            return;
        }

        // Get form data
        $machine_id = $_POST['machine_id'];
        $production_line_id = $_POST['production_line_id'];
        $planning_id = !empty($_POST['planning_id']) ? $_POST['planning_id'] : null;
        $intervention_date = $_POST['intervention_date'];
        $maintenance_by = $_POST['maintenance_by'];
        $intervention_type_id =  !empty($_POST['intervention_type_id']) ? $_POST['intervention_type_id'] : null;
        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');
        // Create database connection
        $db = new Database();
        $conn = $db->getConnection();

        try {
            // Begin transaction
            $conn->beginTransaction();

            // Insert into intervention_action table
            $sql = "INSERT INTO gmao_intervention_action 
                (machine_id, production_line_id, planning_id, intervention_date, 
                maintenance_by, intervention_type_id, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(1, $machine_id);
            $stmt->bindParam(2, $production_line_id);
            $stmt->bindParam(3, $planning_id);
            $stmt->bindParam(4, $intervention_date);
            $stmt->bindParam(5, $maintenance_by);
            $stmt->bindParam(6, $intervention_type_id);


            $result = $stmt->execute();

            if ($result) {

                // Commit the transaction
                $conn->commit();
                $_SESSION['success'] = "Intervention préventive enregistrée avec succès";
            } else {
                // Rollback the transaction
                $conn->rollBack();
                $_SESSION['error'] = "Erreur lors de l'enregistrement de l'intervention préventive";
            }
        } catch (\PDOException $e) {
            // Rollback the transaction
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['error'] = "Erreur de base de données: " . $e->getMessage();
        }
        header('Location: ../../public/index.php?route=intervention_preventive');
    }
}
