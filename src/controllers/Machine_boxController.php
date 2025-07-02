<?php
namespace App\Controllers;

use App\Models\Machine_model;


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
