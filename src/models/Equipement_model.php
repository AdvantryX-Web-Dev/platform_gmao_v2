<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class Equipement_model
{
    private $id;
    private $equipment_id;
    private $designation;
    private $reference;
    private $equipment_category;
    private $location_id;


    public function __construct($id, $equipment_id, $designation, $reference, $equipment_category, $location_id)
    {
        $this->id = $id;
        $this->equipment_id = $equipment_id;
        $this->designation = $designation;
        $this->reference = $reference;
        $this->equipment_category = $equipment_category;
        $this->location_id = $location_id;
    }

    public function __get($attr)
    {
        if (!isset($this->$attr))
            return "erreur";
        else
            return ($this->$attr);
    }

    public function __set($attr, $value)
    {
        $this->$attr = $value;
    }


    public static function findAll()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $equipements = array();
        try {
            $req = $conn->query("SELECT e.*, l.location_name AS location, c.category_name AS categorie
                FROM init__equipement e
                LEFT JOIN init__location l ON e.location_id = l.id
                LEFT JOIN init__categoris c ON e.equipment_category = c.id
                ORDER BY e.equipment_id");
            $equipements = $req->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
        return $equipements;
    }
    public static function AllCategorie()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $categories = array();
        $req = $conn->query("SELECT * FROM init__categoris");
        $categories = $req->fetchAll();
        return $categories;
    }
    public static function AllLocations()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $locations = array();
        $req = $conn->query("SELECT * FROM init__location");
        $locations = $req->fetchAll();
        return $locations;
    }

    public static function findById($id)
    {

        $db = new Database();
        $conn = $db->getConnection();
        $equipement = null;
        try {
            $req = $conn->query("SELECT e.*
              FROM init__equipement e
    
            WHERE id='$id'
            ");
            $equipement = $req->fetch();
        } catch (PDOException $e) {

            return false;
        }
        return $equipement;
    }
    public static function StoreEquipement($equipement)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("INSERT INTO init__equipement (`id`,equipment_id ,`designation`, `reference`, `equipment_category`, `location_id`) VALUES (?,?, ?, ?, ?, ?)");
            $stmt->bindParam(1, $equipement->id);
            $stmt->bindParam(2, $equipement->equipment_id);
            $stmt->bindParam(3, $equipement->designation);
            $stmt->bindParam(4, $equipement->reference);
            $stmt->bindParam(5, $equipement->equipment_category);
            $stmt->bindParam(6, $equipement->location_id);


            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    public static function UpdateEquipement($equipement)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("UPDATE init__equipement SET designation = ?, reference = ?, equipment_category = ?, location_id = ? WHERE id = '$equipement->id'");
            $stmt->bindParam(1, $equipement->designation);
            $stmt->bindParam(2, $equipement->reference);
            $stmt->bindParam(3, $equipement->equipment_category);
            $stmt->bindParam(4, $equipement->location_id);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }




    public static function deleteById($id)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("DELETE FROM init__equipement WHERE id = ?");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {

            return false;
        }
    }

    /**
     * Find intervention types by type (preventive/curative)
     * 
     * @param string $type Type of intervention ('preventive' or 'curative')
     * @return array Array of intervention types
     */
    public static function findByType($type)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $equipements = array();

        try {
            $stmt = $conn->prepare("SELECT * FROM init__equipement WHERE LOWER(equipment_category) = LOWER(?) ORDER BY designation");
            $stmt->bindParam(1, $type);
            $stmt->execute();
            $equipements = $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }

        return $equipements;
    }
    public static function equipements_state()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->query("
            SELECT pa.*, 
                   ie.id AS id,
                   ie.equipment_id AS equipment_id,
                   ie.reference AS reference,
                   ec.etat AS etat_equipement,
                   im.machine_id AS machine_id,
                   il.location_name AS location_name
            FROM prod__accessories pa
            LEFT JOIN (
                SELECT accessory_ref, MAX(id) AS max_id
                FROM prod__accessories
                GROUP BY accessory_ref
            ) latest ON pa.accessory_ref = latest.accessory_ref AND pa.id = latest.max_id
            LEFT JOIN init__equipement ie ON pa.accessory_ref = ie.equipment_id
            LEFT JOIN db_mahdco.init__machine im ON pa.machine_id = im.machine_id
            LEFT JOIN init__location il ON ie.location_id = il.id
            left join gmao_etat_equipement ec on ie.etat_equipment_id = ec.id
            WHERE latest.max_id IS NOT NULL
        ");

        $equipements = $req->fetchAll();
        return $equipements;
    }
    public static function affectationEquipementsMachines()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("
        SELECT pa.*, 
               ie.equipment_id AS equipment_id,
               ie.reference AS reference,
               im.machine_id AS machine_id,
               il.location_name AS location_name,
               concat(em.first_name, ' ', em.last_name) as Responsable
               
              
        FROM prod__accessories pa
        LEFT JOIN (
            SELECT accessory_ref, MAX(id) AS max_id
            FROM prod__accessories
            GROUP BY accessory_ref
        ) latest ON pa.accessory_ref = latest.accessory_ref AND pa.id = latest.max_id
        LEFT JOIN init__equipement ie ON pa.accessory_ref = ie.equipment_id
        LEFT JOIN db_mahdco.init__machine im ON pa.machine_id = im.machine_id
        LEFT JOIN init__location il ON ie.location_id = il.id
        left join db_mahdco.init__employee em on pa.maintainer = em.matricule
        WHERE latest.max_id IS NOT NULL
    ");
        $equipements = $req->fetchAll();
        return $equipements;
    }
    //etat de l'equipement
    public static function getEquipementStatus()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT * FROM gmao_etat_equipement");
        $etat_equipement = $req->fetchAll();
        return $etat_equipement;
    }

    public static function locationID($location)
    {

        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT id FROM  init__location WHERE location_name = '$location'");
        $location_id = $req->fetchAll();

        return $location_id;
    }

    public static function getEquipements($location)
    {

        $db = new Database();
        $conn = $db->getConnection();
        $location = self::locationID("$location");
        $location_id = $location[0]['id'];
        $req = $conn->query("SELECT * FROM init__equipement WHERE location_id = '$location_id'");
        $equipements = $req->fetchAll();
        return $equipements;
    }

    public static function equipmentByMachine_id($machine_id)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
        SELECT pa.*, ie.designation, ie.reference
        FROM prod__accessories pa
        INNER JOIN (
            SELECT accessory_ref, MAX(id) AS max_id
            FROM prod__accessories
            GROUP BY accessory_ref
        ) latest ON pa.id = latest.max_id
        LEFT JOIN init__equipement ie ON pa.accessory_ref = ie.equipment_id
        WHERE pa.machine_id LIKE ? AND pa.is_removed = 0
    ");
        $stmt->execute(["%$machine_id%"]);


        $machine_equipement = $stmt->fetchAll();
        return $machine_equipement;
    }
}
