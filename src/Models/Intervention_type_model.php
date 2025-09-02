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
            $req = $conn->query("
            SELECT * FROM gmao__type_intervention order by created_at desc");
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
            $req = $conn->query("SELECT * FROM gmao__type_intervention WHERE id='$id'");
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
            $stmt = $conn->prepare("INSERT INTO gmao__type_intervention (`id`, `designation`, `type`, `code`) VALUES (?, ?, ?, ?)");
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
            $stmt = $conn->prepare("UPDATE gmao__type_intervention SET designation = ?, type = ?, code = ? WHERE id = '$intervention_type->id'");
            $stmt->bindParam(1, $intervention_type->designation);
            $stmt->bindParam(2, $intervention_type->type);
            $stmt->bindParam(3, $intervention_type->code);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Vérifie si un code d'intervention existe déjà.
     * Si $excludeId est fourni, exclut cet enregistrement (utile pour l'édition).
     */
    public static function existsByCode($code, $excludeId = null)
    {
        if ($code === null || $code === '') {
            return false;
        }
        $db = new Database();
        $conn = $db->getConnection();
        try {
            if ($excludeId) {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__type_intervention WHERE code = ? AND id <> ? LIMIT 1");
                $stmt->bindParam(1, $code);
                $stmt->bindParam(2, $excludeId);
            } else {
                $stmt = $conn->prepare("SELECT 1 FROM gmao__type_intervention WHERE code = ? LIMIT 1");
                $stmt->bindParam(1, $code);
            }
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }




    public static function deleteById($id)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("DELETE FROM gmao__type_intervention WHERE id = ?");
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
        $types = array();

        try {
            $stmt = $conn->prepare("SELECT * FROM gmao__type_intervention WHERE LOWER(type) = LOWER(?) ORDER BY designation");
            $stmt->bindParam(1, $type);
            $stmt->execute();
            $types = $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }

        return $types;
    }
}
