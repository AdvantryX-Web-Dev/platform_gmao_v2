<?php

namespace App\Models;

class Machines_box_model
{
    private $id;
    private $prod_line;
    private $machine_id;
    private $smartbox;
    private $operator;
    private $cur_date;
    private $cur_time;
    private $db;

    public function __construct($id, $prod_line, $machine_id, $smartbox, $operator, $cur_date, $cur_time)
    {
        $this->id = $id;
        $this->prod_line = $prod_line;
        $this->machine_id = $machine_id;
        $this->smartbox = $smartbox;
        $this->operator = $operator;
        $this->cur_date = $cur_date;
        $this->cur_time = $cur_time;
        $this->db = new Database();
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

        // Connexion à la base de données db_digitex pour init__machine
        $dbDigitex = Database::getInstance('db_digitex');
        $connDigitex = $dbDigitex->getConnection();

        // Utilisation des qualificateurs de base de données dans la requête SQL
        $query = "SELECT  p.*, isb.position, m.designation
        FROM prod__implantation p 
        INNER JOIN init__smartbox isb ON (p.smartbox = isb.smartbox)
        INNER JOIN init__machine m ON (p.machine_id = m.machine_id)
        -- where YEAR(p.cur_date) = YEAR(CURDATE())
        ";

        // Exécution sur la connexion GMAO car c'est la table principale de la requête
        $req = $connDigitex->query($query);
        $req->execute();
        $resultats = $req->fetchAll();
        return $resultats;
    }

   
}
