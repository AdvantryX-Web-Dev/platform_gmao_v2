<?php
namespace App\Models;
use App\Models\Database;
use PDO;
class MouvementMachine_model
{

    public static function getMachinesWithDetails($machine_id)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $requete = $conn->query("SELECT 
       Max( p.machine_id) AS machine_id,
       Max( m.reference) AS refMachine,
      Max( m.designation) AS designationMachine,
        Max(p.smartbox) AS box,
        Max(p.prod_line) AS emplacement,
        Max(m.cur_date) AS datePositionnement,
        COUNT(p.operation_num) AS nbOperations,
        SUM(p.pack_qty * p.unit_time) AS tempsFonctionnement,
        MAX(IFNULL(
            CASE 
                WHEN t.call_maint = 1 THEN 
                510 - TIMESTAMPDIFF(SECOND, Mi.created_at, e.created_at)/60  
               
            END,
            510
        )) AS tempsDispo,
       Max(p.cur_date) AS date,
        Max(p.cur_time) AS time
    FROM 
        init__machine m
    INNER JOIN 
        prod__pack_operation p ON m.machine_id = p.machine_id
    LEFT JOIN  
        aleas__req_interv r ON m.machine_id = r.machine_id 
        AND p.cur_date = Date(r.created_at) 
    LEFT JOIN 
    aleas__mon_interv Mi on r.id = Mi.req_interv_id
    Left JOIN 
    init__aleas_type t on Mi.aleas_type_id = t.id
    LEFT JOIN
        aleas__end_mon_interv e ON r.id = e.req_interv_id
        where YEAR(p.cur_date) = YEAR(CURDATE())  AND p.machine_id='$machine_id'
    GROUP BY 
        p.prod_line, p.cur_date
UNION ALL
SELECT 
        m.machine_id, 
        m.reference AS refMachine,
        m.designation AS designationMachine,
        NULL AS box,
        CASE
        WHEN lower(mo.type_Mouv) like 'entr%' THEN 'E_ParcMachine'
        WHEN lower(mo.type_Mouv) = 'sortie' THEN 'S_ParcMachine'
    END AS emplacement,
        m.cur_date AS datePositionnement,
        NULL AS nbOperationsAujourdhui,
        NULL AS tempsFonctionnement,
        NULL AS tempsDispo,
        DATE(mo.date_Mouv_Mach) AS date,
        TIME(mo.date_Mouv_Mach) AS time
    FROM 
        init__machine m
    INNER JOIN 
        `gmao__mouvement_machine`  mo ON m.machine_id = mo.id_machine
    WHERE 
       
         YEAR(DATE(mo.date_Mouv_Mach)) = YEAR(CURDATE()) AND mo.id_machine='$machine_id'
         UNION ALL
SELECT 
        m.machine_id, 
        m.reference AS refMachine,
        m.designation AS designationMachine,
        NULL AS box,
        
       
    hm.Preteur AS emplacement,
        m.cur_date AS datePositionnement,
        NULL AS nbOperationsAujourdhui,
        NULL AS tempsFonctionnement,
        NULL AS tempsDispo,
        DATE(hm.dateDebut) AS date,
      TIME(hm.dateDebut) AS time
    FROM 
        init__machine m
    INNER JOIN 
        `gmao__hst_mvt_mach`  hm ON m.machine_id = hm.id_machine
    WHERE 
       
         YEAR(DATE(hm.dateDebut)) = YEAR(CURDATE()) AND hm.id_machine='$machine_id' AND hm.state=1
         UNION ALL
SELECT 
            pi.machine_id, 
            m.reference AS refMachine,
            m.designation AS designationMachine,
            pi.smartbox AS box,
            pi.prod_line AS emplacement,
            m.cur_date AS datePositionnement,
            NULL AS nbOperationsAujourdhui,
            NULL AS tempsFonctionnement,
            NULL AS tempsDispo,
            DATE(pi.cur_date) AS date,
            TIME(pi.cur_time) AS time
        FROM 
            init__machine m
        INNER JOIN 
            prod__implantation pi ON m.machine_id = pi.machine_id
        WHERE 
         YEAR(DATE(pi.cur_date)) = YEAR(CURDATE()) AND  pi.machine_id NOT IN (
            SELECT po.machine_id 
            FROM prod__pack_operation po 
            JOIN (
                SELECT machine_id, MAX(cur_date) AS max_date
                FROM prod__implantation
                GROUP BY machine_id
            ) max_implantation ON po.machine_id = max_implantation.machine_id
            WHERE po.cur_date >= max_implantation.max_date AND YEAR(DATE(po.cur_date)) = YEAR(CURDATE())
            ) AND pi.machine_id NOT IN (select id_machine from `gmao__mouvement_machine`  WHERE (type_Mouv like 'entr%' AND statut = 1) OR (type_Mouv = 'sortie' AND id_Rais = 5)) AND m.machine_id= '$machine_id';");

        return $requete->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getMachineDetails($id_machine, $date)
    {
        include("../Connexion/Connexion.php");
        $currentYear = date("Y");
        // si la machine est présente dans la table aleas__req_interv, elle utilisera la différence calculée entre r.created_at et e.created_at pour déterminer le temps d'arrêt. Si la machine n'est pas dans cette table, elle attribuera un temps d'arrêt de 510 minutes.
        $requete = $conn->query(" SELECT 
       Max( p.cur_date),
         Max(p.machine_id),
         Max(m.reference) AS refMachine,
         Max(m.designation) AS designationMachine,
         Max(p.smartbox) AS box,
        Max( p.prod_line) AS chaine,
        CONCAT(e.first_name,' ',e.last_name )AS operatorName,
       Max(p.operator) AS operator,
        CASE 
        WHEN t.call_maint = 1 THEN  Mi.created_at
        ELSE NULL 
    END AS dateDebut,
    CASE 
        WHEN t.call_maint = 1 THEN ei.created_at
        ELSE NULL 
    END AS dateFin,
        COUNT(p.operation_num) AS nbOperationsOperatrice,
    
        CASE 
                WHEN  t.call_maint = 1 THEN 
                  TIMESTAMPDIFF(SECOND,  Mi.created_at,  ei.created_at) end  AS duree
    FROM 
        init__machine m
        INNER JOIN 
        prod__pack_operation p ON m.machine_id = p.machine_id
    LEFT JOIN 
        init__employee e ON p.operator = e.matricule
    LEFT JOIN 
        aleas__req_interv r ON m.machine_id = r.machine_id 
    AND p.cur_date = Date(r.created_at)
    LEFT JOIN 
    aleas__mon_interv Mi on r.id = Mi.req_interv_id
    Left JOIN 
    init__aleas_type t on Mi.aleas_type_id = t.id
    LEFT JOIN 
        aleas__end_mon_interv ei ON r.id = ei.req_interv_id
    WHERE 
        m.machine_id = '$id_machine' AND p.cur_date = '$date' AND YEAR(p.cur_date) = YEAR(CURDATE()) 
    GROUP BY 
        p.cur_date, p.machine_id, m.reference, m.designation, p.smartbox, p.prod_line, operatorName, p.operator,dateDebut,dateFin,duree;
");

        return $requete->fetchAll();
    }
}
