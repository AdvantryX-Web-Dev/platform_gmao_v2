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


    public function __construct($machine_id, $designation, $reference, $type, $brand, $billing_num, $bill_date, $machines_location_id, $machines_status_id)
    {
        $this->machine_id = $machine_id;
        $this->designation = $designation;
        $this->reference = $reference;
        $this->type = $type;
        $this->brand = $brand;
        $this->billing_num = $billing_num;
        $this->bill_date = $bill_date;
        $this->machines_location_id = $machines_location_id;
        $this->machines_status_id = $machines_status_id;
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

    public static function findAllMachine()
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
    //utilise dans implementation de equipment
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
    //machine by id return machine
    public static function findById($id_machine)
    {
        $db = Database::getInstance('db_digitex');
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

    public static function ModifierMachine($machine, $price = null)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("UPDATE init__machine SET reference = ?, designation = ?, brand = ?, type = ?, billing_num = ?, bill_date = ?, price = ?, cur_date = NOW(), machines_location_id = ?, machines_status_id = ? WHERE machine_id = ?");
            $stmt->bindParam(1, $machine->reference);
            $stmt->bindParam(2, $machine->designation);
            $stmt->bindParam(3, $machine->brand);
            $stmt->bindParam(4, $machine->type);
            $stmt->bindParam(5, $machine->billing_num);
            $stmt->bindParam(6, $machine->bill_date);
            $stmt->bindParam(7, $price);
            $stmt->bindParam(8, $machine->machines_location_id);
            $stmt->bindParam(9, $machine->machines_status_id);
            $stmt->bindParam(10, $machine->machine_id);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {

            return false;
        }
    }

    public static function AjouterMachine($machine, $price = null)
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("INSERT INTO init__machine (`machine_id`, `reference`, `brand`, `type`, `designation`, `billing_num`, `bill_date`, `price`, `cur_date`, `machines_location_id`, `machines_status_id`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
            $stmt->bindParam(1, $machine->machine_id);
            $stmt->bindParam(2, $machine->reference);
            $stmt->bindParam(3, $machine->brand);
            $stmt->bindParam(4, $machine->type);
            $stmt->bindParam(5, $machine->designation);
            $stmt->bindParam(6, $machine->billing_num);
            $stmt->bindParam(7, $machine->bill_date);
            $stmt->bindParam(8, $price);
            $stmt->bindParam(9, $machine->machines_location_id);
            $stmt->bindParam(10, $machine->machines_status_id);

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


    //mouvmennt liste des  machineByLocation
    public static function machineByLocation($location)
    {

        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $req = $conn->query("SELECT * FROM init__machine 
            left join gmao__location ml on ml.id=init__machine.machines_location_id
             where ml.location_category='$location'
            ORDER BY machine_id");
            return $req->fetchAll();
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function MachinesStateTable($userMatricule = null, $filters = [])
    {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $userID = $_SESSION['user']['id'] ?? null;
            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
            $today = date('Y-m-d');

            // Récupérer les filtres
            $filterMatricule = $filters['matricule'] ?? null;
            $filterMachineId = $filters['machine_id'] ?? null;
            $filterLocation = $filters['location'] ?? null;
            $filterStatus = $filters['status'] ?? null;
			$filterDesignation = $filters['designation'] ?? null;

            $query = "
                SELECT 
                    m.machine_id,
                    m.id,
                    m.reference,
                    m.designation,
                    m.type,
                    m.brand,
                    m.billing_num,
                    m.machines_location_id,
                    m.machines_status_id,
                    m.bill_date,
                    m.created_at,
                    m.updated_at,
                    ms.status_name AS etat_machine,
                    ms.id AS status_id,
                    ml.location_name AS location,
                    ml.location_category AS location_category,
                    maint.maintener_id AS maintener_id,
                    -- Données du mainteneur (employé associé)
                    e.matricule AS maintener_matricule,
                    CONCAT(e.first_name, ' ', e.last_name) AS maintener_name,
    
                    -- Dernière présence avec priorité à celle d'aujourd'hui
                    COALESCE(pp_today.p_state, pp_last.p_state, 0) AS p_state,
                    COALESCE(pp_today.cur_time, pp_last.cur_time) AS cur_time,
                    COALESCE(pp_today.cur_date, pp_last.cur_date) AS cur_date,
                    COALESCE(
                        CONCAT(pp_today.cur_date, ' ', pp_today.cur_time),
                        CONCAT(pp_last.cur_date, ' ', pp_last.cur_time)
                    ) AS cur_date_time,
    
                    -- Statut final (active/inactive)
                    CASE 
                        WHEN ms.status_name = 'active' AND COALESCE(pp_today.p_state, 0) = 0 THEN 'inactive'
                        ELSE ms.status_name
                    END AS status_name_final
    
                FROM init__machine m
                LEFT JOIN gmao__status ms ON ms.id = m.machines_status_id
                LEFT JOIN gmao__location ml ON ml.id = m.machines_location_id
    
                -- 🔹 Dernière présence du jour
                LEFT JOIN (
                    SELECT p1.*
                    FROM prod__presence p1
                    INNER JOIN (
                        SELECT machine_id, MAX(id) AS last_id
                        FROM prod__presence
                        WHERE cur_date = :today
                        GROUP BY machine_id
                    ) t ON t.machine_id = p1.machine_id AND t.last_id = p1.id
                ) pp_today ON pp_today.machine_id = m.machine_id
    
                -- 🔹 Dernière présence active globale
                LEFT JOIN (
                    SELECT p2.*
                    FROM prod__presence p2
                    INNER JOIN (
                        SELECT machine_id, MAX(id) AS last_id
                        FROM prod__presence
                        WHERE p_state = 1
                        GROUP BY machine_id
                    ) t2 ON t2.machine_id = p2.machine_id AND t2.last_id = p2.id
                ) pp_last ON pp_last.machine_id = m.machine_id
    
                -- 🔹 Récupération du mainteneur actuel et de son employé associé
                LEFT JOIN (
                    SELECT mm.machine_id, mm.maintener_id
                    FROM gmao__machine_maint mm
                    INNER JOIN (
                        SELECT machine_id, MAX(id) AS last_id
                        FROM gmao__machine_maint
                        GROUP BY machine_id
                    ) mm_last 
                    ON mm_last.machine_id = mm.machine_id AND mm_last.last_id = mm.id
                ) maint ON maint.machine_id = m.id
    
                LEFT JOIN init__employee e ON e.id = maint.maintener_id
            ";

            // 🔸 Construction des conditions WHERE
            $whereConditions = [];
            $params = [];

            // Si utilisateur non-admin : restriction à ses propres machines
            if (!$isAdmin && $userID) {
                $whereConditions[] = "maint.maintener_id = :userID";
                $params[':userID'] = $userID;
            }

            // Filtre par matricule du mainteneur
            if ($filterMatricule && $isAdmin) {
                $whereConditions[] = "e.matricule = :filterMatricule";
                $params[':filterMatricule'] = $filterMatricule;
            } elseif (!$isAdmin && $userMatricule) {
                // Pour les non-admins, filtrer automatiquement sur leur matricule
                $whereConditions[] = "e.matricule = :userMatricule";
                $params[':userMatricule'] = $userMatricule;
            }

            // Filtre par machine_id
            if ($filterMachineId) {
                $whereConditions[] = "m.machine_id LIKE :filterMachineId";
                $params[':filterMachineId'] = '%' . $filterMachineId . '%';
            }

            // Filtre par emplacement
            if ($filterLocation) {
                $whereConditions[] = "ml.location_name = :filterLocation";
                $params[':filterLocation'] = $filterLocation;
            }

            // Filtre par état
            if ($filterStatus) {
                if ($filterStatus === 'active') {
                    $whereConditions[] = "COALESCE(pp_today.p_state, 0) = 1";
                } elseif ($filterStatus === 'inactive') {
                    $whereConditions[] = "COALESCE(pp_today.p_state, 0) = 0";
                } else {
                    // Utiliser l'ID du statut pour le filtrage
                    $whereConditions[] = "ms.id = :filterStatus";
                    $params[':filterStatus'] = $filterStatus;
                }
            }

			// Filtre par désignation (recherche partielle)
			if ($filterDesignation) {
				$whereConditions[] = "m.designation LIKE :filterDesignation";
				$params[':filterDesignation'] = '%' . $filterDesignation . '%';
			}

            // Ajouter les conditions WHERE si elles existent
            if (!empty($whereConditions)) {
                $query .= " WHERE " . implode(" AND ", $whereConditions);
            }

            $query .= " ORDER BY m.updated_at DESC";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':today', $today);

            // Bind tous les paramètres de filtrage
            foreach ($params as $param => $value) {
                if ($param === ':userID') {
                    $stmt->bindValue($param, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($param, $value);
                }
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in MachinesStateTable: ' . $e->getMessage());
            error_log('SQL Query: ' . $query);
            return [];
        }
    }
    public static function getDesignationMachine()
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        try {
            $stmt = $conn->prepare("SELECT DISTINCT designation FROM init__machine order by designation ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {

            return [];
        }
    }
    /**
     * Récupère la liste des mainteneurs pour le filtre
     */
    public static function getMaintainersList()
    {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $query = "
                SELECT DISTINCT e.id, e.matricule, CONCAT(e.first_name, ' ', e.last_name) AS full_name
                FROM init__employee e
                INNER JOIN gmao__machine_maint mm ON mm.maintener_id = e.id
                ORDER BY e.matricule
            ";

            $stmt = $conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in getMaintainersList: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la liste des machines pour le filtre
     */
    public static function getMachinesList()
    {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $query = "
                SELECT DISTINCT machine_id, reference, designation
                FROM init__machine
                ORDER BY machine_id
            ";

            $stmt = $conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in getMachinesList: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère la liste des emplacements pour le filtre
     */
    public static function getLocationsList()
    {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            $query = "
                SELECT DISTINCT location_category, location_name
                FROM gmao__location
                WHERE location_category IS NOT NULL
                ORDER BY location_category, location_name
            ";

            $stmt = $conn->prepare($query);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Error in getLocationsList: ' . $e->getMessage());
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
}
