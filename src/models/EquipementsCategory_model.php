<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class EquipementsCategory_model
{
    private $id;
    private $category_name ;
    private $category_type;


    public function __construct($id, $category_name, $category_type)
    {
        $this->id = $id;
        $this->category_name = $category_name;
        $this->category_type = $category_type;
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


   
    public static function AllCategorie()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $categories = array();
        $req = $conn->query("SELECT * FROM init__categoris");
        $categories = $req->fetchAll();
        return $categories;
    }
  

    public static function StoreCategorie($categorie)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("INSERT INTO init__categoris (`id`,category_name ,`category_type`) VALUES (?,?, ?)");
            $stmt->bindParam(1, $categorie->id);
            $stmt->bindParam(2,$categorie->category_name);
            $stmt->bindParam(3, $categorie->category_type);


            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    public static function UpdateCategorie($categorie)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("UPDATE init__categoris SET category_name = ?, category_type = ? WHERE id = '$categorie->id'");
            $stmt->bindParam(1, $categorie->category_name);
            $stmt->bindParam(2, $categorie->category_type);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function findById($id)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $categorie = array();
        $req = $conn->query("SELECT * FROM init__categoris WHERE id = '$id'");
        $categorie = $req->fetch();
        return $categorie;
    }

    public static function deleteById($id)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("DELETE FROM init__categoris WHERE id = ?");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {

            return false;
        }
    }

    /**
     * Vérifie si un nom de catégorie existe déjà.
     * Si $excludeId est fourni, exclut cet enregistrement (utile pour l'édition).
     */
    public static function existsByCategoryName($categoryName, $excludeId = null)
    {
        if ($categoryName === null || $categoryName === '') {
            return false;
        }
        $db = new Database();
        $conn = $db->getConnection();
        try {
            if ($excludeId) {
                $stmt = $conn->prepare("SELECT 1 FROM init__categoris WHERE category_name = ? AND id <> ? LIMIT 1");
                $stmt->bindParam(1, $categoryName);
                $stmt->bindParam(2, $excludeId);
            } else {
                $stmt = $conn->prepare("SELECT 1 FROM init__categoris WHERE category_name = ? LIMIT 1");
                $stmt->bindParam(1, $categoryName);
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
            $stmt = $conn->prepare("SELECT * FROM init__categoris WHERE LOWER(category_type) = LOWER(?) ORDER BY category_name");
            $stmt->bindParam(1, $type);
            $stmt->execute();
            $equipements = $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }

        return $equipements;
    }
}
