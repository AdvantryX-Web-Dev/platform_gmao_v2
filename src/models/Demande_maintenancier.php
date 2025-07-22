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


    public static function AjouterDemandeMain($id_machine, $req_interv, $matriculeMain, $intervention_type_id, $production_line_id = null)
    {
        if (empty($req_interv) || !is_numeric($req_interv)) {
            throw new \Exception("req_interv_id invalide : la valeur est vide ou non numérique.");
        }

        $db = new Database();
        $conn = $db->getConnection();

        try {
            // Commencer une transaction
            $conn->beginTransaction();

            // 1. Insertion dans gmao__demande_maint_curat
            $stmt1 = $conn->prepare("INSERT INTO `gmao__demande_maint_curat` (id_machine, req_interv_id, matriculeMa) VALUES (:id_machine, :req_interv, :matriculeMa)");
            $stmt1->bindParam(':id_machine', $id_machine);
            $stmt1->bindParam(':req_interv', $req_interv, \PDO::PARAM_INT);
            $stmt1->bindParam(':matriculeMa', $matriculeMain);
            $stmt1->execute();

            // Récupérer la ligne de production si non fournie
            if (empty($production_line_id)) {
                $stmtLine = $conn->prepare("SELECT id FROM prod__implantation WHERE machine_id = :machine_id LIMIT 1");
                $stmtLine->bindParam(':machine_id', $id_machine);
                $stmtLine->execute();
                $production_line_id = $stmtLine->fetchColumn();

                if (!$production_line_id) {
                    throw new \Exception("Impossible de déterminer la ligne de production pour cette machine.");
                }
            }

            // 2. Insertion dans gmao__intervention_action
            $stmt2 = $conn->prepare("
                INSERT INTO `gmao__intervention_action` 
                (machine_id, production_line_id, planning_id, intervention_date, maintenance_by, intervention_type_id, created_at, updated_at) 
                VALUES (:machine_id, :production_line_id, NULL, CURDATE(), :maintenance_by, :intervention_type_id, NOW(), NOW())
            ");
            // Convertir machine_id en entier car la table utilise un type INT
            $machine_id_int = intval($id_machine);
            $stmt2->bindParam(':machine_id', $machine_id_int, \PDO::PARAM_INT);
            $stmt2->bindParam(':production_line_id', $production_line_id, \PDO::PARAM_INT);
            $stmt2->bindParam(':maintenance_by', $matriculeMain, \PDO::PARAM_INT);
            $stmt2->bindParam(':intervention_type_id', $intervention_type_id, \PDO::PARAM_INT);
            $stmt2->execute();

            // Valider la transaction
            $conn->commit();
            return true;
        } catch (\PDOException $e) {
            // Annuler la transaction en cas d'erreur
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            throw new \Exception("Erreur lors de l'ajout de la demande: " . $e->getMessage());
        }
    }
}
