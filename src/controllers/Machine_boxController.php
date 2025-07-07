<?php
namespace App\Controllers;
use App\Models\Machines_box_model;
class Machine_boxController {
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }
    public static function affectationBoxsMachines()
    {
        return Machines_box_model::findAll();
    }
}
