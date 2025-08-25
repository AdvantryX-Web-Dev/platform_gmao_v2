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

    public static function findAllv2()
    {
        // Connexion à la base de données MAHDCO_MAINT
        $dbGmao = Database::getInstance('MAHDCO_MAINT');
        $connGmao = $dbGmao->getConnection();

        // Connexion à la base de données db_digitex (qui pointe vers db_digitex)
        $dbDigitex = Database::getInstance('db_digitex');
        $connDigitex = $dbDigitex->getConnection();

        // Au lieu d'utiliser des qualificateurs de base de données, nous allons effectuer des requêtes séparées
        // puis les combiner dans le PHP

        // 1. Récupérer les données de prod__implantation depuis MAHDCO_MAINT
        $implantQuery = "SELECT * FROM prod__implantation";
        $implantStmt = $connGmao->query($implantQuery);
        $implantations = $implantStmt->fetchAll();

        // 2. Récupérer les données de init__smartbox depuis db_digitex (via connexion db_digitex)
        $smartboxQuery = "SELECT * FROM init__smartbox";
        $smartboxStmt = $connDigitex->query($smartboxQuery);
        $smartboxes = [];
        while ($row = $smartboxStmt->fetch()) {
            $smartboxes[$row['smartbox']] = $row;
        }

        // 3. Récupérer les données de init__machine depuis db_digitex (via connexion db_digitex)
        $machinesQuery = "SELECT machine_id, designation FROM init__machine";
        $machinesStmt = $connDigitex->query($machinesQuery);
        $machines = [];
        while ($row = $machinesStmt->fetch()) {
            $machines[$row['machine_id']] = $row;
        }

        // 4. Combiner les résultats dans le format attendu
        $resultats = [];
        foreach ($implantations as $implantation) {
            $machineId = $implantation['machine_id'];
            $smartboxId = $implantation['smartbox'];

            // Ajouter les informations du smartbox
            if (isset($smartboxes[$smartboxId])) {
                $implantation['position'] = $smartboxes[$smartboxId]['position'];
            } else {
                $implantation['position'] = null;
            }

            // Ajouter les informations de la machine
            if (isset($machines[$machineId])) {
                $implantation['designation'] = $machines[$machineId]['designation'];
            } else {
                $implantation['designation'] = null;
            }

            $resultats[] = $implantation;
        }

        return $resultats;
    }
}
