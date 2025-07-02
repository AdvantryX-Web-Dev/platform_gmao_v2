<?php
namespace App\Models;

class Database {
    private static $instance = null;
    private $conn;

    public function __construct() {
        $this->connect();
    }

    /**
     * Établit la connexion à la base de données
     */
    private function connect() {
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
            
            // Construire le DSN
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
            
            // Options PDO
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Créer la connexion
            $this->conn = new \PDO($dsn, $config['username'], $config['password'], $options);
        } catch (\PDOException $e) {
            // Log error
            error_log("Erreur de connexion à la base de données: " . $e->getMessage());
            throw new \Exception("Erreur de connexion à la base de données: " . $e->getMessage());
        } catch (\Exception $e) {
            // Log error
            error_log("Erreur: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retourne l'instance unique de la classe Database (Singleton)
     * 
     * @return Database L'instance de la classe Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Retourne la connexion à la base de données
     * 
     * @return \PDO La connexion à la base de données
     */
    public function getConnection() {
        return $this->conn;
    }
} 