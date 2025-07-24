<?php

namespace App\Models;

use App\Models\Database;
use PDOException;

class Machines_status_model
{
    private $id;
    private $status_name;


    public function __construct($id, $status_name)
    {
        $this->id = $id;
        $this->status_name = $status_name;
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
        $db =  Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $machines_status = array();
        try {
            $req = $conn->query("SELECT * FROM gmao__machines_status order by updated_at desc");
            $machines_status = $req->fetchAll();
        } catch (PDOException $e) {

            return false;
        }
        return $machines_status;
    }

    public static function findById($id)
    {
        $db =  Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $machines_status = null;
        try {
            $req = $conn->query("SELECT * FROM gmao__machines_status WHERE id='$id'");
            $machines_status = $req->fetch();
        } catch (PDOException $e) {

            return false;
        }
        return $machines_status;
    }
    public static function StoreMachinesStatus($machines_status)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("INSERT INTO gmao__machines_status (`id`, `status_name`) VALUES (?, ?)");
            $stmt->bindParam(1, $machines_status->id);
            $stmt->bindParam(2, $machines_status->status_name);



            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    public static function UpdateMachinesStatus($machines_status)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("UPDATE gmao__machines_status SET status_name = ? WHERE id = '$machines_status->id'");
            $stmt->bindParam(1, $machines_status->status_name);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }




    public static function deleteById($id)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("DELETE FROM gmao__machines_status WHERE id = ?");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {

            return false;
        }
    }
}
