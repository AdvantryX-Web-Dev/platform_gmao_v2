<?php

namespace App\Models;

use App\Models\Database;
use PDO;


class Demande_maintenancier
{

    public function __construct($id_machine, $req_interv, $matriculeMain)
    {

        $this->id_machine = $id_machine;
        $this->req_interv = $req_interv;
        $this->matriculeMain = $matriculeMain;
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


    public static function id_interv($chaine, $id_machine)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $sql = "
        SELECT id 
        FROM aleas__req_interv  
        WHERE created_at = (
            SELECT MAX(created_at) 
            FROM aleas__req_interv a 
            WHERE a.group = :chaine AND a.machine_id = :id_machine
        )
    ";

        $req = $conn->prepare($sql);
        $req->execute([
            'chaine' => $chaine,
            'id_machine' => $id_machine
        ]);

        return $req->fetchColumn();
    }


    public static function AjouterDemandeMain($id_machine, $req_interv, $matriculeMain)
    {
        if (empty($req_interv) || !is_numeric($req_interv)) {
            throw new \Exception("req_interv_id invalide : la valeur est vide ou non numérique.");
        }

        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("INSERT INTO `gmao__demande_maint_curat` (id_machine, req_interv_id, matriculeMa) VALUES (:id_machine, :req_interv, :matriculeMa)");
        $stmt->bindParam(':id_machine', $id_machine);
        $stmt->bindParam(':req_interv', $req_interv, \PDO::PARAM_INT);
        $stmt->bindParam(':matriculeMa', $matriculeMain);

        $stmt->execute();
        return true;
    }
}
