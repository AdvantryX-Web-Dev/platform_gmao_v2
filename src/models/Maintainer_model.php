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
        $db = new Database();
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
            $db = new Database();
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
        $db = new Database();
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
        $db = new Database();
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

   
  

    public static function deleteById($id) {
        $db = new Database();
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
