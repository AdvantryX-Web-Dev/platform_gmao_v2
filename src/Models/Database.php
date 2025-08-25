<?php

namespace App\Models;

class Database
{
    private static $instances = [];
    private $conn;
    private $dbName;

    /**
     * Constructeur qui établit la connexion à la base de données spécifiée
     * 
     * @param string $dbKey Clé de la base de données à utiliser (db_digitex, MAHDCO_MAINT)
     */
    public function __construct($dbKey = null)
    {
        $this->connect($dbKey);
    }

    /**
     * Établit la connexion à la base de données spécifiée
     * 
     * @param string $dbKey Clé de la base de données à utiliser
     */
    private function connect($dbKey = null)
    {
        try {
            // Chemin absolu vers le fichier de configuration
            $configPath = __DIR__ . '/../../config/database.php';

            // Vérifier si le fichier existe
            if (!file_exists($configPath)) {
                throw new \Exception("Le fichier de configuration de la base de données n'existe pas: " . $configPath);
            }

            // Charger les configurations de la base de données
            $config = require $configPath;

            if (!is_array($config)) {
                throw new \Exception("La configuration de la base de données n'est pas valide");
            }

            // Si aucune base de données spécifiée, utiliser celle par défaut
            if ($dbKey === null) {
                $dbKey = $config['default'];
            }

            $this->dbName = $dbKey;

            // Vérifier si la configuration pour cette base de données existe
            if (!isset($config['connections'][$dbKey])) {
                throw new \Exception("La configuration pour la base de données '$dbKey' n'existe pas");
            }

            $dbConfig = $config['connections'][$dbKey];

            // Construire le DSN
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";

            // Créer la connexion
            $this->conn = new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
        } catch (\PDOException $e) {
            // Log error
            error_log("Erreur de connexion à la base de données '$dbKey': " . $e->getMessage());
            throw new \Exception("Erreur de connexion à la base de données '$dbKey': " . $e->getMessage());
        } catch (\Exception $e) {
            // Log error
            error_log("Erreur: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retourne l'instance de la base de données spécifiée (Singleton)
     * 
     * @param string $dbKey Clé de la base de données à utiliser
     * @return Database L'instance de la classe Database pour la base de données spécifiée
     */
    public static function getInstance($dbKey = null)
    {
        // Si aucune base de données spécifiée, charger la configuration pour trouver la base par défaut
        if ($dbKey === null) {
            $configPath = __DIR__ . '/../../config/database.php';
            $config = require $configPath;
            $dbKey = $config['default'];
        }

        // Créer une instance pour cette base de données si elle n'existe pas déjà
        if (!isset(self::$instances[$dbKey])) {
            self::$instances[$dbKey] = new self($dbKey);
        }

        return self::$instances[$dbKey];
    }

    /**
     * Retourne la connexion à la base de données
     * 
     * @return \PDO La connexion à la base de données
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * Retourne le nom de la base de données utilisée par cette instance
     * 
     * @return string Nom de la base de données
     */
    public function getDatabaseName()
    {
        return $this->dbName;
    }
}
