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
        //insert into gmao__inventaire_machine - VERSION OPTIMISÉE avec fallback
        try {
            $this->save_inventaire_machine_optimized($conn, $rows);
        } catch (\Throwable $e) {
            error_log("Erreur avec la version optimisée, utilisation de la version classique: " . $e->getMessage());
            $conn->rollBack();
            $conn->beginTransaction();
        $this->save_inventaire_machine($conn, $rows);
        }


        header('Location: index.php?route=importInventaire');
        exit;
    }

    /**
     * VERSION OPTIMISÉE: Seulement 2 connexions à la base de données
     * 1. Charger toutes les données de référence
     * 2. Traiter en mémoire PHP
     * 3. Insérer toutes les données validées
     */
    private function save_inventaire_machine_optimized($conn, $rows)
    {
        try {
            // ÉTAPE 1: Charger TOUTES les données de référence en une seule fois
            $referenceData = $this->loadAllReferenceData($conn);
            
            // ÉTAPE 2: Traiter TOUTES les données Excel en mémoire PHP
            $validatedData = $this->processAllExcelDataInMemory($rows, $referenceData);
            
            // ÉTAPE 3: Insérer TOUTES les données validées en une seule fois
            $insertSuccess = $this->insertAllValidatedData($conn, $validatedData);
            
            if ($insertSuccess) {
                $conn->commit();
                
                // Message de succès avec statistiques détaillées
                $message = sprintf(
                    //'Import effectué avec succès!
                    //  Machines mises à jour: %d/%d| Lignes ignorées: %d | Machines trouvées: %d | Machines crées: %d | Inventaires ajoutés: %d ',
                    // $validatedData['stats']['rows_processed'],
                    // $validatedData['stats']['total_rows'],
                    // $validatedData['stats']['rows_skipped'],
                    // $validatedData['stats']['machines_found'],
                    // $validatedData['stats']['machines_created'],
                    // count($validatedData['inventaire']
                    'Import effectué avec succès!',
                    
                );
                $_SESSION['flash_success'] = $message;
            } else {
                $conn->rollBack();
                $_SESSION['flash_error'] = 'Erreur lors de l\'insertion des données validées';
            }
            
        } catch (\Throwable $e) {
            $conn->rollBack();
            error_log("Erreur détaillée import optimisé: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['flash_error'] = 'Erreur lors de l\'import optimisé: ' . $e->getMessage();
        }
    }

    private function save_inventaire_machine($conn, $rows)
    {


        $stats = [
            'total_rows' => count($rows),
            'machines_created' => 0,
            'machines_found' => 0,
            'rows_processed' => 0,
            'rows_skipped' => 0
        ];

        try {
            // Vérifier l'existence d'une ligne avec même machine_id et la même date
            $checkDuplicate = $conn->prepare("SELECT id FROM gmao__inventaire_machine WHERE machine_id = :machine_id AND DATE(created_at) = :current_date LIMIT 1");

            foreach ($rows as $row) {
                // 1. Validation et récupération de l'ID machine
                $machineId = $this->validateAndGetMachineId($conn, $row, $stats);
                if ($machineId === null) {
                        continue;
                    }

                // Vérifier s'il existe déjà une ligne avec le même machine_id et la même date
                $currentDate = date('Y-m-d');
                $checkDuplicate->execute([
                    ':machine_id' => $machineId,
                    ':current_date' => $currentDate
                ]);

                // 2. Validation et récupération de l'ID location
                $locationId = $this->validateAndGetLocationId($conn, $row, $stats);
                if ($locationId === null && trim((string)$row[9]) !== '') {
                    continue; // Location invalide (ex: FERAILLE)
                }

                // 3. Détermination du statut
                $location_idxl = trim((string)$row[9]);
                $location_category = null;
                if ($locationId !== null) {
                    $location_category_stmt = $conn->prepare("SELECT location_category FROM gmao__location WHERE id = :location_id");
                    $location_category_stmt->execute([':location_id' => $locationId]);
                    $location_category = $location_category_stmt->fetchColumn();
                }
                
                $status = $this->determineStatus($row, $location_idxl, $location_category);
                $statusId = $this->getStatusId($conn, $status);

                // 4. Mise à jour de init__machine AVANT la section maintenancier
                $this->updateMachineStatusAndLocation($conn, $machineId, $statusId, $locationId);

                // 5. Validation et récupération de l'ID maintenancier
                $maintenerId = $this->validateAndGetMaintenerId($conn, $row, $stats);
                if ($maintenerId === null) {
                            continue;
                }

                // 6. Insertions dans les tables gmao__inventaire_machine
                $insertSuccess = $this->insertInventoryAndMaintenance($conn, $machineId, $maintenerId, $locationId, $statusId);
                if (!$insertSuccess) {
                    $stats['rows_skipped']++;
                    continue;
                }

                // 7. Audit trail
                $this->logAuditImport($machineId, $maintenerId, $locationId, $statusId);
                $stats['rows_processed']++;
            }
            $conn->commit();

            // Message de succès avec statistiques détaillées
            $message = sprintf(
                'Import effectué avec succès! Lignes traitées: %d/%d| Lignes ignorées: %d | Machines trouvées: %d | Machines crées: %d ',
                $stats['rows_processed'],
                $stats['total_rows'],
               
                $stats['rows_skipped'],
                $stats['machines_found'],
                $stats['machines_created']
            );
            $_SESSION['flash_success'] = $message;
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
            if($machineNotfound !== []){
                $_SESSION['flash_error'] = " Machines introuvables: " . (count($machineNotfound) ? implode(',machine id ', $machineNotfound) : '0');

            }else{
                $_SESSION['flash_success'] = "Machine inventairees avec succès!, $ok machines ajoutées";
            }
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

    /**
     * Crée une nouvelle machine dans init__machine avec les données du fichier Excel
     * @param PDO $conn Connexion à la base de données
     * @param array $row Ligne du fichier Excel
     * @return int|null ID de la machine créée ou null en cas d'échec
     */
    private function createNewMachine($conn, $row)
    {
        try {
            // Mapping des colonnes Excel vers les champs de la table init__machine
            // Basé sur votre structure: machine_id=row[0], reference=row[18], etc.
            $machineData = [
                'machine_id' => trim((string)($row[10] ?? '')),      // Colonne A (machine_id)
                'reference' => trim((string)($row[17] ?? '')),
                'brand' => trim((string)($row[4] ?? '')),           // Colonne B (brand) mafque
                'type' => trim((string)($row[2] ?? '')),
                'designation' => trim((string)($row[3] ?? '')),
                'billing_num' => trim((string)($row[19] ?? '')),
                'bill_date' => $this->parseDate($row[20] ?? ''),
                'cur_date' => date('Y-m-d'),                        // Date actuelle
                'machines_location_id' => null,                     // Sera mis à jour plus tard
                'machines_status_id' => null                        // Sera mis à jour plus tard
            ];

            // Validation des champs obligatoires
            if (empty($machineData['machine_id'])) {
                error_log("Impossible de créer la machine: machine_id manquant");
                return null;
            }

            // Vérifier si la machine n'existe pas déjà (double vérification)
            $checkStmt = $conn->prepare("SELECT id FROM init__machine WHERE machine_id = ? LIMIT 1");
            $checkStmt->execute([$machineData['machine_id']]);
            if ($checkStmt->fetchColumn()) {
                // La machine existe déjà, récupérer son ID
                $checkStmt->execute([$machineData['machine_id']]);
                return (int)$checkStmt->fetchColumn();
            }

            // Préparer la requête d'insertion
            $insertStmt = $conn->prepare("
                INSERT INTO init__machine (
                    machine_id, reference, brand, type, designation, 
                    billing_num, bill_date,  cur_date, 
                    machines_location_id, machines_status_id, created_at
                ) VALUES (?, ?, ?, ?, ?, ?,  ?, ?, ?, ?, NOW())
            ");

            // Exécuter l'insertion
            $result = $insertStmt->execute([
                $machineData['machine_id'],
                $machineData['reference'],
                $machineData['brand'],
                $machineData['type'],
                $machineData['designation'],
                $machineData['billing_num'],
                $machineData['bill_date'],

                $machineData['cur_date'],
                $machineData['machines_location_id'],
                $machineData['machines_status_id']
            ]);

            if ($result) {
                $newMachineId = $conn->lastInsertId();
                error_log("Nouvelle machine crée: ID={$newMachineId}, machine_id={$machineData['machine_id']}");
                return (int)$newMachineId;
            } else {
                error_log("Échec de création de la machine: " . implode(', ', $insertStmt->errorInfo()));
                return null;
            }
        } catch (\Throwable $e) {
            error_log("Erreur lors de la création de la machine: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse une date depuis le fichier Excel
     * @param mixed $dateValue Valeur de date du fichier Excel
     * @return string|null Date au format Y-m-d ou null
     */
    private function parseDate($dateValue)
    {
        if (empty($dateValue)) {
            return null;
        }

        // Si c'est déjà une date au bon format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
            return $dateValue;
        }

        // Si c'est un timestamp Excel
        if (is_numeric($dateValue)) {
            $timestamp = ($dateValue - 25569) * 86400; // Conversion Excel vers Unix timestamp
            return date('Y-m-d', $timestamp);
        }

        // Essayer de parser avec strtotime
        $parsed = strtotime($dateValue);
        if ($parsed !== false) {
            return date('Y-m-d', $parsed);
        }

        return null;
    }

    /**
     * Fonction privée pour valider et récupérer les données machine
     * @param PDO $conn Connexion à la base de données
     * @param array $row Ligne du fichier Excel
     * @param array $stats Statistiques (passé par référence)
     * @return int|null ID de la machine ou null si échec
     */
    private function validateAndGetMachineId($conn, $row, &$stats)
    {
        $machine_idxl = trim((string)$row[10]);
        
        if ($machine_idxl === '') {
            $stats['rows_skipped']++;
            return null;
        }

        $idMachine = $conn->prepare("SELECT id FROM init__machine WHERE machine_id = :id_machine LIMIT 1");
        $idMachine->execute([':id_machine' => $machine_idxl]);
        $machineId = $idMachine->fetchColumn();
        
        if ($machineId === false || $machineId === null || $machineId === '') {
            // Créer une nouvelle machine dans init__machine
            $machineId = $this->createNewMachine($conn, $row);
            if ($machineId === null) {
                $stats['rows_skipped']++;
                return null;
            }
            $stats['machines_created']++;
        } else {
            $stats['machines_found']++;
        }
        
        return (int)$machineId;
    }

    /**
     * Fonction privée pour gérer les locations
     * @param PDO $conn Connexion à la base de données
     * @param array $row Ligne du fichier Excel
     * @param array $stats Statistiques (passé par référence)
     * @return int|null ID de la location ou null
     */
    private function validateAndGetLocationId($conn, $row, &$stats)
    {
        $location_idxl = trim((string)$row[9]);
        
        if ($location_idxl === '') {
            return null;
        }

        $idLocation = $conn->prepare("SELECT id FROM gmao__location WHERE location_name = :id_location LIMIT 1");
        $location_category = $conn->prepare("SELECT location_category FROM gmao__location WHERE location_name = :id_location");
        
        $location_category->execute([':id_location' => $location_idxl]);
        $location_category = $location_category->fetchColumn();
        
        $idLocation->execute([':id_location' => $location_idxl]);
        $locationId = $idLocation->fetchColumn();
        
        if (empty($locationId)) {
            if (strpos($location_idxl, 'CH') === 0 || strpos($location_idxl, 'ECH') === 0 || strpos($location_idxl, 'ch') === 0) {
                $location_category = 'prodline';
            } elseif ($location_idxl === "FERAILLE") {
                $stats['rows_skipped']++;
                return null;
            } else {
                $location_category = 'parc';
            }
            
            $stmt = $conn->prepare("INSERT INTO gmao__location (location_name, location_category) VALUES (:location_name, :location_category)");
            $stmt->execute([':location_name' => $location_idxl, ':location_category' => $location_category]);
            $locationId = $conn->lastInsertId();
        }
        
        return ($locationId !== false && $locationId !== null && $locationId !== '') ? (int)$locationId : null;
    }

    /**
     * Fonction privée pour gérer les statuts
     * @param array $row Ligne du fichier Excel
     * @param string $location_idxl Nom de la location
     * @param string $location_category Catégorie de la location
     * @return string|null Nom du statut ou null
     */
    private function determineStatus($row, $location_idxl, $location_category)
    {
        $status_idxl = trim((string)$row[18]);
        
        if ($status_idxl === '') {
            return null;
        }
        
        if ($status_idxl === "NON OK" && $location_category === "parc") {
            return "en panne";
        } elseif ($status_idxl === "NON OK" && $location_idxl === "ANNEX CHIBA") {
            
            return "ferraille";
        } elseif ($status_idxl === "OK" && $location_category === "parc") {
            return "inactive";
        } elseif ($status_idxl === "NON OK" && $location_category === "prodline") {
            return "inactive";
        } elseif ($status_idxl === "OK" && $location_category === "prodline") {
            return "active";
        }
        
        return null;
    }

    /**
     * Fonction privée pour récupérer l'ID du statut
     * @param PDO $conn Connexion à la base de données
     * @param string|null $status Nom du statut
     * @return int|null ID du statut ou null
     */
    private function getStatusId($conn, $status)
    {
        if ($status === null) {
            return null;
        }
        
        $idStatus = $conn->prepare("SELECT id FROM gmao__status WHERE status_name = :id_status LIMIT 1");
        $idStatus->execute([':id_status' => $status]);
        $statusId = $idStatus->fetchColumn();
        
        return ($statusId !== false && $statusId !== null && $statusId !== '') ? (int)$statusId : null;
    }

    /**
     * Fonction privée pour mettre à jour init__machine
     * @param PDO $conn Connexion à la base de données
     * @param int $machineId ID de la machine
     * @param int|null $statusId ID du statut
     * @param int|null $locationId ID de la location
     * @return bool Succès de la mise à jour
     */
    private function updateMachineStatusAndLocation($conn, $machineId, $statusId, $locationId)
    {
        $stmt = $conn->prepare("UPDATE init__machine SET machines_status_id = :status_id, machines_location_id = :location_id WHERE id = :machine_id");
        return $stmt->execute([':status_id' => $statusId, ':location_id' => $locationId, ':machine_id' => $machineId]);
    }

    /**
     * Fonction privée pour valider le maintenancier
     * @param PDO $conn Connexion à la base de données
     * @param array $row Ligne du fichier Excel
     * @param array $stats Statistiques (passé par référence)
     * @return int|null ID du maintenancier ou null si échec
     */
    private function validateAndGetMaintenerId($conn, $row, &$stats)
    {
        $maintener_idxl = trim((string)$row[7]);
        
        if ($maintener_idxl === '') {
            $stats['rows_skipped']++;
            return null;
        }
        
        $idMaintener = $conn->prepare("SELECT id FROM init__employee WHERE matricule = :id_maintener LIMIT 1");
        $idMaintener->execute([':id_maintener' => $maintener_idxl]);
        $maintenerId = $idMaintener->fetchColumn();
        
        if ($maintenerId === false || $maintenerId === null || $maintenerId === '') {
            $stats['rows_skipped']++;
            return null;
        }
        
        return (int)$maintenerId;
    }

    /**
     * Fonction privée pour effectuer les insertions dans les tables
     * @param PDO $conn Connexion à la base de données
     * @param int $machineId ID de la machine
     * @param int $maintenerId ID du maintenancier
     * @param int|null $locationId ID de la location
     * @param int|null $statusId ID du statut
     * @return bool Succès des insertions
     */
    private function insertInventoryAndMaintenance($conn, $machineId, $maintenerId, $locationId, $statusId)
    {
        try {
            // 1. INSERT dans gmao__inventaire_machine
            $insertInventaire = $conn->prepare("INSERT INTO gmao__inventaire_machine (machine_id, maintener_id, location_id, status_id, created_at) VALUES (:machine_id, :maintener_id, :location_id, :status_id, :created_at)");
            $insertInventaire->execute([
                ':machine_id' => $machineId,
                ':maintener_id' => $maintenerId,
                ':location_id' => $locationId,
                ':status_id' => $statusId,
                ':created_at' => date('Y-m-d H:i:s')
            ]);
            
            // 2. INSERT dans gmao__machine_maint
            $insertMachineMaint = $conn->prepare("INSERT INTO gmao__machine_maint (machine_id, maintener_id, location_id) VALUES (:machine_id, :maintener_id, :location_id)");
            $insertMachineMaint->execute([
                ':machine_id' => $machineId,
                ':maintener_id' => $maintenerId,
                ':location_id' => $locationId
            ]);
            
            return true;
        } catch (\Throwable $e) {
            error_log("Erreur lors des insertions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * OPTIMISATION: Charge toutes les données de référence en une seule fois
     * @param PDO $conn Connexion à la base de données
     * @return array Toutes les données de référence indexées
     */
    private function loadAllReferenceData($conn)
    {
        $referenceData = [
            'machines' => [],
            'employees' => [],
            'locations' => [],
            'statuses' => [],
            'existing_inventaire' => []
        ];

        try {
            // 1. Charger toutes les machines
            $stmt = $conn->query("SELECT id, machine_id FROM init__machine");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $referenceData['machines'][$row['machine_id']] = (int)$row['id'];
            }

            // 2. Charger tous les employés
            $stmt = $conn->query("SELECT id, matricule FROM init__employee");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $referenceData['employees'][$row['matricule']] = (int)$row['id'];
            }

            // 3. Charger toutes les locations
            $stmt = $conn->query("SELECT id, location_name, location_category FROM gmao__location");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $referenceData['locations'][$row['location_name']] = [
                    'id' => (int)$row['id'],
                    'category' => $row['location_category']
                ];
            }

            // 4. Charger tous les statuts
            $stmt = $conn->query("SELECT id, status_name FROM gmao__status");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $referenceData['statuses'][$row['status_name']] = (int)$row['id'];
            }

            // 5. Charger l'inventaire existant pour aujourd'hui
            $today = date('Y-m-d');
            $stmt = $conn->prepare("SELECT machine_id FROM gmao__inventaire_machine WHERE DATE(created_at) = :today");
            $stmt->execute([':today' => $today]);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $referenceData['existing_inventaire'][(int)$row['machine_id']] = true;
            }

        } catch (\Throwable $e) {
            error_log("Erreur lors du chargement des données de référence: " . $e->getMessage());
        }

        return $referenceData;
    }

    /**
     * OPTIMISATION: Traite toutes les données Excel en mémoire PHP
     * @param array $rows Données Excel
     * @param array $referenceData Données de référence chargées
     * @return array Données validées prêtes pour insertion
     */
    private function processAllExcelDataInMemory($rows, $referenceData)
    {
        $validatedData = [
            'inventaire' => [],
            'machine_maint' => [],
            'machine_updates' => [],
            'new_locations' => [],
            'new_machines' => [],
            'stats' => [
                'total_rows' => count($rows),
                'machines_created' => 0,
                'machines_found' => 0,
                'rows_processed' => 0,
                'rows_skipped' => 0
            ]
        ];

        foreach ($rows as $rowIndex => $row) {
            // 1. Validation machine
            $machine_idxl = trim((string)($row[10] ?? ''));
            if ($machine_idxl === '') {
                $validatedData['stats']['rows_skipped']++;
                continue;
            }

            $machineId = $referenceData['machines'][$machine_idxl] ?? null;
            if ($machineId === null) {
                // Vérifier si cette machine n'a pas déjà été ajoutée dans new_machines
                $alreadyAdded = false;
                foreach ($validatedData['new_machines'] as $newMachine) {
                    if ($newMachine['machine_id'] === $machine_idxl) {
                        $alreadyAdded = true;
                        $machineId = -array_search($newMachine, $validatedData['new_machines']) - 1;
                        break;
                    }
                }
                
                if (!$alreadyAdded) {
                    // Créer une nouvelle machine seulement si elle n'existe pas
                    $newMachine = $this->prepareNewMachineData($row);
                    if ($newMachine === null) {
                        $validatedData['stats']['rows_skipped']++;
                        continue;
                    }
                    $validatedData['new_machines'][] = $newMachine;
                    $validatedData['stats']['machines_created']++;
                    // Utiliser un ID temporaire négatif pour le suivi
                    $machineId = -count($validatedData['new_machines']);
                }
            } else {
                $validatedData['stats']['machines_found']++;
            }

            // Vérifier si déjà dans l'inventaire d'aujourd'hui
            if (isset($referenceData['existing_inventaire'][$machineId])) {
                $validatedData['stats']['rows_skipped']++;
                continue;
            }

            // 2. Validation location
            $location_idxl = trim((string)($row[9] ?? ''));
            $locationId = null;
            $location_category = null;

            if ($location_idxl !== '') {
                // Vérifier d'abord dans les locations existantes
                if (isset($referenceData['locations'][$location_idxl])) {
                    $locationId = $referenceData['locations'][$location_idxl]['id'];
                    $location_category = $referenceData['locations'][$location_idxl]['category'];
                } else {
                    // Vérifier si cette location n'a pas déjà été ajoutée dans new_locations
                    $alreadyAdded = false;
                    foreach ($validatedData['new_locations'] as $newLocation) {
                        if ($newLocation['location_name'] === $location_idxl) {
                            $alreadyAdded = true;
                            $locationId = -array_search($newLocation, $validatedData['new_locations']) - 1;
                            $location_category = $newLocation['location_category'];
                            break;
                        }
                    }
                    
                    if (!$alreadyAdded) {
                        // Créer une nouvelle location seulement si elle n'existe pas
                        $location_category = $this->determineLocationCategory($location_idxl);
                        if ($location_category === null) {
                            $validatedData['stats']['rows_skipped']++;
                            continue; // FERAILLE
                        }
                        $validatedData['new_locations'][] = [
                            'location_name' => $location_idxl,
                            'location_category' => $location_category
                        ];
                        // Utiliser un ID temporaire négatif
                        $locationId = -count($validatedData['new_locations']);
                    }
                }
            }

            // 3. Détermination du statut
            $status = $this->determineStatus($row, $location_idxl, $location_category);
            $statusId = $status ? ($referenceData['statuses'][$status] ?? null) : null;

            // 4. PRIORITÉ: Toujours mettre à jour init__machine si on a location ou status
            if ($locationId !== null || $statusId !== null) {
                $validatedData['machine_updates'][] = [
                    'machine_id' => $machineId,
                    'status_id' => $statusId,
                    'location_id' => $locationId
                ];
                $validatedData['stats']['rows_processed']++;
            }

            // 5. Validation maintenancier (optionnel pour les autres tables)
            $maintener_idxl = trim((string)($row[7] ?? ''));
            $maintenerId = null;
            
            if ($maintener_idxl !== '') {
                $maintenerId = $referenceData['employees'][$maintener_idxl] ?? null;
            }

            // 6. Insérer dans les autres tables SEULEMENT si maintenancier trouvé
            if ($maintenerId !== null) {
                $validatedData['inventaire'][] = [
                    'machine_id' => $machineId,
                    'maintener_id' => $maintenerId,
                    'location_id' => $locationId,
                    'status_id' => $statusId,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $validatedData['machine_maint'][] = [
                    'machine_id' => $machineId,
                    'maintener_id' => $maintenerId,
                    'location_id' => $locationId
                ];
            } else {
                // Maintenancier non trouvé, mais init__machine déjà mis à jour
                $validatedData['stats']['rows_skipped']++;
            }
        }

        return $validatedData;
    }

    /**
     * OPTIMISATION: Insère toutes les données validées en une seule fois
     * @param PDO $conn Connexion à la base de données
     * @param array $validatedData Données validées
     * @return bool Succès de l'insertion
     */
    private function insertAllValidatedData($conn, $validatedData)
    {
        try {
            // 1. Insérer les nouvelles locations (éviter les doublons)
            if (!empty($validatedData['new_locations'])) {
                error_log("Insertion de " . count($validatedData['new_locations']) . " nouvelles locations");
                $locationStmt = $conn->prepare("INSERT IGNORE INTO gmao__location (location_name, location_category) VALUES (?, ?)");
                foreach ($validatedData['new_locations'] as $location) {
                    $locationStmt->execute([$location['location_name'], $location['location_category']]);
                }
            }

            // 2. Insérer les nouvelles machines (éviter les doublons)
            if (!empty($validatedData['new_machines'])) {
                error_log("Insertion de " . count($validatedData['new_machines']) . " nouvelles machines");
                $machineStmt = $conn->prepare("
                    INSERT IGNORE INTO init__machine (
                        machine_id, reference, brand, type, designation, 
                        billing_num, bill_date, cur_date, 
                        machines_location_id, machines_status_id, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                foreach ($validatedData['new_machines'] as $index => $machine) {
                    // Log des données avant insertion pour debug
                    error_log("Machine $index: type='" . $machine['type'] . "' (longueur: " . strlen($machine['type']) . ")");
                    
                    // Tronquer les champs trop longs
                    $machine['type'] = substr($machine['type'], 0, 16); // Limiter à 16 caractères
                    $machine['brand'] = substr($machine['brand'], 0, 24); // Limiter à 24 caractères
                    $machine['designation'] = substr($machine['designation'], 0, 48); // Limiter à 48 caractères
                    $machine['reference'] = substr($machine['reference'], 0, 16); // Limiter à 16 caractères
                    $machine['billing_num'] = substr($machine['billing_num'], 0, 10); // Limiter à 10 caractères
                    
                    $machineStmt->execute([
                        $machine['machine_id'], $machine['reference'], $machine['brand'],
                        $machine['type'], $machine['designation'], $machine['billing_num'],
                        $machine['bill_date'], $machine['cur_date'],
                        $machine['machines_location_id'], $machine['machines_status_id']
                    ]);
                }
            }

            // 3. Mettre à jour les IDs temporaires avec les vrais IDs
            $this->updateTemporaryIds($conn, $validatedData);

            // 4. PRIORITÉ: Mettre à jour init__machine en premier
            if (!empty($validatedData['machine_updates'])) {
                error_log("Mise à jour de " . count($validatedData['machine_updates']) . " machines (PRIORITÉ)");
                $updateStmt = $conn->prepare("
                    UPDATE init__machine 
                    SET machines_status_id = ?, machines_location_id = ? 
                    WHERE id = ?
                ");
                foreach ($validatedData['machine_updates'] as $update) {
                    $updateStmt->execute([
                        $update['status_id'], $update['location_id'], $update['machine_id']
                    ]);
                }
            }

            // 5. Insérer l'inventaire
            if (!empty($validatedData['inventaire'])) {
                error_log("Insertion de " . count($validatedData['inventaire']) . " enregistrements d'inventaire");
                $inventaireStmt = $conn->prepare("
                    INSERT INTO gmao__inventaire_machine 
                    (machine_id, maintener_id, location_id, status_id, created_at) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($validatedData['inventaire'] as $inventaire) {
                    $inventaireStmt->execute([
                        $inventaire['machine_id'], $inventaire['maintener_id'],
                        $inventaire['location_id'], $inventaire['status_id'],
                        $inventaire['created_at']
                    ]);
                }
            }

            // 5. Insérer les maintenances
            if (!empty($validatedData['machine_maint'])) {
                error_log("Insertion de " . count($validatedData['machine_maint']) . " enregistrements de maintenance");
                $maintStmt = $conn->prepare("
                    INSERT INTO gmao__machine_maint 
                    (machine_id, maintener_id, location_id) 
                    VALUES (?, ?, ?)
                ");
                foreach ($validatedData['machine_maint'] as $maint) {
                    $maintStmt->execute([
                        $maint['machine_id'], $maint['maintener_id'], $maint['location_id']
                    ]);
                }
            }

            // 6. Les machines ont déjà été mises à jour en priorité (étape 4)

            return true;

        } catch (\Throwable $e) {
            error_log("Erreur lors de l'insertion des données: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fonction utilitaire pour préparer les données d'une nouvelle machine
     */
    private function prepareNewMachineData($row)
    {
        $machineData = [
            'machine_id' => trim((string)($row[10] ?? '')),
            'reference' => trim((string)($row[17] ?? '')),
            'brand' => trim((string)($row[4] ?? '')),
            'type' => trim((string)($row[2] ?? '')),
            'designation' => trim((string)($row[3] ?? '')),
            'billing_num' => trim((string)($row[19] ?? '')),
            'bill_date' => $this->parseDate($row[20] ?? ''),
            'cur_date' => date('Y-m-d'),
            'machines_location_id' => null,
            'machines_status_id' => null
        ];

        if (empty($machineData['machine_id'])) {
            return null;
        }

        return $machineData;
    }

    /**
     * Fonction utilitaire pour déterminer la catégorie de location
     */
    private function determineLocationCategory($location_idxl)
    {
        if (strpos($location_idxl, 'CH') === 0 || strpos($location_idxl, 'ECH') === 0 || strpos($location_idxl, 'ch') === 0) {
            return 'prodline';
        } elseif ($location_idxl === "FERAILLE") {
            return null; // Ignorer FERAILLE
        } else {
            return 'parc';
        }
    }

    /**
     * Fonction utilitaire pour mettre à jour les IDs temporaires
     */
    private function updateTemporaryIds($conn, &$validatedData)
    {
        // Mettre à jour les IDs temporaires des nouvelles locations
        if (!empty($validatedData['new_locations'])) {
            $locationMap = [];
            // Récupérer les IDs des locations par nom (plus fiable que ORDER BY id DESC)
            $locationNames = array_column($validatedData['new_locations'], 'location_name');
            $placeholders = str_repeat('?,', count($locationNames) - 1) . '?';
            $stmt = $conn->prepare("SELECT id, location_name FROM gmao__location WHERE location_name IN ($placeholders)");
            $stmt->execute($locationNames);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $locationMap[$row['location_name']] = (int)$row['id'];
            }
            
            // Mettre à jour les IDs temporaires dans les données
            foreach ($validatedData['inventaire'] as &$inventaire) {
                if ($inventaire['location_id'] < 0) {
                    $tempIndex = abs($inventaire['location_id']) - 1;
                    if (isset($validatedData['new_locations'][$tempIndex])) {
                        $locationName = $validatedData['new_locations'][$tempIndex]['location_name'];
                        $inventaire['location_id'] = $locationMap[$locationName] ?? null;
                    }
                }
            }
            
            foreach ($validatedData['machine_maint'] as &$maint) {
                if ($maint['location_id'] < 0) {
                    $tempIndex = abs($maint['location_id']) - 1;
                    if (isset($validatedData['new_locations'][$tempIndex])) {
                        $locationName = $validatedData['new_locations'][$tempIndex]['location_name'];
                        $maint['location_id'] = $locationMap[$locationName] ?? null;
                    }
                }
            }
            
            foreach ($validatedData['machine_updates'] as &$update) {
                if ($update['location_id'] < 0) {
                    $tempIndex = abs($update['location_id']) - 1;
                    if (isset($validatedData['new_locations'][$tempIndex])) {
                        $locationName = $validatedData['new_locations'][$tempIndex]['location_name'];
                        $update['location_id'] = $locationMap[$locationName] ?? null;
                    }
                }
            }
        }
        
        // Mettre à jour les IDs temporaires des nouvelles machines
        if (!empty($validatedData['new_machines'])) {
            $machineMap = [];
            $stmt = $conn->query("SELECT id, machine_id FROM init__machine ORDER BY id DESC LIMIT " . count($validatedData['new_machines']));
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $machineMap[$row['machine_id']] = (int)$row['id'];
            }
            
            // Mettre à jour les IDs temporaires dans les données
            foreach ($validatedData['inventaire'] as &$inventaire) {
                if ($inventaire['machine_id'] < 0) {
                    $tempIndex = abs($inventaire['machine_id']) - 1;
                    if (isset($validatedData['new_machines'][$tempIndex])) {
                        $machineId = $validatedData['new_machines'][$tempIndex]['machine_id'];
                        $inventaire['machine_id'] = $machineMap[$machineId] ?? null;
                    }
                }
            }
            
            foreach ($validatedData['machine_maint'] as &$maint) {
                if ($maint['machine_id'] < 0) {
                    $tempIndex = abs($maint['machine_id']) - 1;
                    if (isset($validatedData['new_machines'][$tempIndex])) {
                        $machineId = $validatedData['new_machines'][$tempIndex]['machine_id'];
                        $maint['machine_id'] = $machineMap[$machineId] ?? null;
                    }
                }
            }
            
            foreach ($validatedData['machine_updates'] as &$update) {
                if ($update['machine_id'] < 0) {
                    $tempIndex = abs($update['machine_id']) - 1;
                    if (isset($validatedData['new_machines'][$tempIndex])) {
                        $machineId = $validatedData['new_machines'][$tempIndex]['machine_id'];
                        $update['machine_id'] = $machineMap[$machineId] ?? null;
                    }
                }
            }
        }
    }
}
