<?php
namespace App\Controllers;

use App\Models\Machine_model;


if (isset($_POST['importSubmit'])) {
    // Vérifie si le formulaire a été soumis correctement
    if (!empty($_FILES['file']['name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
        $csvMimes = array(
            'text/x-comma-separated-values',
            'text/comma-separated-values',
            'application/octet-stream',
            'application/vnd.ms-excel',
            'application/x-csv',
            'text/x-csv',
            'text/csv',
            'application/csv',
            'application/excel',
            'application/vnd.msexcel',
            'text/plain'
        );

        // Vérifie si le fichier est un fichier CSV valide
        if (in_array(mime_content_type($_FILES['file']['tmp_name']), $csvMimes) && is_readable($_FILES['file']['tmp_name'])) {
            // Parse data from CSV file
            $importedMachines = parseCSV($_FILES['file']['tmp_name']);

            if ($importedMachines !== false) {
                foreach ($importedMachines as $machineData) {
                    // Vérifie que toutes les données nécessaires sont présentes
                    if (isset($machineData[0], $machineData[1], $machineData[2], $machineData[3], $machineData[4], $machineData[5], $machineData[6])) {
                        // Récupère les données de la machine à partir du tableau CSV
                        $id_machine = $machineData[0];
                        $reference = $machineData[1];
                        $designation = $machineData[4];
                        $type = $machineData[3];
                        $brand = $machineData[2];
                        $billing_num = $machineData[5];
                        $bill_date = date('Y-m-d', strtotime($machineData[6]));
                        // $price = $machineData[7];
                        // $dateImportation = $machineData[8];

                        // Vérifie si la machine existe déjà dans la base de données
                        $machineI = Machine_model::findById($id_machine);
                        $machine = new Machine_model($id_machine, $designation, $reference, $type, $brand, $billing_num, $bill_date);

                        // Met à jour ou insère la machine dans la base de données
                        if ($machineI !== false) {
                            Machine_model::ModifierMachine($machine);
                        } else {
                            Machine_model::AjouterMachine($machine);
                        }
                    } else {
                        $_SESSION['error_messages'][] = 'Données CSV invalides, certaines informations sont manquantes.';
                    }
                }

                $msg = '?status=succ';
            } else {
                $msg = '?status=err';
            }
        } else {
            $msg = '?status=invalid_file';
        }
    } else {
        $msg = '?status=no_file';
    }

    header("Location: ../Vue/init_Machine.php" . $msg);
}

// Fonction pour parser le fichier CSV
function parseCSV($filePath)
{
    $csvFile = fopen($filePath, 'r');
    fgetcsv($csvFile); // Ignore la première ligne (entête)

    $data = [];

    while (($line = fgetcsv($csvFile, 1000, ';')) !== false) {
        // Ignorer les lignes vides
        if (count($line) < 7 || empty($line[0]) || empty($line[1]) || empty($line[2]) || empty($line[3]) || empty($line[4]) || empty($line[5]) || empty($line[6])) {
            continue;
        }
        $data[] = $line;
    }

    fclose($csvFile);

    return $data;
}
// Section pour ajouter une machine
if (isset($_POST['ajouter'])) {
    if (!empty($_POST['id_machine']) && isset($_POST['typesM']) && !empty($_POST['designation']) && !empty($_POST['reference'])) {
        $type = $_POST['typesM'];
        $id_machine = $_POST['id_machine'];
        $reference = $_POST['reference'];
        $designation = $_POST['designation'];
        $brand = $_POST['marqueMach'];

        $billing_num = $_POST['numFac'];

        $bill_date = $_POST['dateF'];

        // $price = $_POST['prixFac'];

        $dateImportation = "";

        $machineI = Machine_model::findById($id_machine);
        $machine = new Machine_model($id_machine, $designation, $reference, $type, $brand, $billing_num, $bill_date);

        if ($machineI !== false) {
            $msg = '?status=errorM_exi';
        } else {
            $ajoutM = Machine_model::AjouterMachine($machine);

            if ($ajoutM) {
                $msg = '?status=succM';
            } else {
                $msg = '?status=err';
            }
        }
    } else {
        $msg = '?status=errorInfoM';
    }
    header("Location: ../Vue/init_Machine.php" . $msg);
}

// Section pour modifier une machine
if (isset($_POST['modifier'])) {
    if (!empty($_POST['id_mach']) && isset($_POST['typesM']) && !empty($_POST['des']) && !empty($_POST['ref'])) {
        $type = $_POST['typesM'];
        $id_machine = $_POST['id_mach'];
        $reference = $_POST['ref'];
        $designation = $_POST['des'];
        $brand = $_POST['marqueMach'];
        ;
        $billing_num = $_POST['numFac'];
        ;
        $bill_date = $_POST['dateF'];
        ;
        // $price = $_POST['prixFac'];
        ;
        $dateImportation = "";

        $machine = new Machine_model($id_machine, $designation, $reference, $type, $brand, $billing_num, $bill_date);

        $modifieMach = Machine_model::ModifierMachine($machine);
        if ($modifieMach) {
            $msg = '?status=succModiMac';
        } else {
            $msg = '?status=errorM';
        }
    } else {
        $msg = '?status=errorInfoM';
    }
    header("Location: ../Vue/init_Machine.php" . $msg);
}

function afficherMachines()
{
    $machines = Machine_model::findAll();
    return $machines;
}

function afficherDetailsMachine($id_machine)
{
    $machine = Machine_model::findById($id_machine);
    return $machine;
}
function typeMBymachine($machine_id)
{
    return Machine_model::findBytype($machine_id);
}
