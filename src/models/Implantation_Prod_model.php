<?php
namespace App\Models;

use App\Models\Database;

class implantation_Prod_model
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
    public static function findByMachineNonF()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->query("SELECT *
        FROM 
            init__machine m
        INNER JOIN 
            prod__implantation pi ON m.machine_id = pi.machine_id
        WHERE 
            YEAR(DATE(pi.cur_date)) = YEAR(CURDATE()) 
            AND pi.machine_id NOT IN (
                SELECT po.machine_id 
                FROM prod__pack_operation po 
                JOIN (
                    SELECT machine_id, MAX(cur_date) AS max_date
                    FROM prod__implantation
                    GROUP BY machine_id
                ) max_implantation ON po.machine_id = max_implantation.machine_id
                WHERE po.cur_date >= max_implantation.max_date 
                  AND YEAR(DATE(po.cur_date)) = YEAR(CURDATE()) 
            )
            AND pi.machine_id NOT IN (
                SELECT id_machine 
                FROM `gmao__mouvement_machine`  
                WHERE (type_Mouv = 'entrée' AND statut = 1) OR (type_Mouv = 'sortie' AND id_Rais = 5)
            )
        ");
        $machines = $req->fetchAll();
        return $machines;
    }
    public static function findAll()
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->query("SELECT p.*,isb.position,m.designation
        FROM prod__implantation p Inner join init__smartbox isb on (p.smartbox=isb.smartbox)
        inner join init__machine m on (p.machine_id=m.machine_id)
        -- where YEAR(p.cur_date) = YEAR(CURDATE())
       ");
        $req->execute();
        $resultats = $req->fetchAll();
        return $resultats;
    }
    public static function findChBymachine($machine_id)
        {
            $db = new Database();
            $conn = $db->getConnection();

        $req = $conn->query("SELECT prod_line
        FROM prod__implantation  where machine_id='$machine_id'
       ");
        $req->execute();
        $resultat = $req->fetchColumn();
        return $resultat;
    }
    public static function findBoxAndOperBymachine($machine_id)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $req = $conn->query("SELECT operator ,smartbox
        FROM prod__implantation  where machine_id='$machine_id'");
        $req->execute();
        $resultat = $req->fetch();
        return $resultat;
    }
    
    public static function findAllChaines()
    {
        $db = new Database();
        $conn = $db->getConnection();
        
        $req = $conn->query("SELECT DISTINCT prod_line FROM prod__implantation ORDER BY prod_line");
        $req->execute();
        $resultats = $req->fetchAll();
        return $resultats;
    }
}
