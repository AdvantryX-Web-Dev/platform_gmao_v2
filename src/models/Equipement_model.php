<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class Equipement_model
{
    private $id;
    private $equipment_id ;
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
                LEFT JOIN init__categoris c ON e.equipment_category = c.id");
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
    public static function AllLocations(){
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
            $req = $conn->query("SELECT * FROM init__equipement WHERE id='$id'");
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
            $stmt->bindParam(2,$equipement->equipment_id);
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
}
