<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class Machine_model
{
    private $machine_id;
    private $designation;
    private $reference;
    private $type;
    private $brand;
    private $billing_num;
    private $bill_date;
    private $price;
    private $dateImportation;

    public function __construct($machine_id, $designation, $reference, $type, $brand, $billing_num, $bill_date)
    {
        $this->machine_id = $machine_id;
        $this->designation = $designation;
        $this->reference = $reference;
        $this->type = $type;
        $this->brand = $brand;
        $this->billing_num = $billing_num;
        $this->bill_date = $bill_date;
        // $this->price = $price;

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
        $machines = array();
        try {
            $req = $conn->query("SELECT * FROM init__machine m  LEFT JOIN `gmao__numTete` gn on gn.id_machine=m.machine_id");
            $machines = $req->fetchAll();
        } catch (PDOException $e) {
            // Gérer les erreurs de requête SQL
            // error_log('Erreur: ' . $e->getMessage());
            return false;
        }
        return $machines;
    }

    public static function findById($id_machine)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $machine = null;
        try {
            $req = $conn->query("SELECT * FROM init__machine m LEFT JOIN `gmao__numTete` gn on gn.id_machine=m.machine_id WHERE machine_id='$id_machine'");
            $machine = $req->fetch();
        } catch (PDOException $e) {
            // Gérer les erreurs de requête SQL
            // error_log('Erreur: ' . $e->getMessage());
            return false;
        }
        return $machine;
    }

    public static function ModifierMachine($machine)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            // $prix_formate = number_format($machine->price, 3, ',', '.');
            $stmt = $conn->prepare("UPDATE init__machine SET reference = ?, designation = ?, brand = ?, type = ?, billing_num = ?, bill_date = ?, cur_date=NOW() WHERE machine_id = ?");
            $stmt->bindParam(1, $machine->reference);
            $stmt->bindParam(2, $machine->designation);
            $stmt->bindParam(3, $machine->brand);
            $stmt->bindParam(4, $machine->type);
            $stmt->bindParam(5, $machine->billing_num);
            $stmt->bindParam(6, $machine->bill_date);
            // $stmt->bindParam(7, $prix_formate);
            $stmt->bindParam(7, $machine->machine_id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Gérer les erreurs de requête SQL
            // error_log('Erreur: ' . $e->getMessage());
            return false;
        }
    }

    public static function AjouterMachine($machine)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            // $prix_formate = number_format($machine->price, 3, ',', '.');
            $stmt = $conn->prepare("INSERT INTO init__machine (`machine_id`, `reference`, `brand`, `type`, `designation`, `billing_num`, `bill_date`, `cur_date`) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bindParam(1, $machine->machine_id);
            $stmt->bindParam(2, $machine->reference);
            $stmt->bindParam(3, $machine->brand);
            $stmt->bindParam(4, $machine->type);
            $stmt->bindParam(5, $machine->designation);
            $stmt->bindParam(6, $machine->billing_num);
            $stmt->bindParam(7, $machine->bill_date);
            // $stmt->bindParam(8, $prix_formate);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Gérer les erreurs de requête SQL
            // error_log('Erreur: ' . $e->getMessage());
            return false;
        }
    }
    public static function findBytype($type)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $machines = array();
        try {
            $req = $conn->query("SELECT * FROM init__machine where designation='$type'");
            $machines = $req->fetchAll();
        } catch (PDOException $e) {
            // Gérer les erreurs de requête SQL
            // error_log('Erreur: ' . $e->getMessage());
            return false;
        }
        return $machines;
    }
    public static function findTypeByMachine($id_machine)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $machines = array();
        try {
            $req = $conn->query("SELECT type FROM init__machine  where machine_id='$id_machine'");
            $type = $req->fetchColumn();
        } catch (PDOException $e) {
            // Gérer les erreurs de requête SQL
            // error_log('Erreur: ' . $e->getMessage());
            return false;
        }
        return $type;
    }
    public static function deleteById($id)
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("DELETE FROM init__machine WHERE machine_id = ?");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            // Gérer les erreurs de requête SQL
            // error_log('Erreur: ' . $e->getMessage());
            return false;
        }
    }

    public static function findAllTypes()
    {
        $db = new Database();
        $conn = $db->getConnection();
        try {
            $req = $conn->query("SELECT DISTINCT type FROM init__machine ORDER BY type");
            return $req->fetchAll();
        } catch (PDOException $e) {
            // Gérer les erreurs de requête SQL
            return false;
        }
    }
}
