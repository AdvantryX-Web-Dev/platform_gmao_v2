<?php
namespace App\Models;
use App\Models\Database;
use PDOException;

class Intervention_type_model
{
    private $id;
    private $designation;
    private $type;
    private $code;
 
    public function __construct($id, $designation, $type, $code)
    {
        $this->id = $id;
        $this->designation = $designation;
        $this->type = $type;
        $this->code = $code;

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
        $categories = array();
        try {
            $req = $conn->query("SELECT * FROM gmao_type_intervention");
            $categories = $req->fetchAll();
        } catch (PDOException $e) {
            
            return false;
        }
        return $categories;
    }

    public static function findById($id)
        {
            $db = new Database();
            $conn = $db->getConnection();
            $category = null;
            try {
                $req = $conn->query("SELECT * FROM gmao_type_intervention WHERE id='$id'");
            $category = $req->fetch();
        } catch (PDOException $e) {
          
            return false;
        }
        return $category;
    }
    public static function StoreInterventionType($intervention_type)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("INSERT INTO gmao_type_intervention (`id`, `designation`, `type`, `code`) VALUES (?, ?, ?, ?)");
            $stmt->bindParam(1, $intervention_type->id);
            $stmt->bindParam(2, $intervention_type->designation);
            $stmt->bindParam(3, $intervention_type->type);
            $stmt->bindParam(4, $intervention_type->code);
            

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    public static function UpdateInterventionType($intervention_type)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("UPDATE gmao_type_intervention SET designation = ?, type = ?, code = ? WHERE id = '$intervention_type->id'");
            $stmt->bindParam(1, $intervention_type->designation);
            $stmt->bindParam(2, $intervention_type->type);
            $stmt->bindParam(3, $intervention_type->code);
           
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

   
  

    public static function deleteById($id) {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("DELETE FROM gmao_type_intervention WHERE id = ?");
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
    public static function findByType($type) {
        $db = new Database();
        $conn = $db->getConnection();
        $types = array();
        
        try {
            $stmt = $conn->prepare("SELECT * FROM gmao_type_intervention WHERE LOWER(type) = LOWER(?) ORDER BY designation");
            $stmt->bindParam(1, $type);
            $stmt->execute();
            $types = $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
        
        return $types;
    }
}
