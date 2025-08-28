<?php

namespace App\Models;

use App\Models\Database;
use PDO;

class Mouvement_equipment_model
{
    private $num_Mouv_Mach;
    private $date_mouvement;
    private $id_machine;
    private $id_Rais;
    private $idEmp;
    private $type_Mouv;

    public function __construct($num_Mouv_Mach = null, $date_mouvement = null, $id_machine = null, $id_Rais = null, $idEmp = null, $type_Mouv = null)
    {
        $this->num_Mouv_Mach = $num_Mouv_Mach;
        $this->date_mouvement = $date_mouvement;
        $this->id_machine = $id_machine;
        $this->id_Rais = $id_Rais;
        $this->idEmp = $idEmp;
        $this->type_Mouv = $type_Mouv;
    }

    public function __get($attr)
    {
        if (!isset($this->$attr)) {
            return "erreur";
        } else {
            return ($this->$attr);
        }
    }

    public function __set($attr, $value)
    {
        $this->$attr = $value;
    }

    public static function findAll()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->query("SELECT mm.*, m.reference, m.designation 
            FROM gmao__mouvement_machine mm 
            INNER JOIN init__machine m ON mm.id_machine = m.machine_id
            ORDER BY mm.date_mouvement DESC");
        $req->execute();
        $resultats = $req->fetchAll();
        return $resultats;
    }


    public static function findEntreMagasin()
    {
        $dbGmao = new Database();
        $conn = $dbGmao->getConnection();

        // Utiliser une requête qui spécifie explicitement les bases de données
        $query = "
                SELECT
                mm.*, 
                m.machine_id as machine_id,
                m.reference as machine_reference,
                e.equipment_id as equipment_id,
                e.reference as equipment_reference ,
                e.designation ,
                rm.raison_mouv_mach as raison_mouv,
                e1.first_name as initiator_first_name, e1.last_name as initiator_last_name,
                e2.first_name as acceptor_first_name, e2.last_name as acceptor_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
        FROM gmao__mouvement_equipment mm 
        INNER JOIN gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
        INNER JOIN gmao__init_equipment e ON mm.equipment_id = e.id
        INNER JOIN init__machine m ON mm.id_machine = m.id
        LEFT JOIN init__employee e1 ON mm.idEmp_moved = e1.id
        LEFT JOIN init__employee e2 ON mm.idEmp_accepted = e2.id
        WHERE mm.type_Mouv = 'entre_magasin'
        ORDER BY mm.date_mouvement DESC
        ";


        try {
            $req = $conn->query($query);
            $resultats = $req->fetchAll();

            return $resultats;
        } catch (\PDOException $e) {

            // En cas d'erreur (par exemple si les qualificateurs de bases ne fonctionnent pas)
            // Logger l'erreur et utiliser la méthode alternative
            error_log("Erreur dans findEntreMagasin: " . $e->getMessage());
            return [];
        }
    }

    public static function findSortieMagasin()
    {
        $dbGmao = new Database();
        $conn = $dbGmao->getConnection();

        // Utiliser une requête qui spécifie explicitement les bases de données
        $query = "
                SELECT
                mm.*, 
                m.machine_id as machine_id,
                m.reference as machine_reference,
                e.equipment_id as equipment_id,
                e.reference as equipment_reference ,
                e.designation ,
                rm.raison_mouv_mach as raison_mouv,
                e1.first_name as initiator_first_name, e1.last_name as initiator_last_name,
                e2.first_name as acceptor_first_name, e2.last_name as acceptor_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
        FROM gmao__mouvement_equipment mm 
        INNER JOIN gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
        INNER JOIN gmao__init_equipment e ON mm.equipment_id = e.id
        INNER JOIN init__machine m ON mm.id_machine = m.id
        LEFT JOIN init__employee e1 ON mm.idEmp_moved = e1.id
        LEFT JOIN init__employee e2 ON mm.idEmp_accepted = e2.id
        WHERE mm.type_Mouv = 'sortie_magasin'
        ORDER BY mm.date_mouvement DESC
        ";


        try {
            $req = $conn->query($query);
            $resultats = $req->fetchAll();

            return $resultats;
        } catch (\PDOException $e) {

            // En cas d'erreur (par exemple si les qualificateurs de bases ne fonctionnent pas)
            // Logger l'erreur et utiliser la méthode alternative
            error_log("Erreur dans findEntreMagasin: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Méthode de secours qui utilise plusieurs requêtes si la jointure entre bases échoue
     */


    public static function historiqueEquipement()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->prepare("
            SELECT 
                mm.*,
                rm.raison_mouv_mach as raison_mouv,
              
                e1.first_name as initiator_first_name, 
                e1.last_name as initiator_last_name,
                e2.first_name as acceptor_first_name, 
                e2.last_name as acceptor_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
            FROM 
                gmao__mouvement_equipment mm
            INNER JOIN 
              gmao__init_equipment e ON mm.equipment_id = e.id
            INNER JOIN 
                gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
            LEFT JOIN 
                init__employee e1 ON mm.idEmp_moved = e1.id
            LEFT JOIN 
                init__employee e2 ON mm.idEmp_accepted = e2.id
            LEFT JOIN 
              gmao__status ee ON e.etat_equipment_id  = ee.id
            LEFT JOIN 
               gmao__location l ON e.location_id = l.id
            WHERE 
                mm.equipment_id = :equipment_id
            ORDER BY 
                mm.date_mouvement DESC, mm.created_at DESC
        ");
        $req->bindParam(':equipment_id', $equipment_id, PDO::PARAM_STR);
        $req->execute();
        $resultats = $req->fetchAll();

        return $resultats;
    }
}
