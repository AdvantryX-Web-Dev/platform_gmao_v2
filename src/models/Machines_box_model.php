<?php
namespace App\Models;

class Machines_box_model {
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
}
