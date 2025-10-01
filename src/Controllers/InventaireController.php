<?php

namespace App\Controllers;

use App\Models\Database;
use App\Models\Maintainer_model;
use App\Models\AuditTrail_model;

class InventaireController
{
    public function importInventaire()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleImport();
            return;
        }
        include __DIR__ . '/../views/inventaire/importInventaire.php';
    }

    public function listInventaire()
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $stmt = $conn->query("SELECT iim.*, m.reference AS machine_reference, e.fullname AS maintainer_name, l.location_name, s.status_name FROM gmao__inventaire_machine iim
            LEFT JOIN init__machine m ON m.id = iim.machine_id
            LEFT JOIN init__employee e ON e.id = iim.maintener_id
            LEFT JOIN gmao__location l ON l.id = iim.location_id
            LEFT JOIN gmao__status s ON s.id = iim.status_id
            ORDER BY iim.created_at DESC");
        $inventaires = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        include __DIR__ . '/../views/inventaire/listInventaire.php';
    }

    private function handleImport()
    {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = 'Fichier invalide';
            header('Location: index.php?route=importInventaire');
            exit;
        }

        $tmpPath = $_FILES['file']['tmp_name'];
        $originalName = $_FILES['file']['name'];
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Support  Excel (xlsx/xls) using PhpSpreadsheet if available
        $rows = [];
        if (in_array($ext, ['xlsx', 'xls'])) {
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                $_SESSION['flash_error'] = 'Support Excel manquant. Installez PhpSpreadsheet ou importez en CSV.';
                header('Location: index.php?route=importInventaire');
                exit;
            }
            try {
                $ioFactoryClass = '\\PhpOffice\\PhpSpreadsheet\\IOFactory';
                $reader = $ioFactoryClass::createReaderForFile($tmpPath);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($tmpPath);
                $sheet = $spreadsheet->getActiveSheet();
                $rowIndex = 0;
                foreach ($sheet->getRowIterator() as $row) {
                    $rowIndex++;
                    if ($rowIndex === 1) continue; // skip first title row
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $data = [];
                    foreach ($cellIterator as $cell) {
                        // Use formatted value to match what Excel displays (dates, numbers)
                        if (method_exists($cell, 'getFormattedValue')) {
                            $val = $cell->getFormattedValue();
                        } else {
                            $val = $cell->getValue();
                        }
                        $data[] = is_string($val) ? trim($val) : (string)$val;
                    }
                    // Skip header row where first cell is 'code'
                    if (isset($data[0]) && strtolower(trim($data[0])) === 'code') {
                        continue;
                    }
                    // Skip completely empty rows
                    $allEmpty = true;
                    foreach ($data as $v) {
                        if (trim($v) !== '') {
                            $allEmpty = false;
                            break;
                        }
                    }
                    if ($allEmpty) {
                        continue;
                    }
                    // Keep numeric indices exactly as in Excel row
                    $rows[] = $data;
                }
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Erreur lecture Excel: ' . $e->getMessage();
                header('Location: index.php?route=importInventaire');
                exit;
            }
        } else {
            $_SESSION['flash_error'] = 'Format non supporté. Utilisez CSV, XLSX ou XLS.';
            header('Location: index.php?route=importInventaire');
            exit;
        }

        if (empty($rows)) {
            $_SESSION['flash_error'] = 'Aucune donnée à importer';
            header('Location: index.php?route=importInventaire');
            exit;
        }

        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $conn->beginTransaction();
        //insert into gmao__inventaire_machine
        $this->save_inventaire_machine($conn, $rows);


        header('Location: index.php?route=importInventaire');
        exit;
    }
    private function save_inventaire_machine($conn, $rows)
    {

        try {
            $insert = $conn->prepare("INSERT INTO gmao__inventaire_machine (machine_id, maintener_id, location_id, status_id, created_at) VALUES (:machine_id, :maintener_id, :location_id, :status_id, :created_at)");
            // Lookup prepared statements
            $idMachine = $conn->prepare("SELECT id FROM init__machine WHERE machine_id = :id_machine LIMIT 1");
            // Vérifier l'existence d'une ligne avec même machine_id et created_at
            $checkDuplicate = $conn->prepare("SELECT id FROM gmao__inventaire_machine WHERE machine_id = :machine_id AND DATE(created_at) = :current_date LIMIT 1");

            foreach ($rows as $row) {

                $machine_idxl = trim((string)$row[10]);
                // id machine
                if ($machine_idxl !== '') {
                    $idMachine->execute([':id_machine' => $machine_idxl]);
                    $machineId = $idMachine->fetchColumn();
                    if ($machineId === false || $machineId === null || $machineId === '') {
                        // Aucun équipement correspondant, ignorer la ligne pour éviter la violation FK
                        continue;
                    }
                    $machineId = (int)$machineId;
                } else {
                    continue;
                }
                // Vérifier s'il existe déjà une ligne avec le même machine_id et la même date
                $currentDate = date('Y-m-d');
                $checkDuplicate->execute([
                    ':machine_id' => $machineId,
                    ':current_date' => $currentDate
                ]);

                // id maintenancier
                $idMaintener = $conn->prepare("SELECT id FROM init__employee WHERE matricule = :id_maintener LIMIT 1");
                $maintener_idxl = trim((string)$row[7]);

                if ($maintener_idxl !== '') {
                    $idMaintener->execute([':id_maintener' => $maintener_idxl]);
                    $maintenerId = $idMaintener->fetchColumn();
                    if ($maintenerId === false || $maintenerId === null || $maintenerId === '') {
                        // Matricule inconnu, ignorer la ligne pour éviter la violation FK
                        continue;
                    }
                    $maintenerId = (int)$maintenerId;
                } else {
                    continue;
                }
                // id location
                $idLocation = $conn->prepare("SELECT id FROM gmao__location WHERE location_name = :id_location LIMIT 1");
                $location_idxl = trim((string)$row[9]);

                if ($location_idxl !== '') {


                    $location_category =  ($conn->prepare("select location_category
                     from gmao__location 
                     where location_name = :id_location;"));

                    $location_category->execute([':id_location' => $location_idxl]);
                    $location_category = $location_category->fetchColumn();

                    $idLocation->execute([':id_location' => $location_idxl]);
                    $locationId = $idLocation->fetchColumn();


                    if (empty($locationId)) {
                        if (strpos($location_idxl, 'CH') === 0 || strpos($location_idxl, 'ECH') === 0 || strpos($location_idxl, 'ch') === 0) {
                            $location_category = 'prodline';
                        } elseif ($location_idxl === "FERAILLE") {
                            continue;
                        } else {
                            $location_category = 'parc';
                        }
                        $stmt = $conn->prepare("INSERT INTO gmao__location (location_name, location_category) VALUES (:location_name, :location_category)");
                        $stmt->execute([':location_name' => $location_idxl, ':location_category' => $location_category]);
                        $locationId = $conn->lastInsertId(); // récupérer l'ID correct après INSERT



                    }
                    // Cast en entier si présent
                    if ($locationId !== false && $locationId !== null && $locationId !== '') {
                        $locationId = (int)$locationId;
                    }
                } else {
                    $locationId = null;
                }

                //id status
                $status_idxl = trim((string)$row[18]);
                $status = null;
                if ($status_idxl !== '') {
                    if ($status_idxl === "NON OK" && $location_category === "parc") {
                        $status = "en panne";
                    } elseif ($status_idxl === "NON OK" && $location_idxl === "ANNEX CHIBA") {
                        $status = "ferraille";
                    } elseif ($status_idxl === "OK" && $location_category === "parc") {
                        $status = "inactive";
                    } elseif ($status_idxl === "NON OK" && $location_category === "prodline") {
                        $status = "inactive";
                    } elseif ($status_idxl === "OK" && $location_category === "prodline") {
                        $status = "active";
                    }
                }
                if ($status !== null) {
                    $idStatus = $conn->prepare("SELECT id FROM gmao__status WHERE status_name = :id_status LIMIT 1");
                    $idStatus->execute([':id_status' => $status]);
                    $statusId = $idStatus->fetchColumn();
                    $statusId = ($statusId !== false && $statusId !== null && $statusId !== '') ? (int)$statusId : null;
                } else {
                    $statusId = null;
                }




                $insert->execute([
                    ':machine_id' => $machineId,
                    ':maintener_id' => $maintenerId,
                    ':location_id' => $locationId,
                    ':status_id' => $statusId,
                    ':created_at' => date('Y-m-d H:i:s')
                ]);
                //insert into gmao__machine_maint
                $stmt = $conn->prepare("INSERT INTO gmao__machine_maint (machine_id, maintener_id, location_id) VALUES (:machine_id, :maintener_id, :location_id)");
                $stmt->execute([':machine_id' => $machineId, ':maintener_id' => $maintenerId, ':location_id' => $locationId]);
                //update init__machine set machines_status_id et machines_location_id 
                $stmt = $conn->prepare("UPDATE init__machine SET machines_status_id = :status_id, machines_location_id = :location_id WHERE id = :machine_id");
                $stmt->execute([':status_id' => $statusId, ':location_id' => $locationId, ':machine_id' => $machineId]);
                $this->logAuditImport($machineId, $maintenerId, $locationId, $statusId);
            }
            $conn->commit();

            $_SESSION['flash_success'] = 'Import effectué avec succès';
        } catch (\Throwable $e) {
            $conn->rollBack();
            $_SESSION['flash_error'] = 'Erreur lors de l\'import: ' . $e->getMessage();
        }
    }

    public function maintenancier_machine()
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        // Récupérer le matricule de l'utilisateur connecté
        $connectedMatricule = $_SESSION['user']['matricule'] ?? null;
        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';

        // Filtres pour admin
        $filterMatricule = $_GET['matricule'] ?? '';
        $filterChaine = $_GET['chaine'] ?? '';
        $filterMachine = $_GET['machine'] ?? '';

        // Construire la requête de base - une ligne par machine
        $sql = "
            SELECT
                e.matricule AS maintener_name,
                e.first_name,
                e.last_name,
                mm.maintener_id,
                m.reference AS machine_reference,
                m.machine_id AS machine_id,
                COALESCE(s.status_name, 'non défini') AS machine_status,
                l.location_name AS location_name,
                l.location_category AS location_category
            FROM (
                SELECT mm.*
                FROM gmao__machine_maint mm
                INNER JOIN (
                    SELECT MAX(id) AS id
                    FROM gmao__machine_maint
                    GROUP BY machine_id
                ) last ON last.id = mm.id
            ) mm
            LEFT JOIN init__employee e ON e.id = mm.maintener_id
            LEFT JOIN init__machine m ON m.id = mm.machine_id
            LEFT JOIN gmao__location l ON l.id = mm.location_id
            LEFT JOIN gmao__status s ON s.id = m.machines_status_id
            WHERE 1=1
        ";

        $params = [];

        // Si admin, appliquer les filtres
        if ($isAdmin) {
            if (!empty($filterMatricule)) {
                $sql .= " AND e.matricule = :matricule";
                $params['matricule'] = $filterMatricule;
            }
            if (!empty($filterChaine)) {
                $sql .= " AND l.location_name = :chaine";
                $params['chaine'] = $filterChaine;
            }
            if (!empty($filterMachine)) {
                // Si le filtre contient " - ", c'est un format "ID - Reference"
                if (strpos($filterMachine, ' - ') !== false) {
                    $parts = explode(' - ', $filterMachine, 2);
                    $sql .= " AND (m.machine_id = :machine_id AND m.reference = :machine_ref)";
                    $params['machine_id'] = trim($parts[0]);
                    $params['machine_ref'] = trim($parts[1]);
                } else {
                    // Sinon chercher par ID ou référence
                    $sql .= " AND (m.machine_id = :machine OR m.reference = :machine_ref)";
                    $params['machine'] = $filterMachine;
                    $params['machine_ref'] = $filterMachine;
                }
            }
        } else {
            // Si pas admin, filtrer par matricule connecté
            if ($connectedMatricule) {
                $sql .= " AND e.matricule = :matricule";
                $params['matricule'] = $connectedMatricule;
            }
        }

        $sql .= " ORDER BY e.matricule ASC, m.reference ASC";

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $maintenances = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Récupérer les options pour les filtres admin
        $chainOptions = [];
        $machineOptions = [];
        $matriculeOptions = [];

        if ($isAdmin) {
            // Récupérer toutes les chaînes
            $stmt = $conn->query("SELECT DISTINCT location_name FROM gmao__location WHERE location_category IN ('prodline', 'parc') ORDER BY location_name");
            $chains = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $chainOptions = array_combine($chains, $chains);

            // Récupérer toutes les machines avec ID et référence
            $stmt = $conn->query("SELECT DISTINCT machine_id, reference FROM init__machine ORDER BY reference");
            $machinesData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $machineOptions = [];
            foreach ($machinesData as $machine) {
                $machineId = $machine['machine_id'] ?? '';
                $machineRef = $machine['reference'] ?? '';
                if (!empty($machineId) && !empty($machineRef)) {
                    $displayValue = $machineId . ' - ' . $machineRef;
                    $machineOptions[$displayValue] = $displayValue;
                } elseif (!empty($machineId)) {
                    $machineOptions[$machineId] = $machineId;
                } elseif (!empty($machineRef)) {
                    $machineOptions[$machineRef] = $machineRef;
                }
            }

            // Récupérer tous les matricules de maintenanciers
            $stmt = $conn->query("SELECT  DISTINCT e.matricule FROM init__employee e 
               where e.qualification = 'MAINTAINER'
                ORDER BY e.matricule");
            $matricules = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $matriculeOptions = array_combine($matricules, $matricules);
        }

        include __DIR__ . '/../views/inventaire/maintenancier_machine.php';
    }
    public function maintenance_default()
    {
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();
        $sql = "
            SELECT
                e.matricule AS maintener_name,
                mm.maintener_id,
                GROUP_CONCAT(DISTINCT CONCAT(m.reference, '|', COALESCE(s.status_name, 'unknown')) ORDER BY m.reference SEPARATOR '||') AS machines_with_status,
                GROUP_CONCAT(DISTINCT l.location_name ORDER BY l.location_name SEPARATOR ', ') AS chains_list
            FROM (
                SELECT mm.*
                FROM gmao__machine_maint mm
                INNER JOIN (
                    SELECT MAX(id) AS id
                    FROM gmao__machine_maint
                    GROUP BY machine_id
                ) last ON last.id = mm.id
            ) mm
            LEFT JOIN init__employee e ON e.id = mm.maintener_id
            LEFT JOIN init__machine m ON m.id = mm.machine_id
            LEFT JOIN gmao__location l ON l.id = mm.location_id
            LEFT JOIN gmao__status s ON s.id = m.machines_status_id
            GROUP BY mm.maintener_id
            ORDER BY e.matricule ASC
        ";
        $stmt = $conn->query($sql);
        $maintenances = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        include __DIR__ . '/../views/inventaire/maintenance_defaultold.php';
    }

    public function AddInventaire()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
            if ($isAdmin) {
                $this->handleAddInventaireAdmin();
                return;
            } else {
                $this->handleAddInventaire();
                return;
            }
        }
        // Préparer les données pour le formulaire
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        $isAdmin = isset($_SESSION['qualification']) && $_SESSION['qualification'] === 'ADMINISTRATEUR';
        $connectedMaintenerId = null;

        // Options maintenanciqualificationer (si admin)
        if ($isAdmin) {
            $maintenancier = Maintainer_model::findAll();
        } else {
            $connectedMaintenerId = $_SESSION['user']['id'] ?? null;
            $connectedMatricule = $_SESSION['user']['matricule'];
        }
        // Options location
        $stmt = $conn->query("SELECT id, location_name FROM gmao__location ORDER BY location_name ASC");
        $locationOptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Options status
        $stmt = $conn->query("SELECT id, status_name FROM gmao__status ORDER BY status_name ASC");
        $statusOptions = $stmt->fetchAll(\PDO::FETCH_ASSOC);


        include __DIR__ . '/../views/inventaire/add_inventaire.php';
    }

    private function handleAddInventaire()
    {
        $machineIds = $_POST['machines_ids'] ?? '';
        $maintener_id = $_POST['maintener_id'] ?? '';
        $location_id = $_POST['location_id'] ?? '';
        $status_id = $_POST['status_id'] ?? '';
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        try {

            $machineIds = is_array($machineIds) ? $machineIds : (trim((string)$machineIds) !== '' ? [trim((string)$machineIds)] : []);
            if (count($machineIds) === 0) {
                $_SESSION['flash_error'] = "Au moins une machine est requise";
                header('Location: index.php?route=ajouterInventaire');
                return;
            }
            $machineNotfound = [];
            $ok = 0;
            $conn->beginTransaction();

            $resolveStmt = $conn->prepare("SELECT id FROM init__machine WHERE machine_id = :mid LIMIT 1");
            $insertInv = $conn->prepare("INSERT INTO gmao__historique_inventaire (machine_id, maintainer_id, location_id, status_id, created_at) VALUES (:machine_id, :maintainer_id, :location_id, :status_id, :created_at)");

            foreach ($machineIds as $displayMid) {
                $displayMid = trim((string)$displayMid);
                if ($displayMid === '') {
                    continue;
                }
                // Resolve init__machine.id from public machine_id
                $resolveStmt->execute([':mid' => $displayMid]);
                $resolvedId = $resolveStmt->fetchColumn();
                if (!$resolvedId) {
                    $machineNotfound[] = $displayMid;
                    continue;
                }


                // Insert inventory row
                $insertInv->execute([
                    ':machine_id' => (int)$resolvedId,
                    ':maintainer_id' => $maintener_id !== '' ? (int)$maintener_id : null,
                    ':location_id' => $location_id !== '' ? (int)$location_id : null,
                    ':status_id' => $status_id !== '' ? (int)$status_id : null,
                    ':created_at' => date('Y-m-d H:i:s')
                ]);
                $ok++;
                //insert into gmao__machine_maint
                $stmt = $conn->prepare("INSERT INTO gmao__machine_maint (machine_id, maintener_id, location_id) VALUES (:machine_id, :maintener_id, :location_id)");
                $stmt->execute([':machine_id' => $resolvedId, ':maintener_id' => $maintener_id, ':location_id' => $location_id]);
                //update into init__machine location_id and status_id
                $stmt = $conn->prepare("UPDATE init__machine SET machines_status_id = :status_id, machines_location_id = :location_id WHERE id = :machine_id");
                $stmt->execute([':status_id' => $status_id, ':location_id' => $location_id, ':machine_id' => $resolvedId]);

                // Audit trail pour non-admin
                $this->logAuditNonAdmin($resolvedId, $maintener_id, $location_id, $status_id);
            }

            $conn->commit();
            $_SESSION['flash_success'] = "Machine ajoutée avec succès: $ok, Machines introuvables: " . (count($machineNotfound) ? implode(',machine id ', $machineNotfound) : '0');
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['flash_error'] = 'Erreur: ' . $e->getMessage();
        }

        header('Location: index.php?route=ajouterInventaire');
    }
    public function handleAddInventaireAdmin()
    {
        $machineIds = $_POST['machines_ids'] ?? '';
        $maintener_id = $_POST['maintener_id'] ?? '';
        $location_id = $_POST['location_id'] ?? '';
        $status_id = $_POST['status_id'] ?? '';
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        try {
            $machineIds = is_array($machineIds) ? $machineIds : (trim((string)$machineIds) !== '' ? [trim((string)$machineIds)] : []);
            if (count($machineIds) === 0) {
                $_SESSION['flash_error'] = "Au moins une machine est requise";
                header('Location: index.php?route=ajouterInventaire');
                return;
            }
            $machineNotfound = [];
            $ok = 0;
            $conn->beginTransaction();
            $resolveStmt = $conn->prepare("SELECT id FROM init__machine WHERE machine_id = :mid LIMIT 1");
            $insertInv = $conn->prepare("INSERT INTO gmao__historique_inventaire (machine_id, maintainer_id, location_id, status_id, created_at) VALUES (:machine_id, :maintainer_id, :location_id, :status_id, :created_at)");
            foreach ($machineIds as $displayMid) {
                $displayMid = trim((string)$displayMid);
                if ($displayMid === '') {
                    continue;
                }
                $resolveStmt->execute([':mid' => $displayMid]);
                $resolvedId = $resolveStmt->fetchColumn();
                if (!$resolvedId) {
                    $machineNotfound[] = $displayMid;
                    continue;
                }
                $insertInv->execute([
                    ':machine_id' => (int)$resolvedId,
                    ':maintainer_id' => (int)$maintener_id,
                    ':location_id' => $location_id !== '' ? (int)$location_id : null,
                    ':status_id' => $status_id !== '' ? (int)$status_id : null,
                    ':created_at' => date('Y-m-d H:i:s')
                ]);
                $ok++;

                // Audit trail pour admin (seulement gmao__historique_inventaire)
                $this->logAuditAdmin($resolvedId, $maintener_id, $location_id, $status_id);
            }
            $conn->commit();
            $_SESSION['flash_success'] = "Machine ajoutée avec succès: $ok, Machines introuvables: " . (count($machineNotfound) ? implode(',machine id ', $machineNotfound) : '0');
        } catch (\Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $_SESSION['flash_error'] = 'Erreur: ' . $e->getMessage();
        }
        header('Location: index.php?route=ajouterInventaire');
    }

    /**
     * 1. Audit pour IMPORT : gmao__inventaire_machine, init__machine, gmao__machine_maint
     * @param int $machineId ID de la machine
     * @param int $maintenerId ID du maintenancier
     * @param int $locationId ID de la location
     * @param int|null $statusId ID du statut
     */
    private function logAuditImport($machineId, $maintenerId, $locationId, $statusId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            // 1. gmao__inventaire_machine (ADD)
            $newValue = [
                'machine_id' => $machineId,
                'maintener_id' => $maintenerId,
                'location_id' => $locationId,
                'status_id' => $statusId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__inventaire_machine', null, $newValue);

            // 2. init__machine (UPDATE)
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();
            $oldValuesStmt = $conn->prepare("SELECT machines_status_id, machines_location_id FROM init__machine WHERE id = :machine_id");
            $oldValuesStmt->execute([':machine_id' => $machineId]);
            $oldValues = $oldValuesStmt->fetch(\PDO::FETCH_ASSOC);

            $oldValue = [
                'id' => $machineId,
                'machines_status_id' => $oldValues['machines_status_id'],
                'machines_location_id' => $oldValues['machines_location_id']
            ];

            $newValue = [
                'id' => $machineId,
                'machines_status_id' => $statusId,
                'machines_location_id' => $locationId
            ];
            AuditTrail_model::logAudit($userMatricule, 'update', 'init__machine', $oldValue, $newValue);

            // 3. gmao__machine_maint (ADD)
            $newValue = [
                'machine_id' => $machineId,
                'maintener_id' => $maintenerId,
                'location_id' => $locationId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__machine_maint', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit import: " . $e->getMessage());
        }
    }

    /**
     * 2. Audit pour NON-ADMIN : gmao__historique_inventaire, init__machine, gmao__machine_maint
     * @param int $machineId ID de la machine
     * @param int $maintenerId ID du maintenancier
     * @param int $locationId ID de la location
     * @param int|null $statusId ID du statut
     */
    private function logAuditNonAdmin($machineId, $maintenerId, $locationId, $statusId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            // 1. gmao__historique_inventaire (ADD)
            $newValue = [
                'machine_id' => $machineId,
                'maintainer_id' => $maintenerId,
                'location_id' => $locationId,
                'status_id' => $statusId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__historique_inventaire', null, $newValue);

            // 2. init__machine (UPDATE)
            $db = Database::getInstance('db_digitex');
            $conn = $db->getConnection();
            $oldValuesStmt = $conn->prepare("SELECT machines_status_id, machines_location_id FROM init__machine WHERE id = :machine_id");
            $oldValuesStmt->execute([':machine_id' => $machineId]);
            $oldValues = $oldValuesStmt->fetch(\PDO::FETCH_ASSOC);

            $oldValue = [
                'id' => $machineId,
                'machines_status_id' => $oldValues['machines_status_id'],
                'machines_location_id' => $oldValues['machines_location_id']
            ];

            $newValue = [
                'id' => $machineId,
                'machines_status_id' => $statusId,
                'machines_location_id' => $locationId
            ];
            AuditTrail_model::logAudit($userMatricule, 'update', 'init__machine', $oldValue, $newValue);

            // 3. gmao__machine_maint (ADD)
            $newValue = [
                'machine_id' => $machineId,
                'maintener_id' => $maintenerId,
                'location_id' => $locationId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__machine_maint', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit non-admin: " . $e->getMessage());
        }
    }

    /**
     * 3. Audit pour ADMIN : gmao__historique_inventaire seulement
     * @param int $machineId ID de la machine
     * @param int $maintenerId ID du maintenancier
     * @param int $locationId ID de la location
     * @param int|null $statusId ID du statut
     */
    private function logAuditAdmin($machineId, $maintenerId, $locationId, $statusId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            // gmao__historique_inventaire (ADD seulement)
            $newValue = [
                'machine_id' => $machineId,
                'maintainer_id' => $maintenerId,
                'location_id' => $locationId,
                'status_id' => $statusId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__historique_inventaire', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit admin: " . $e->getMessage());
        }
    }
}
  


// if ($ext === 'csv') {
//     if (($handle = fopen($tmpPath, 'r')) !== false) {
//         // Read header
//         $header = fgetcsv($handle, 0, ';');
//         if ($header === false) {
//             $header = fgetcsv($handle, 0, ',');
//         }
//         if ($header === false) {
//             $_SESSION['flash_error'] = 'CSV vide ou illisible';
//             header('Location: index.php?route=importInventaire');
//             exit;
//         }
//         // normalise header
//         $header = array_map(function($h){ return strtolower(trim($h)); }, $header);
//         while (($data = fgetcsv($handle, 0, ';')) !== false) {
//             if (count($data) === 1) {
//                 // maybe comma separated
//                 $data = str_getcsv($data[0], ',');
//             }
//             if (count($data) !== count($header)) {
//                 continue;
//             }
//             $rows[] = array_combine($header, $data);
//         }
//         fclose($handle);
//     }
// }