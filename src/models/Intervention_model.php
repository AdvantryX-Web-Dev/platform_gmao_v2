<?php

namespace App\Models;

use App\Models\Database;

class Intervention_model
{

    public function __construct($maintainer_matricule, $prodline_id, $machine_id, $duration, $intervention_type, $etatMachine, $actionPrev, $intervention_date, $intervention_time, $codepannes, $articles)
    {
        $this->maintainer_matricule = $maintainer_matricule;
        $this->prodline_id = $prodline_id;
        $this->machine_id = $machine_id;
        $this->duration = $duration;


        $this->intervention_type  = $intervention_type;
        $this->etatMachine  = $etatMachine;
        $this->actionPrev  = $actionPrev;
        $this->intervention_date = $intervention_date;
        $this->intervention_time = $intervention_time;
        $this->codepannes = $codepannes;
        $this->articles = $articles;
    }
    public function __get($attr)
    {
        if (!isset($this->$attr)) return "erreur";
        else return ($this->$attr);
    }
    public function __set($attr, $value)
    {
        $this->$attr = $value;
    }

    public static function findByChaine($nomCh)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT m.designation, pi.*
        FROM prod__implantation pi
         JOIN init__machine m on (pi.machine_id=m.machine_id)
         where pi.prod_line ='$nomCh' and pi.machine_id NOT IN (select id_machine from `gmao__mouvement_machine`  WHERE (type_Mouv = 'entrée' AND statut = 1) OR (type_Mouv = 'sortie' AND id_Rais = 5))
         ORDER BY pi.cur_date, pi.cur_time

         ");
        $machines = $req->fetchAll();
        return $machines;
    }

    public static function nbInterPanneMachine($machine_id)
    {
        $db = new Database();
        $conn = $db->getConnection();
        $year = date('Y');
        $req = $conn->query("SELECT p.codePanne, COUNT(*) as nbInter FROM `gmao__interventions` i JOIN `gmao__interv_panne` p ON(i.id= p.numInter )  WHERE machine_id = '" . $machine_id . "' AND YEAR(intervention_date) = '$year' GROUP BY codePanne");
        $resultats = $req->fetchAll();

        return $resultats;
    }


    public static function findByMachine($id_machine)
    {

        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT * FROM `gmao__interventions`   WHERE  machine_id  = '" . $id_machine . "' ORDER BY intervention_date DESC");
        $inters = $req->fetchAll();
        return $inters;
    }
    public static function maint_dispo()
    {
        $db = new Database();
        $conn = $db->getConnection();
        $req = $conn->query("SELECT last_name, first_name, matricule
        FROM init__employee
        WHERE matricule NOT IN (SELECT maintainer FROM `aleas__maint_dispo`   where  req_interv_id NOT IN (select req_interv_id from aleas__end_maint_interv) ) AND lower(qualification)= 'MAINTAINER'");


        $req->execute();
        $maintDispos = $req->fetchAll();
        return $maintDispos;
    }
}
