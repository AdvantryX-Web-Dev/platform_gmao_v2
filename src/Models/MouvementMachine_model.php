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
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
            $userId = $_SESSION['user']['id'] ?? null;

            $dbGmao = Database::getInstance('db_digitex');
            $conn = $dbGmao->getConnection();

            $query = "
                SELECT 
                    mm.*, 
                    m.reference, 
                    m.designation, 
                    rm.raison_mouv_mach AS raison_mouv,
                    e1.first_name AS initiator_first_name, 
                    e1.last_name AS initiator_last_name,
                    e2.first_name AS acceptor_first_name, 
                    e2.last_name AS acceptor_last_name,
                    CONCAT(e1.first_name, ' ', e1.last_name) AS emp_initiator_name,
                    CONCAT(e2.first_name, ' ', e2.last_name) AS emp_acceptor_name,
                    (
                        SELECT COUNT(*) 
                        FROM gmao__mouvement_machine mm2 
                        WHERE mm2.status IS NULL 
                        AND mm2.type_Mouv = 'inter_chaine' 
                        AND (
                            mm2.idEmp_moved = :user_id_moved_sub 
                            OR
                            mm2.idEmp_accepted = :user_id_accepted_sub
                        )
                    ) AS count_machine           
                FROM gmao__mouvement_machine mm
                INNER JOIN gmao__raison_mouv_mach rm 
                    ON mm.id_Rais = rm.id_Raison
                INNER JOIN init__machine m 
                    ON mm.id_machine = m.machine_id
                LEFT JOIN init__employee e1 
                    ON mm.idEmp_moved = e1.id
                LEFT JOIN init__employee e2 
                    ON mm.idEmp_accepted = e2.id
                WHERE mm.type_Mouv = 'inter_chaine'
            ";


            if (!$isAdmin && $userId) {
                $query .= " AND (mm.idEmp_moved = :user_id_moved_main OR mm.idEmp_accepted = :user_id_accepted_main)";
            }
            $query .= " ORDER BY mm.date_mouvement DESC";

            $req = $conn->prepare($query);

            if (!$isAdmin && $userId) {

                $req->bindValue(':user_id_moved_main', $userId, PDO::PARAM_INT);
                $req->bindValue(':user_id_accepted_main', $userId, PDO::PARAM_INT);
            }
            $req->bindValue(':user_id_moved_sub', $userId, PDO::PARAM_INT);
            $req->bindValue(':user_id_accepted_sub', $userId, PDO::PARAM_INT);


            $req->execute();
            return $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans findInterChaine : " . $e->getMessage());
            echo "Erreur SQL : " . $e->getMessage();
            return [];
        }
    }



    public static function findParcChaine()
    {

        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
            $userId = $_SESSION['user']['id'] ?? null;

            $dbGmao = Database::getInstance('db_digitex');
            $conn = $dbGmao->getConnection();

            $query = "
                SELECT 
                    mm.*, 
                    m.reference, 
                    m.designation, 
                    rm.raison_mouv_mach AS raison_mouv,
                    e1.first_name AS initiator_first_name, 
                    e1.last_name AS initiator_last_name,
                    e2.first_name AS acceptor_first_name, 
                    e2.last_name AS acceptor_last_name,
                    CONCAT(e1.first_name, ' ', e1.last_name) AS emp_initiator_name,
                    CONCAT(e2.first_name, ' ', e2.last_name) AS emp_acceptor_name,
                    (
                        SELECT COUNT(*) 
                        FROM gmao__mouvement_machine mm2 
                        WHERE mm2.status IS NULL 
                        AND mm2.type_Mouv = 'parc_chaine' 
                        AND (
                            mm2.idEmp_moved = :user_id_moved_sub 
                            OR
                            mm2.idEmp_accepted = :user_id_accepted_sub
                        )
                    ) AS count_machine           
                FROM gmao__mouvement_machine mm
                INNER JOIN gmao__raison_mouv_mach rm 
                    ON mm.id_Rais = rm.id_Raison
                INNER JOIN init__machine m 
                    ON mm.id_machine = m.machine_id
                LEFT JOIN init__employee e1 
                    ON mm.idEmp_moved = e1.id
                LEFT JOIN init__employee e2 
                    ON mm.idEmp_accepted = e2.id
                        WHERE mm.type_Mouv = 'parc_chaine'
            ";


            if (!$isAdmin && $userId) {
                $query .= " AND (mm.idEmp_moved = :user_id_moved_main OR mm.idEmp_accepted = :user_id_accepted_main)";
            }
            $query .= " ORDER BY mm.date_mouvement DESC";

            $req = $conn->prepare($query);

            if (!$isAdmin && $userId) {

                $req->bindValue(':user_id_moved_main', $userId, PDO::PARAM_INT);
                $req->bindValue(':user_id_accepted_main', $userId, PDO::PARAM_INT);
            }
            $req->bindValue(':user_id_moved_sub', $userId, PDO::PARAM_INT);
            $req->bindValue(':user_id_accepted_sub', $userId, PDO::PARAM_INT);


            $req->execute();
            return $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans findParcChaine : " . $e->getMessage());
            echo "Erreur SQL : " . $e->getMessage();
            return [];
        }
    }



    public static function findChaineParc()
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
            $userId = $_SESSION['user']['id'] ?? null;

            $dbGmao = Database::getInstance('db_digitex');
            $conn = $dbGmao->getConnection();

            $query = "
                SELECT 
                    mm.*, 
                    m.reference, 
                    m.designation, 
                    rm.raison_mouv_mach AS raison_mouv,
                    e1.first_name AS initiator_first_name, 
                    e1.last_name AS initiator_last_name,
                    e2.first_name AS acceptor_first_name, 
                    e2.last_name AS acceptor_last_name,
                    CONCAT(e1.first_name, ' ', e1.last_name) AS emp_initiator_name,
                    CONCAT(e2.first_name, ' ', e2.last_name) AS emp_acceptor_name,
                    (
                        SELECT COUNT(*) 
                        FROM gmao__mouvement_machine mm2 
                        WHERE mm2.status IS NULL 
                        AND mm2.type_Mouv = 'chaine_parc' 
                        AND (
                            mm2.idEmp_moved = :user_id_moved_sub 
                            OR
                            mm2.idEmp_accepted = :user_id_accepted_sub
                        )
                    ) AS count_machine           
                FROM gmao__mouvement_machine mm
                INNER JOIN gmao__raison_mouv_mach rm 
                    ON mm.id_Rais = rm.id_Raison
                INNER JOIN init__machine m 
                    ON mm.id_machine = m.machine_id
                LEFT JOIN init__employee e1 
                    ON mm.idEmp_moved = e1.id
                LEFT JOIN init__employee e2 
                    ON mm.idEmp_accepted = e2.id
                WHERE mm.type_Mouv = 'chaine_parc'
            ";


            if (!$isAdmin && $userId) {
                $query .= " AND (mm.idEmp_moved = :user_id_moved_main OR mm.idEmp_accepted = :user_id_accepted_main)";
            }
            $query .= " ORDER BY mm.date_mouvement DESC";

            $req = $conn->prepare($query);

            if (!$isAdmin && $userId) {

                $req->bindValue(':user_id_moved_main', $userId, PDO::PARAM_INT);
                $req->bindValue(':user_id_accepted_main', $userId, PDO::PARAM_INT);
            }
            $req->bindValue(':user_id_moved_sub', $userId, PDO::PARAM_INT);
            $req->bindValue(':user_id_accepted_sub', $userId, PDO::PARAM_INT);


            $req->execute();
            return $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Erreur dans findChaineParc : " . $e->getMessage());
            echo "Erreur SQL : " . $e->getMessage();
            return [];
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
                CONCAT(e1.first_name, ' ', e1.last_name) as emp_initiator_name,
                CONCAT(e2.first_name, ' ', e2.last_name) as emp_acceptor_name
            FROM gmao__mouvement_machine mm 
            INNER JOIN db_mahdco.init__machine m ON mm.id_machine = m.machine_id
            INNER JOIN gmao__raison_mouv_mach rm ON mm.id_Rais = rm.id_Raison
            LEFT JOIN db_mahdco.init__employee e1 ON mm.idEmp_moved = e1.id
            LEFT JOIN db_mahdco.init__employee e2 ON mm.idEmp_accepted = e2.id
            LEFT JOIN db_mahdco.gmao__status ms ON m.machines_status_id = ms.id
            WHERE mm.id_machine = :machine_id
            ORDER BY mm.date_mouvement DESC, mm.created_at DESC
        ");
        $req->bindParam(':machine_id', $machine_id, PDO::PARAM_STR);
        $req->execute();
        $resultats = $req->fetchAll();

        return $resultats;
    }
}
