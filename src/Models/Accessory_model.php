<?php

namespace App\Models;

class Accessory_model
{
    private $id;
    private $accessory_ref;
    private $machine_id;
    private $maintainer;
    private $mvt_state;
    private $allocation_time;
    private $allocation_date;

    public function __construct($id = null, $accessory_ref = null, $machine_id = null, $maintainer = null, $mvt_state = 'SS', $allocation_time = null, $allocation_date = null)
    {
        $this->id = $id;
        $this->accessory_ref = $accessory_ref;
        $this->machine_id = $machine_id;
        $this->maintainer = $maintainer;
        $this->mvt_state = $mvt_state;
        $this->allocation_time = $allocation_time;
        $this->allocation_date = $allocation_date;
    }

    // Getters et Setters
    public function getId()
    {
        return $this->id;
    }

    public function getAccessoryRef()
    {
        return $this->accessory_ref;
    }

    public function setAccessoryRef($accessory_ref)
    {
        $this->accessory_ref = $accessory_ref;
    }

    public function getMachineId()
    {
        return $this->machine_id;
    }

    public function setMachineId($machine_id)
    {
        $this->machine_id = $machine_id;
    }

    public function getMaintainer()
    {
        return $this->maintainer;
    }

    public function setMaintainer($maintainer)
    {
        $this->maintainer = $maintainer;
    }

    public function getMvtState()
    {
        return $this->mvt_state;
    }

    public function setMvtState($mvt_state)
    {
        $this->mvt_state = $mvt_state;
    }

    public function getAllocationTime()
    {
        return $this->allocation_time;
    }

    public function setAllocationTime($allocation_time)
    {
        $this->allocation_time = $allocation_time;
    }

    public function getAllocationDate()
    {
        return $this->allocation_date;
    }

    public function setAllocationDate($allocation_date)
    {
        $this->allocation_date = $allocation_date;
    }

    // Méthodes statiques pour la gestion des accessoires



    /**
     * Ajoute un nouvel accessoire (affectation)
     */
    public static function add(Accessory_model $accessory)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $query = "INSERT INTO gmao__prod_implementation_equipment (accessory_ref, machine_id, maintainer, mvt_state, allocation_time, allocation_date) 
                 VALUES (:accessory_ref, :machine_id, :maintainer, :mvt_state, :allocation_time, :allocation_date)";
        $stmt = $conn->prepare($query);

        $accessory_ref = $accessory->getAccessoryRef();
        $machine_id = $accessory->getMachineId();
        $maintainer = $accessory->getMaintainer();
        $mvt_state = $accessory->getMvtState();
        $allocation_time = $accessory->getAllocationTime();
        $allocation_date = $accessory->getAllocationDate();

        $stmt->bindParam(':accessory_ref', $accessory_ref);
        $stmt->bindParam(':machine_id', $machine_id);
        $stmt->bindParam(':maintainer', $maintainer);
        $stmt->bindParam(':mvt_state', $mvt_state);
        $stmt->bindParam(':allocation_time', $allocation_time);
        $stmt->bindParam(':allocation_date', $allocation_date);

        return $stmt->execute();
    }


    public static function isAlreadyAllocated($accessory_ref, $machine_id = null)
    {
        $db = new Database();
        $conn = $db->getConnection();

        if ($machine_id) {
            // Vérifie si l'équipement est déjà affecté à cette machine spécifique
            $query = "SELECT COUNT(*) FROM gmao__prod_implementation_equipment WHERE accessory_ref = :accessory_ref AND machine_id = :machine_id AND is_removed = 0";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':accessory_ref', $accessory_ref);
            $stmt->bindParam(':machine_id', $machine_id);
        } else {
            // Vérifie si l'équipement est déjà affecté à n'importe quelle machine
            $query = "SELECT COUNT(*) FROM gmao__prod_implementation_equipment WHERE accessory_ref = :accessory_ref AND is_removed = 0";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':accessory_ref', $accessory_ref);
        }

        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}
