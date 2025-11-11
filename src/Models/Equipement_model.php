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
    private $equipment_category_id;
    private $status_id;
    private $location_id;


    public function __construct($id, $equipment_id, $designation, $reference, $equipment_category_id, $status_id, $location_id)
    {
        $this->id = $id;
        $this->equipment_id = $equipment_id;
        $this->designation = $designation;
        $this->reference = $reference;
        $this->equipment_category_id = $equipment_category_id;
        $this->status_id = $status_id;
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
                FROM gmao__init_equipment e
                LEFT JOIN gmao__location l ON e.location_id = l.id
                LEFT JOIN gmao__init__category c ON e.equipment_category_id = c.id
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
        $req = $conn->query("SELECT * FROM gmao__init__category");
        $categories = $req->fetchAll();
        return $categories;
    }
    public static function AllLocations()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $locations = array();
        $req = $conn->query("SELECT * FROM gmao__location");
        $locations = $req->fetchAll();
        return $locations;
    }
    public static function AllStatus()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $status = array();
        $req = $conn->query("SELECT * FROM gmao__status");
        $status = $req->fetchAll();
        return $status;
    }
    public static function findById($id)
    {

        $db = new Database();
        $conn = $db->getConnection();
        $equipement = null;
        try {
            $req = $conn->query("SELECT e.*
              FROM gmao__init_equipment e
    
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
            $stmt = $conn->prepare("INSERT INTO gmao__init_equipment (`id`,equipment_id ,`designation`, `reference`, `equipment_category_id`, `location_id`, `status_id`) VALUES (?,?, ?, ?, ?, ?, ?)");
            $stmt->bindParam(1, $equipement->id);
            $stmt->bindParam(2, $equipement->equipment_id);
            $stmt->bindParam(3, $equipement->designation);
            $stmt->bindParam(4, $equipement->reference);
            $stmt->bindParam(5, $equipement->equipment_category_id);
            $stmt->bindParam(6, $equipement->location_id);
            $stmt->bindParam(7, $equipement->status_id);

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
            $stmt = $conn->prepare("UPDATE gmao__init_equipment SET designation = ?, reference = ?, equipment_category_id = ?, location_id = ?, status_id = ? WHERE id = '$equipement->id'");
            $stmt->bindParam(1, $equipement->designation);
            $stmt->bindParam(2, $equipement->reference);
            $stmt->bindParam(3, $equipement->equipment_category_id);
            $stmt->bindParam(4, $equipement->location_id);
            $stmt->bindParam(5, $equipement->status_id);
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
            $stmt = $conn->prepare("DELETE FROM gmao__init_equipment WHERE id = ?");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {

            return false;
        }
    }

    /**
     * Vérifie l'existence d'un équipement par equipment_id
     */
    public static function existsByEquipmentId($equipmentId, $excludeId = null)
    {
        if ($equipmentId === null || $equipmentId === '') {
            return false;
        }
        $db = new Database();
        $conn = $db->getConnection();
        try {
            if ($excludeId) {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__init_equipment WHERE equipment_id = ? AND id <> ? LIMIT 1");
                $stmt->bindParam(1, $equipmentId);
                $stmt->bindParam(2, $excludeId);
            } else {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__init_equipment WHERE equipment_id = ? LIMIT 1");
                $stmt->bindParam(1, $equipmentId);
            }
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Vérifie l'existence d'un équipement par référence
     */
    public static function existsByReference($reference, $excludeId = null)
    {
        if ($reference === null || $reference === '') {
            return false;
        }
        $db = new Database();
        $conn = $db->getConnection();
        try {
            if ($excludeId) {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__init_equipment WHERE reference = ? AND id <> ? LIMIT 1");
                $stmt->bindParam(1, $reference);
                $stmt->bindParam(2, $excludeId);
            } else {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__init_equipment WHERE reference = ? LIMIT 1");
                $stmt->bindParam(1, $reference);
            }
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
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
            $stmt = $conn->prepare("SELECT * FROM gmao__init_equipment WHERE LOWER(equipment_category) = LOWER(?) ORDER BY designation");
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
        try {
            $req = $conn->query("
            
                SELECT 
                    gie.*, 
                    ec.status_name AS etat_equipement,
                    ie.*, 
                    im.machine_id AS machine_id,
                    il.location_name AS location_name,
                    il.location_category AS location_category
                FROM gmao__init_equipment gie
                
                LEFT JOIN (
                    SELECT accessory_ref, MAX(id) AS max_id
                    FROM gmao__prod_implementation_equipment
                    GROUP BY accessory_ref
                ) latest 
                    ON gie.equipment_id = latest.accessory_ref
                
                LEFT JOIN gmao__prod_implementation_equipment ie 
                    ON ie.id = latest.max_id
                
                -- Machine liée à l’implémentation
                LEFT JOIN init__machine im 
                    ON ie.machine_id = im.machine_id
                
                -- Localisation de l’équipement
                LEFT JOIN gmao__location il 
                    ON gie.location_id = il.id
                
                -- Statut de l’équipement
                LEFT JOIN gmao__status ec 
                    ON gie.status_id = ec.id
            ");
            $equipements = $req->fetchAll();
            return $equipements;
        } catch (PDOException $e) {
            return [];
        }
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
               im.reference AS machine_reference,

               il.location_name AS location_name,
               concat(em.first_name, ' ', em.last_name) as Responsable
               
              
        FROM gmao__prod_implementation_equipment pa
        LEFT JOIN (
            SELECT accessory_ref, MAX(id) AS max_id
            FROM gmao__prod_implementation_equipment
            GROUP BY accessory_ref
        ) latest ON pa.accessory_ref = latest.accessory_ref AND pa.id = latest.max_id
        LEFT JOIN gmao__init_equipment ie ON pa.accessory_ref = ie.equipment_id
        LEFT JOIN init__machine im ON pa.machine_id = im.machine_id
        LEFT JOIN gmao__location il ON ie.location_id = il.id
        left join init__employee em on pa.maintainer = em.matricule
        WHERE latest.max_id IS NOT NULL AND pa.is_removed = 0
    ");
        $equipements = $req->fetchAll();
        return $equipements;
    }
    //etat de l'equipement
    public static function getEquipementStatus()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT * FROM gmao__status");
        $etat_equipement = $req->fetchAll();
        return $etat_equipement;
    }

    public static function locationID($location)
    {

        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT id FROM  gmao__location WHERE location_category = '$location'");
        $location_id = $req->fetchAll();

        return $location_id;
    }


    public static function getEquipements($location)
    {
        $db = new Database();
        $conn = $db->getConnection();

        // Récupérer tous les IDs de location
        $locations = self::locationID("$location");

        // Extraire tous les IDs dans un tableau
        $location_ids = array_column($locations, 'id');

        // Transformer le tableau en chaîne pour SQL IN
        $ids_str = implode(',', $location_ids);

        // Requête pour tous les IDs
        $req = $conn->query("SELECT * FROM gmao__init_equipment WHERE location_id IN ($ids_str)");

        $equipements = $req->fetchAll();

        return $equipements;
    }

    public static function equipmentByMachine_id($machine_id)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
        SELECT pa.*, ie.designation, ie.reference
        FROM gmao__prod_implementation_equipment pa
        INNER JOIN (
            SELECT accessory_ref, MAX(id) AS max_id
            FROM gmao__prod_implementation_equipment
            GROUP BY accessory_ref
        ) latest ON pa.id = latest.max_id
        LEFT JOIN gmao__init_equipment ie ON pa.accessory_ref = ie.equipment_id
        WHERE pa.machine_id LIKE ? AND pa.is_removed = 0
    ");
        $stmt->execute(["%$machine_id%"]);


        $machine_equipement = $stmt->fetchAll();
        return $machine_equipement;
    }

    /**
     * Récupère un équipement par son equipment_id avec détails de statut et localisation
     */
    public static function findByEquipmentIdWithDetails($equipmentId)
    {
        if ($equipmentId === null || $equipmentId === '') {
            return null;
        }
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("SELECT e.*, il.location_name, ec.status_name, il.location_category FROM gmao__init_equipment e 
            LEFT JOIN gmao__location il ON e.location_id = il.id 
            LEFT JOIN gmao__status ec ON e.status_id = ec.id 
            WHERE e.equipment_id = ? LIMIT 1");
            $stmt->bindParam(1, $equipmentId);
            $stmt->execute();
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
}
