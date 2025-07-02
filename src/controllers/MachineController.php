<?php
namespace App\Controllers;
use App\Models\Machine_model;

class MachineController {
    public function list() {
        $machines = Machine_model::findAll();
        include(__DIR__ . '/../views/machines/init__machine.php');
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $machine_id = substr($_POST['machine_id'], 0, 16);
            $machine = new Machine_model(
                $machine_id,
                $_POST['designation'],
                $_POST['reference'],
                $_POST['type'],
                $_POST['brand'],
                $_POST['billing_num'],
                $_POST['bill_date']
            );
            Machine_model::AjouterMachine($machine);
            header('Location: /public/index.php?route=machines');
            exit;
        }
        include(__DIR__ . '/../views/machines/ajouter_machine.php');
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ../../public/index.php?route=machines');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $machine = new Machine_model(
                $id,
                $_POST['designation'],
                $_POST['reference'],
                $_POST['type'],
                $_POST['brand'],
                $_POST['billing_num'],
                $_POST['bill_date']
            );
            Machine_model::ModifierMachine($machine);
            header('Location: ../../public/index.php?route=machines');
            exit;
        }
        $machine = Machine_model::findById($id);
        include(__DIR__ . '/../views/machines/edit_machine.php');
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            Machine_model::deleteById($id);
        }
        header('Location: ../../public/index.php?route=machines');
        exit;
    }
} 