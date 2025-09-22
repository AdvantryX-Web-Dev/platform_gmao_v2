<?php

namespace App\Controllers;

use App\Models\Database;

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
            $insert = $conn->prepare("INSERT INTO gmao__inventaire_machine (machine_id, maintener_id, location_id, status_id) VALUES (:machine_id, :maintener_id, :location_id, :status_id)");
            // Lookup prepared statements
            $idMachine = $conn->prepare("SELECT id FROM init__machine WHERE machine_id = :id_machine LIMIT 1");
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
                        if (strpos($location_idxl, 'CH') === 0) {
                            $location_category = 'prodline';
                        }elseif($location_idxl === "FERAILLE") 
                        {
                            continue;
                        }
                        else {
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
                ]);
                //insert into gmao__machine_maint
                $stmt = $conn->prepare("INSERT INTO gmao__machine_maint (machine_id, maintener_id, location_id) VALUES (:machine_id, :maintener_id, :location_id)");
                $stmt->execute([':machine_id' => $machineId, ':maintener_id' => $maintenerId, ':location_id' => $locationId]);
                //update init__machine set machines_status_id et machines_location_id 
                $stmt = $conn->prepare("UPDATE init__machine SET machines_status_id = :status_id, machines_location_id = :location_id WHERE id = :machine_id");
                $stmt->execute([':status_id' => $statusId, ':location_id' => $locationId, ':machine_id' => $machineId]);
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
        include __DIR__ . '/../views/inventaire/maintenancier_machine.php';
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