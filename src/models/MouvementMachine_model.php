<?php

namespace App\Models;

use App\Models\Database;
use PDO;

class MouvementMachine_model
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

    public static function findByType($type_Mouv)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->prepare("SELECT mm.*, m.reference, m.designation 
            FROM gmao__mouvement_machine mm 
            INNER JOIN init__machine m ON mm.id_machine = m.machine_id
            WHERE mm.type_Mouv = ?
            ORDER BY mm.date_mouvement DESC");
        $req->execute([$type_Mouv]);
        $resultats = $req->fetchAll();
        return $resultats;
    }

    public static function findInterChaine()
    {
        $dbGmao = Database::getInstance('MAHDCO_MAINT');
        $conn = $dbGmao->getConnection();

        // Utiliser une requête qui spécifie explicitement les bases de données
        $query = "
        SELECT mm.*, m.reference, m.designation, rm.raison_mouv_mach as raison_mouv,
                e1.first_name as initiator_first_name, e1.last_name as initiator_last_name,
                e2.first_name as acceptor_first_name, e2.last_name as acceptor_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
        FROM MAHDCO_MAINT.gmao__mouvement_machine mm 
        INNER JOIN MAHDCO_MAINT.gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
        INNER JOIN db_mahdco.init__machine m ON mm.id_machine = m.machine_id
        LEFT JOIN db_mahdco.init__employee e1 ON mm.idEmp_moved = e1.id
        LEFT JOIN db_mahdco.init__employee e2 ON mm.idEmp_accepted = e2.id
        WHERE mm.type_Mouv = 'inter_chaine'
        ORDER BY mm.date_mouvement DESC";

        try {
            $req = $conn->query($query);
            $resultats = $req->fetchAll();
            return $resultats;
        } catch (\PDOException $e) {
            // En cas d'erreur (par exemple si les qualificateurs de bases ne fonctionnent pas)
            // Logger l'erreur et utiliser la méthode alternative
            error_log("Erreur dans findChaineParc: " . $e->getMessage());
            return self::findInterChainev0();
        }
        $req->execute();
        $resultats = $req->fetchAll();

        return $resultats;
    }
    public static function findInterChainev0()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->query("SELECT mm.*, m.reference, m.designation, rm.raison_mouv_mach as raison_mouv,
                e1.first_name as initiator_first_name, e1.last_name as initiator_last_name,
                e2.first_name as acceptor_first_name, e2.last_name as acceptor_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
            FROM gmao__mouvement_machine mm 
            INNER JOIN init__machine m ON mm.id_machine = m.machine_id
            INNER JOIN gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
            LEFT JOIN init__employee e1 ON mm.idEmp_moved = e1.id
            LEFT JOIN init__employee e2 ON mm.idEmp_accepted = e2.id
            WHERE mm.type_Mouv = 'inter_chaine'
            ORDER BY mm.date_mouvement DESC");
        $req->execute();
        $resultats = $req->fetchAll();

        return $resultats;
    }
    public static function findParcChaine()
    {
        $dbGmao = Database::getInstance('MAHDCO_MAINT');
        $conn = $dbGmao->getConnection();

        // Utiliser une requête qui spécifie explicitement les bases de données
        $query = "
        SELECT mm.*, m.reference, m.designation, rm.raison_mouv_mach as raison_mouv,
                e1.first_name as initiator_first_name, e1.last_name as initiator_last_name,
                e2.first_name as acceptor_first_name, e2.last_name as acceptor_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
        FROM MAHDCO_MAINT.gmao__mouvement_machine mm 
        INNER JOIN MAHDCO_MAINT.gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
        INNER JOIN db_mahdco.init__machine m ON mm.id_machine = m.machine_id
        LEFT JOIN db_mahdco.init__employee e1 ON mm.idEmp_moved = e1.id
        LEFT JOIN db_mahdco.init__employee e2 ON mm.idEmp_accepted = e2.id
        WHERE mm.type_Mouv = 'parc_chaine'
        ORDER BY mm.date_mouvement DESC";

        try {
            $req = $conn->query($query);
            $resultats = $req->fetchAll();
            return $resultats;
        } catch (\PDOException $e) {
            // En cas d'erreur (par exemple si les qualificateurs de bases ne fonctionnent pas)
            // Logger l'erreur et utiliser la méthode alternative
            error_log("Erreur dans findChaineParc: " . $e->getMessage());
            return self::findParcChainev0();
        }
    }
    public static function findParcChainev0()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->query("SELECT mm.*, m.reference, m.designation, rm.raison_mouv_mach as raison_mouv,
                e1.first_name as initiator_first_name, e1.last_name as initiator_last_name,
                e2.first_name as acceptor_first_name, e2.last_name as acceptor_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
            FROM gmao__mouvement_machine mm 
            INNER JOIN init__machine m ON mm.id_machine = m.machine_id
            INNER JOIN gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
            LEFT JOIN init__employee e1 ON mm.idEmp_moved = e1.id
            LEFT JOIN init__employee e2 ON mm.idEmp_accepted = e2.id
            WHERE mm.type_Mouv = 'parc_chaine'
            ORDER BY mm.date_mouvement DESC");
        $req->execute();
        $resultats = $req->fetchAll();
        return $resultats;
    }
    public static function findChaineParcv2()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->query("
    SELECT mm.*, m.reference, m.designation, rm.raison_mouv_mach as raison_mouv,
            e1.first_name as initiator_first_name, e1.last_name as initiator_last_name,
            e2.first_name as acceptor_first_name, e2.last_name as acceptor_last_name,
            CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
            CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
        FROM gmao__mouvement_machine mm 
        INNER JOIN init__machine m ON mm.id_machine = m.machine_id
        INNER JOIN gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
        LEFT JOIN init__employee e1 ON mm.idEmp_moved = e1.id
        LEFT JOIN init__employee e2 ON mm.idEmp_accepted = e2.id
        WHERE mm.type_Mouv = 'chaine_parc'
        ORDER BY mm.date_mouvement   DESC");
        $req->execute();
        $resultats = $req->fetchAll();
        return $resultats;
    }
    public static function findChaineParc()
    {
        // Obtenir une connexion à la base de données MAHDCO_MAINT 
        // (Les deux connexions pointeront vers le même serveur MySQL)
        $dbGmao = Database::getInstance('MAHDCO_MAINT');
        $conn = $dbGmao->getConnection();

        // Utiliser une requête qui spécifie explicitement les bases de données
        $query = "
        SELECT mm.*, m.reference, m.designation, rm.raison_mouv_mach as raison_mouv,
                e1.first_name as initiator_first_name, e1.last_name as initiator_last_name,
                e2.first_name as acceptor_first_name, e2.last_name as acceptor_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
        FROM MAHDCO_MAINT.gmao__mouvement_machine mm 
        INNER JOIN MAHDCO_MAINT.gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
        INNER JOIN db_mahdco.init__machine m ON mm.id_machine = m.machine_id
        LEFT JOIN db_mahdco.init__employee e1 ON mm.idEmp_moved = e1.id
        LEFT JOIN db_mahdco.init__employee e2 ON mm.idEmp_accepted = e2.id
        WHERE mm.type_Mouv = 'chaine_parc'
        ORDER BY mm.date_mouvement DESC";

        try {
            $req = $conn->query($query);
            $resultats = $req->fetchAll();
            return $resultats;
        } catch (\PDOException $e) {
            // En cas d'erreur (par exemple si les qualificateurs de bases ne fonctionnent pas)
            // Logger l'erreur et utiliser la méthode alternative
            error_log("Erreur dans findChaineParc: " . $e->getMessage());
            return self::findChaineParcv2();
        }
    }



    public static function historiqueMachine($machine_id)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->prepare("
            SELECT 
                mm.*,
                m.reference, 
                m.designation, 
                rm.raison_mouv_mach as raison_mouv,
                ms.status_name,
                e1.first_name as initiator_first_name, 
                e1.last_name as initiator_last_name,
                e2.first_name as acceptor_first_name, 
                e2.last_name as acceptor_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
            FROM 
                gmao__mouvement_machine mm 
            INNER JOIN 
                db_mahdco.init__machine m ON mm.id_machine = m.machine_id
            INNER JOIN 
                gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
            LEFT JOIN 
                db_mahdco.init__employee e1 ON mm.idEmp_moved = e1.id
            LEFT JOIN 
                db_mahdco.init__employee e2 ON mm.idEmp_accepted = e2.id
            LEFT JOIN 
                db_mahdco.gmao__machines_status ms ON m.machines_status_id = ms.id
           
            WHERE 
                mm.id_machine = :machine_id
            ORDER BY 
                mm.num_Mouv_Mach DESC
        ");
        $req->bindParam(':machine_id', $machine_id, PDO::PARAM_STR);
        $req->execute();
        $resultats = $req->fetchAll();

        return $resultats;
    }
}
