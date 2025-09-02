<?php

namespace App\Models;

use App\Models\Database;
use PDOException;
use PDO;

class Machine_model
{
    private $machine_id;
    private $designation;
    private $reference;
    private $type;
    private $brand;
    private $billing_num;
    private $machines_location_id;
    private $machines_status_id;
    private $bill_date;


    public function __construct($machine_id, $designation, $reference, $type, $brand, $billing_num, $machines_location_id, $machines_status_id, $bill_date)
    {
        $this->machine_id = $machine_id;
        $this->designation = $designation;
        $this->reference = $reference;
        $this->type = $type;
        $this->brand = $brand;
        $this->billing_num = $billing_num;
        $this->machines_location_id = $machines_location_id;
        $this->machines_status_id = $machines_status_id;
        $this->bill_date = $bill_date;
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
    //all machine de init__machine
    public static function findAllMachine()
    {
        $db = Database::getInstance('db_digitex'); // Spécifier explicitement la base de données db_digitex
        $conn = $db->getConnection();
        $machines = array();

        try {
            $req = $conn->query("
                SELECT DISTINCT m.machine_id ,m.id
                FROM init__machine m
                order by m.machine_id desc
            ");
            $machines = $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {

            return false;
        }

        return $machines;
    }

    public static function findAll()
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $machines = array();
        try {
            $req = $conn->query("SELECT * FROM init__machine m  
            order by m.created_at desc");
            $machines = $req->fetchAll();
        } catch (PDOException $e) {

            return false;
        }


        return $machines;
    }

    public static function ProdMachine()
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $machines = array();

        try {
            $sql = "
                SELECT m.* 
                FROM init__machine m
                LEFT JOIN gmao__location l ON m.machines_location_id = l.id
                WHERE l.location_category = 'prodline'
                ORDER BY m.machine_id DESC
            ";

            $req = $conn->query($sql);
            $machines = $req->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }

        return $machines;
    }

    public static function findById($id_machine)
    {
        $db = Database::getInstance('db_digitex'); // Spécifier explicitement la base de données db_digitex
        $conn = $db->getConnection();
        $machine = null;
        try {
            $req = $conn->query("SELECT * FROM init__machine m WHERE machine_id='$id_machine'");
            $machine = $req->fetch();
        } catch (PDOException $e) {

            return false;
        }
        return $machine;
    }

    public static function ModifierMachine($machine)
    {
        $db = Database::getInstance('db_digitex'); // Spécifier explicitement la base de données db_digitex
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("UPDATE init__machine SET reference = ?, designation = ?, brand = ?, type = ?, billing_num = ?, bill_date = ?, machines_location_id = ?, machines_status_id = ?, cur_date=NOW() WHERE machine_id = ?");
            $stmt->bindParam(1, $machine->reference);
            $stmt->bindParam(2, $machine->designation);
            $stmt->bindParam(3, $machine->brand);
            $stmt->bindParam(4, $machine->type);
            $stmt->bindParam(5, $machine->billing_num);
            $stmt->bindParam(6, $machine->machines_location_id);
            $stmt->bindParam(7, $machine->machines_status_id);
            $stmt->bindParam(8, $machine->bill_date);
            $stmt->bindParam(9, $machine->machine_id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {

            return false;
        }
    }

    public static function AjouterMachine($machine)
    {
        $db = Database::getInstance('db_digitex'); // Spécifier explicitement la base de données db_digitex
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("INSERT INTO init__machine (`machine_id`, `reference`, `brand`, `type`, `designation`, `billing_num`, `bill_date`, `machines_location_id`, `machines_status_id`, `cur_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bindParam(1, $machine->machine_id);
            $stmt->bindParam(2, $machine->reference);
            $stmt->bindParam(3, $machine->brand);
            $stmt->bindParam(4, $machine->type);
            $stmt->bindParam(5, $machine->designation);
            $stmt->bindParam(6, $machine->billing_num);
            $stmt->bindParam(7, $machine->machines_location_id);
            $stmt->bindParam(8, $machine->machines_status_id);
            $stmt->bindParam(9, $machine->bill_date);

            $stmt->execute();
            return true;
        } catch (PDOException $e) {

            return false;
        }
    }
    /**
     * Vérifie l'existence d'une machine par son ID
     */
    public static function existsByMachineId($machineId)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("SELECT 1 FROM init__machine WHERE machine_id = ? LIMIT 1");
            $stmt->bindParam(1, $machineId);
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Vérifie l'existence d'une machine par sa référence
     * Optionnellement exclut une machine donnée (lors de l'édition)
     */
    public static function existsByReference($reference, $excludeMachineId = null)
    {
        if ($reference === null || $reference === '') {
            return false;
        }
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            if ($excludeMachineId) {
                $stmt = $conn->prepare("SELECT 1 FROM init__machine WHERE reference = ? AND machine_id <> ? LIMIT 1");
                $stmt->bindParam(1, $reference);
                $stmt->bindParam(2, $excludeMachineId);
            } else {
                $stmt = $conn->prepare("SELECT 1 FROM init__machine WHERE reference = ? LIMIT 1");
                $stmt->bindParam(1, $reference);
            }
            $stmt->execute();
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return false;
        }
    }
    public static function findBytype($type)
    {
        $db = Database::getInstance('db_digitex'); // Spécifier explicitement la base de données db_digitex
        $conn = $db->getConnection();
        $machines = array();
        try {
            $req = $conn->query("SELECT * FROM init__machine where designation='$type'");
            $machines = $req->fetchAll();
        } catch (PDOException $e) {

            return false;
        }
        return $machines;
    }

    public static function deleteById($id)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("DELETE FROM init__machine WHERE machine_id = ?");
            $stmt->bindParam(1, $id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {

            return false;
        }
    }

    public static function findAllTypes($location)
    {

        $db = Database::getInstance('db_digitex'); // Spécifier explicitement la base de données db_digitex
        $conn = $db->getConnection();
        try {
            $req = $conn->query("SELECT DISTINCT type FROM init__machine 
            left join gmao__location ml on ml.id=init__machine.machines_location_id
             where ml.location_category='$location'
            ORDER BY type");
            return $req->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }



    public static function MachinesStateTable()
    {

        $db = new Database(); // Spécifier explicitement la base de données db_digitex

        $conn = $db->getConnection();
        try {
            // Obtenir la date d'aujourd'hui
            $today = date('Y-m-d');

            // Requête principale pour obtenir les machines et leurs informations
            // avec jointure sur la table de présence pour aujourd'hui
            $query = "
                SELECT 
                    m.*,
                    ms.status_name as etat_machine,
                    ms.id as status_id,
                    ml.location_name as location,
                    ml.location_category as location_category,
                    pp.p_state
                FROM 
                    init__machine m
                    LEFT JOIN gmao__status ms ON ms.id = m.machines_status_id 
                    LEFT JOIN gmao__location ml ON ml.id = m.machines_location_id 
                    LEFT JOIN prod__presence pp ON pp.machine_id = m.machine_id AND pp.cur_date = :today
                ORDER BY 
                    m.updated_at DESC
            ";

            $stmt = $conn->prepare($query);

            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $machines = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Récupérer les IDs des statuts
            $stmt = $conn->query("
                SELECT id, status_name 
                FROM gmao__status
            ");
            $statusMap = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $statusMap[$row['status_name']] = $row['id'];
            }

            // Parcourir les machines et ajuster leur état selon l'emplacement et l'activité
            foreach ($machines as &$machine) {
                $machineId = $machine['machine_id'];
                $location = $machine['location'];
                $currentStatus = $machine['etat_machine'];
                $newStatusId = null;

                // Déterminer le nouvel état selon l'emplacement
                if ($location == 'prodline') {
                    // Si la machine est en production
                    if ($machine['p_state'] == 1) {
                        // Machine active aujourd'hui (présente dans prod__presence avec p_state = 1)
                        $machine['etat_machine'] = 'active';
                        $newStatusId = $statusMap['active'];
                    } elseif ($currentStatus == 'en panne') {
                        // Si déjà marquée en panne, garder cet état
                        $machine['etat_machine'] = 'en panne';
                        $newStatusId = $statusMap['en panne'];
                    } else {
                        // Machine en production mais pas active aujourd'hui
                        $machine['etat_machine'] = 'inactive';
                        $newStatusId = $statusMap['inactive'];
                    }
                }
                // Pour les machines dans le parc
                elseif ($location == 'parc') {
                    if (in_array($currentStatus, ['en panne', 'ferraille', 'fonctionnelle'])) {
                        // Conserver l'état actuel s'il est approprié pour le parc
                        $newStatusId = $machine['status_id'];
                    } else {
                        // Sinon, mettre par défaut à "fonctionnelle"
                        $machine['etat_machine'] = 'fonctionnelle';
                        $newStatusId = isset($statusMap['fonctionnelle']) ? $statusMap['fonctionnelle'] : null;
                    }
                }

                // Mettre à jour l'état dans la base de données si nécessaire
                if ($newStatusId && $newStatusId != $machine['machines_status_id']) {
                    $updateStmt = $conn->prepare("
                        UPDATE init__machine 
                        SET machines_status_id = :status_id, updated_at = NOW() 
                        WHERE machine_id = :machine_id
                    ");
                    $updateStmt->bindParam(':status_id', $newStatusId, \PDO::PARAM_INT);
                    $updateStmt->bindParam(':machine_id', $machineId, \PDO::PARAM_STR);
                    $updateStmt->execute();
                }
            }

            return $machines;
        } catch (PDOException $e) {
            error_log('Error in MachinesStateTable: ' . $e->getMessage());
            // Return empty array instead of false
            return [];
        }
    }
    /**
     * Get all machine statuses from gmao__status table
     * 
     * @return array Array of machine statuses
     */
    public static function getMachineStatus()
    {
        $db =  Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $query = "SELECT * FROM gmao__status ORDER BY id ASC";
            $stmt = $conn->query($query);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return $result ?: [];
        } catch (PDOException $e) {
            // Handle SQL query errors
            error_log('Error in getMachineStatus: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update machine status
     * 
     * @param string $machineId Machine ID
     * @param int $statusId Status ID from gmao__status table
     * @return bool True if update was successful, false otherwise
     */
    public static function updateMachineStatus($machineId, $statusId)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $query = "UPDATE init__machine SET machines_status_id = :status_id, updated_at = NOW() 
                      WHERE machine_id = :machine_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':status_id', $statusId, PDO::PARAM_INT);
            $stmt->bindParam(':machine_id', $machineId, PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }
}
