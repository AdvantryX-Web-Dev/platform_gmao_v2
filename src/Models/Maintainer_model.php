<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class Maintainer_model
{
    private $id;
    private $card_rfid;
    private $matricule;
    private $first_name;
    private $last_name;
    private $qualification;

    public function __construct($id, $card_rfid, $matricule, $first_name, $last_name, $qualification)
    {
        $this->id = $id;
        $this->card_rfid = $card_rfid;
        $this->matricule = $matricule;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->qualification = $qualification;
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
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $maintainers = array();
        try {
            $req = $conn->query("SELECT * FROM init__employee WHERE qualification = 'MAINTAINER'  order by matricule desc");
            $maintainers = $req->fetchAll();
        } catch (PDOException $e) {

            return false;
        }
        return $maintainers;
    }

    public static function findById($id)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $maintainer = null;
        try {
            $req = $conn->query("SELECT * FROM init__employee WHERE id='$id'");
            $maintainer = $req->fetch();
        } catch (PDOException $e) {

            return false;
        }
        return $maintainer;
    }
    public static function StoreMaintainer($maintainer)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("INSERT INTO init__employee (`id`, `card_rfid`, `matricule`, `first_name`, `last_name`, `qualification`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bindParam(1, $maintainer->id);
            $stmt->bindParam(2, $maintainer->card_rfid);
            $stmt->bindParam(3, $maintainer->matricule);
            $stmt->bindParam(4, $maintainer->first_name);
            $stmt->bindParam(5, $maintainer->last_name);
            $stmt->bindParam(6, $maintainer->qualification);


            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    public static function UpdateMaintainer($maintainer)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("UPDATE init__employee SET card_rfid = ?, matricule = ?, first_name = ?, last_name = ?, qualification = ? WHERE id = '$maintainer->id'");
            $stmt->bindParam(1, $maintainer->card_rfid);
            $stmt->bindParam(2, $maintainer->matricule);
            $stmt->bindParam(3, $maintainer->first_name);
            $stmt->bindParam(4, $maintainer->last_name);
            $stmt->bindParam(5, $maintainer->qualification);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Vérifie si un matricule existe déjà.
     * Si $excludeId est fourni, cette ligne est exclue (utile pour l'édition).
     */
    public static function existsByMatricule($matricule, $excludeId = null)
    {
        if ($matricule === null || $matricule === '') {
            return false;
        }
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            if ($excludeId) {
                $stmt = $conn->prepare("SELECT 1 FROM init__employee WHERE matricule = ? AND id <> ? LIMIT 1");
                $stmt->bindParam(1, $matricule);
                $stmt->bindParam(2, $excludeId);
            } else {
                $stmt = $conn->prepare("SELECT 1 FROM init__employee WHERE matricule = ? LIMIT 1");
                $stmt->bindParam(1, $matricule);
            }
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }




    public static function deleteById($id)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("DELETE FROM init__employee WHERE id = ?");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {

            return false;
        }
    }
}
