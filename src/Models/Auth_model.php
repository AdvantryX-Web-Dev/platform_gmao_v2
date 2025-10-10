<?php

namespace App\Models;

use App\Models\Database;

class Auth_model
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }



    /**
     * Vérifie si un matricule existe dans la table init_employe
     * 
     * @param string $matricule Le matricule à rechercher
     * @return array|false Les données de l'employé ou false si l'employé n'existe pas
     */
    public function findInInitEmploye($matricule)
    {
        try {

            $db = Database::getInstance('db_digitex'); // Spécifier explicitement la base de données db_digitex

            $conn = $db->getConnection();

            $sql = "SELECT * FROM init__employee WHERE matricule = :matricule";
            $stmt = $conn->prepare($sql);

            $stmt->bindParam(':matricule', $matricule);
            $stmt->execute();


            $result = $stmt->fetch(\PDO::FETCH_ASSOC);


            return $result;
        } catch (\PDOException $e) {
            error_log("Erreur lors de la recherche dans init_employe: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Vérifie si un matricule existe déjà dans la table gmao__compte
     * 
     * @param string $matricule Le matricule à vérifier
     * @return bool True si le matricule existe, false sinon
     */


    //register
    public function matriculeExistsInCompte($matricule)
    {
        try {
            $conn = $this->db->getConnection();

            $sql = "SELECT COUNT(*) FROM gmao__compte WHERE matricule = :matricule";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':matricule', $matricule);
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            print_r("model:".$e->getMessage());die;

            // Log error
            error_log("Erreur lors de la vérification du matricule dans gmao__compte: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée un nouveau compte dans la table gmao__compte
     * _
     * @param array $compteData Les données du compte
     * @return bool True si le compte a été créé avec succès, false sinon
     */
    public function createCompte($compteData)
    {
        try {
            $conn = $this->db->getConnection();

            $sql = "INSERT INTO gmao__compte (matricule, motDePasse) 
                    VALUES (:matricule, :motDePasse)";

            $stmt = $conn->prepare($sql);

            // Lier les valeurs aux paramètres
            $stmt->bindParam(':matricule', $compteData['matricule']);
            $stmt->bindParam(':motDePasse', $compteData['password']); // 'password' est bien défini dans le tableau

            $success = $stmt->execute();

            if (!$success) {
                // Afficher les détails d'erreur
                $errorInfo = $stmt->errorInfo();
                echo "Erreur SQL : " . $errorInfo[2];
            }

            return true;
        } catch (\PDOException $e) {
            echo "Erreur PDO : " . $e->getMessage();
            die();
            return false;
        }
    }


    /**
     * Trouve un utilisateur par son matricule dans la table gmao__compte
     * 
     * @param string $matricule Le matricule à rechercher
     * @return array|false Les données de l'utilisateur ou false si l'utilisateur n'existe pas
     */
    public function findByMatriculeInCompte($matricule)
    {
        try {
            $conn = $this->db->getConnection();

            $sql = "SELECT * FROM gmao__compte
            JOIN init__employee ON gmao__compte.matricule = init__employee.matricule
            WHERE gmao__compte.matricule = :matricule";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':matricule', $matricule);
            $stmt->execute();

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Log error
            error_log("Erreur lors de la recherche de l'utilisateur par matricule dans gmao__compte: " . $e->getMessage());
            return false;
        }
    }
}
