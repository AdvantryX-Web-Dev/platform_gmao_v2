<?php

namespace App\Controllers;

use App\Models\Equipement_model;

class Equipment_MachineController
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }
    public static function ListEquipementsMachines()
    {


        $equipements = Equipement_model::affectationEquipementsMachines();

        return $equipements;
    }
    public function affectation_equipmentMachine()
    {
        require_once __DIR__ . '/../views/G_equipements/Affectation_equipementMachine.php';
    }
}
