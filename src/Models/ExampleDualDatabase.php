<?php

namespace App\Models;

/**
 * Exemple de classe utilisant les deux bases de données
 */
class ExampleDualDatabase
{

    /**
     * Récupère des données depuis la base de données db_digitex
     * 
     * @return array Les données récupérées
     */
    public static function getDataFromDbIsa()
    {
        // Obtenir l'instance de la base de données db_digitex
        $db = Database::getInstance('db_digitex');
        $conn = $db->getConnection();

        // Exemple de requête
        $stmt = $conn->prepare("SELECT * FROM your_table_in_db_digitex LIMIT 10");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Récupère des données depuis la base de données MAHDCO_MAINT
     * 
     * @return array Les données récupérées
     */
    public static function getDataFromDbGMAO()
    {
        // Obtenir l'instance de la base de données MAHDCO_MAINT
        $db = Database::getInstance('MAHDCO_MAINT');
        $conn = $db->getConnection();

        // Exemple de requête
        $stmt = $conn->prepare("SELECT * FROM your_table_in_MAHDCO_MAINT LIMIT 10");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Exemple de méthode qui utilise les deux bases de données en même temps
     * 
     * @return array Un tableau combinant des données des deux bases
     */
    public static function combineDataFromBothDatabases()
    {
        // Récupérer des données de db_digitex
        $dbIsa = Database::getInstance('db_digitex');
        $connIsa = $dbIsa->getConnection();

        $stmtIsa = $connIsa->prepare("SELECT * FROM your_table_in_db_digitex LIMIT 5");
        $stmtIsa->execute();
        $dataFromIsa = $stmtIsa->fetchAll();

        // Récupérer des données de MAHDCO_MAINT
        $dbGMAO = Database::getInstance('MAHDCO_MAINT');
        $connGMAO = $dbGMAO->getConnection();

        $stmtGMAO = $connGMAO->prepare("SELECT * FROM your_table_in_MAHDCO_MAINT LIMIT 5");
        $stmtGMAO->execute();
        $dataFromGMAO = $stmtGMAO->fetchAll();

        // Combiner les données
        return [
            'db_digitex_data' => $dataFromIsa,
            'MAHDCO_MAINT_data' => $dataFromGMAO
        ];
    }
}
