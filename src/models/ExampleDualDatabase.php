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
     * Récupère des données depuis la base de données db_GMAO
     * 
     * @return array Les données récupérées
     */
    public static function getDataFromDbGMAO()
    {
        // Obtenir l'instance de la base de données db_GMAO
        $db = Database::getInstance('db_GMAO');
        $conn = $db->getConnection();

        // Exemple de requête
        $stmt = $conn->prepare("SELECT * FROM your_table_in_db_GMAO LIMIT 10");
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

        // Récupérer des données de db_GMAO
        $dbGMAO = Database::getInstance('db_GMAO');
        $connGMAO = $dbGMAO->getConnection();

        $stmtGMAO = $connGMAO->prepare("SELECT * FROM your_table_in_db_GMAO LIMIT 5");
        $stmtGMAO->execute();
        $dataFromGMAO = $stmtGMAO->fetchAll();

        // Combiner les données
        return [
            'db_digitex_data' => $dataFromIsa,
            'db_GMAO_data' => $dataFromGMAO
        ];
    }
}
