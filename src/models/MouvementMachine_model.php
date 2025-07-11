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
    public static function findChaineParc()
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
            WHERE mm.type_Mouv = 'chaine_parc'
            ORDER BY mm.date_mouvement	 DESC");
        $req->execute();
        $resultats = $req->fetchAll();
        return $resultats;
    }
    public static function findByMachine($id_machine)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->prepare("SELECT mm.*, m.reference, m.designation 
            FROM gmao__mouvement_machine mm 
            INNER JOIN init__machine m ON mm.id_machine = m.machine_id
            WHERE mm.id_machine = ?
            ORDER BY mm.date_mouvement DESC");
        $req->execute([$id_machine]);
        $resultats = $req->fetchAll();
        return $resultats;
    }

    public static function findPendingReception()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->query("SELECT mm.*, m.reference, m.designation, rm.raison_mouv_mach as raison_mouv,
                e1.first_name as initiator_first_name, e1.last_name as initiator_last_name,
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name
            FROM gmao__mouvement_machine mm 
            INNER JOIN init__machine m ON mm.id_machine = m.machine_id
            INNER JOIN gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
            LEFT JOIN init__employee e1 ON mm.idEmp = e1.matricule
            WHERE mm.type_Mouv = 'parc_chaine' AND mm.idEmp_accepted IS NULL
            ORDER BY mm.date_mouvement DESC");
        $req->execute();
        $resultats = $req->fetchAll();
        return $resultats;
    }
}
