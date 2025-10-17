<?php

namespace App\Controllers;

use App\Models\Database;
use App\auditTrails\inventaire;
use App\Models\AuditTrail_model;

class ImportInventaireController
{
    public function importInventaire()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleImport();
            return;
        }
        include __DIR__ . '/../views/inventaire/importInventaire.php';
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
            $this->save_import_inventaire($conn, $rows);
        } catch (\Throwable $e) {
            error_log("Erreur avec la version optimisée, utilisation de la version classique: " . $e->getMessage());
            $conn->rollBack();
            $conn->beginTransaction();
            // $this->save_inventaire_machine($conn, $rows);
        }


        header('Location: index.php?route=importInventaire');
        exit;
    }
    private function save_import_inventaire($conn, $rows)
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

                    // Audit trail pour chaque nouvelle location
                    $this->logAuditLocation($location['location_name'], $location['location_category']);
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
                        $machine['machine_id'],
                        $machine['reference'],
                        $machine['brand'],
                        $machine['type'],
                        $machine['designation'],
                        $machine['billing_num'],
                        $machine['bill_date'],
                        $machine['cur_date'],
                        $machine['machines_location_id'],
                        $machine['machines_status_id']
                    ]);

                    // Audit trail pour chaque nouvelle machine
                    $this->logAuditNewMachine($machine);
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
                        $update['status_id'],
                        $update['location_id'],
                        $update['machine_id']
                    ]);

                    // Audit trail pour chaque mise à jour de machine
                    $this->logAuditMachineUpdate($update['machine_id'], $update['status_id'], $update['location_id']);
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
                        $inventaire['machine_id'],
                        $inventaire['maintener_id'],
                        $inventaire['location_id'],
                        $inventaire['status_id'],
                        $inventaire['created_at']
                    ]);

                    // Audit trail pour chaque inventaire
                    $this->logAuditInventaire($inventaire['machine_id'], $inventaire['maintener_id'], $inventaire['location_id'], $inventaire['status_id']);
                }
            }

            // 6. Insérer les maintenances
            if (!empty($validatedData['machine_maint'])) {
                error_log("Insertion de " . count($validatedData['machine_maint']) . " enregistrements de maintenance");
                $maintStmt = $conn->prepare("
                    INSERT INTO gmao__machine_maint 
                    (machine_id, maintener_id, location_id) 
                    VALUES (?, ?, ?)
                ");
                foreach ($validatedData['machine_maint'] as $maint) {
                    $maintStmt->execute([
                        $maint['machine_id'],
                        $maint['maintener_id'],
                        $maint['location_id']
                    ]);

                    // Audit trail pour chaque maintenance
                    $this->logAuditMachineMaint($maint['machine_id'], $maint['maintener_id'], $maint['location_id']);
                }
            }

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
        } elseif ($status_idxl === "ferraille") {

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
    //audit trails
    /**
     * Audit pour nouvelle location créée
     * @param string $locationName Nom de la location
     * @param string $locationCategory Catégorie de la location
     */
    private function logAuditLocation($locationName, $locationCategory)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            $newValue = [
                'location_name' => $locationName,
                'location_category' => $locationCategory,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__location', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit location: " . $e->getMessage());
        }
    }

    private function logAuditNewMachine($machineData)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            $newValue = [
                'machine_id' => $machineData['machine_id'],
                'reference' => $machineData['reference'],
                'brand' => $machineData['brand'],
                'type' => $machineData['type'],
                'designation' => $machineData['designation'],
                'billing_num' => $machineData['billing_num'],
                'bill_date' => $machineData['bill_date'],
                'cur_date' => $machineData['cur_date'],
                'machines_location_id' => $machineData['machines_location_id'],
                'machines_status_id' => $machineData['machines_status_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'init__machine', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit nouvelle machine: " . $e->getMessage());
        }
    }
    private function logAuditMachineUpdate($machineId, $statusId, $locationId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            // Récupérer les anciennes valeurs
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
        } catch (\Throwable $e) {
            error_log("Erreur audit mise à jour machine: " . $e->getMessage());
        }
    }
    private function logAuditMachineMaint($machineId, $maintenerId, $locationId)
    {
        try {
            $userMatricule = $_SESSION['user']['matricule'] ?? null;
            if (!$userMatricule) return;

            $newValue = [
                'machine_id' => $machineId,
                'maintener_id' => $maintenerId,
                'location_id' => $locationId,
                'created_at' => date('Y-m-d H:i:s')
            ];
            AuditTrail_model::logAudit($userMatricule, 'add', 'gmao__machine_maint', null, $newValue);
        } catch (\Throwable $e) {
            error_log("Erreur audit maintenance: " . $e->getMessage());
        }
    }
    private function logAuditInventaire($machineId, $maintenerId, $locationId, $statusId)
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
        } catch (\Throwable $e) {
            error_log("Erreur audit import: " . $e->getMessage());
        }
    }
}
