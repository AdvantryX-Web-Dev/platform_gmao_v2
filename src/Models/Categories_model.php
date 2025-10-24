<?php
namespace App\Models;
use App\Models\Database;
use PDOException;

class Categories_model
{
    private $id_Raison;
    private $raison_mouv_mach;
    private $typeR;
 
    public function __construct($id_Raison, $raison_mouv_mach, $typeR)
    {
        $this->id_Raison = $id_Raison;
        $this->raison_mouv_mach = $raison_mouv_mach;
        $this->typeR = $typeR;

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
            $req = $conn->query("SELECT * FROM gmao__raison_mouv_mach ");
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
                $req = $conn->query("SELECT * FROM gmao__raison_mouv_mach WHERE id_Raison='$id'");
            $category = $req->fetch();
        } catch (PDOException $e) {
          
            return false;
        }
        return $category;
    }
    public static function StoreCategory($category)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("INSERT INTO gmao__raison_mouv_mach (`id_Raison`, `raison_mouv_mach`, `typeR`) VALUES (?, ?, ?)");
            $stmt->bindParam(1, $category->id_Raison);
            $stmt->bindParam(2, $category->raison_mouv_mach);
            $stmt->bindParam(3, $category->typeR);
            

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    public static function UpdateCategory($category)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("UPDATE gmao__raison_mouv_mach SET raison_mouv_mach = ?, typeR = ? WHERE id_Raison = '$category->id_Raison'");
            $stmt->bindParam(1, $category->raison_mouv_mach);
            $stmt->bindParam(2, $category->typeR);
           
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Vérifie si un motif existe déjà par son libellé raison_mouv_mach.
     * Si $excludeId est fourni, exclut cet enregistrement (utile pour l'édition).
     */
    public static function existsByRaison($raison, $excludeId = null)
    {
        if ($raison === null || $raison === '') {
            return false;
        }
        $db = new Database();
        $conn = $db->getConnection();
        try {
            if ($excludeId) {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__raison_mouv_mach WHERE raison_mouv_mach = ? AND id_Raison <> ? LIMIT 1");
                $stmt->bindParam(1, $raison);
                $stmt->bindParam(2, $excludeId);
            } else {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__raison_mouv_mach WHERE raison_mouv_mach = ? LIMIT 1");
                $stmt->bindParam(1, $raison);
            }
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function deleteById($id) {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("DELETE FROM gmao__raison_mouv_mach WHERE id_Raison = ?");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            
            return false;
        }
    }
}
