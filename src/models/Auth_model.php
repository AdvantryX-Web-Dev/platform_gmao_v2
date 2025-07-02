<?php

namespace App\Models;

use App\Models\Database;

class Auth_model {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

   
    /**
     * Vérifie si un email existe déjà dans la table gmao_compte
     * 
     * @param string $email L'email à vérifier
     * @return bool True si l'email existe, false sinon
     */
    //login
    public function emailExistsInCompte($email) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "SELECT COUNT(*) FROM gmao_compte WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            // Log error
            error_log("Erreur lors de la vérification de l'email dans gmao_compte: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un matricule existe dans la table init_employe
     * 
     * @param string $matricule Le matricule à rechercher
     * @return array|false Les données de l'employé ou false si l'employé n'existe pas
     */
    public function findInInitEmploye($matricule) {
        try {
            $conn = $this->db->getConnection();

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
     * Vérifie si un matricule existe déjà dans la table gmao_compte
     * 
     * @param string $matricule Le matricule à vérifier
     * @return bool True si le matricule existe, false sinon
     */

     
    //register
    public function matriculeExistsInCompte($matricule) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "SELECT COUNT(*) FROM gmao_compte WHERE matricule = :matricule";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':matricule', $matricule);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            // Log error
            error_log("Erreur lors de la vérification du matricule dans gmao_compte: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée un nouveau compte dans la table gmao_compte
     * 
     * @param array $compteData Les données du compte
     * @return bool True si le compte a été créé avec succès, false sinon
     */
    public function createCompte($compteData) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "INSERT INTO gmao__compte (matricule, email, motDePasse) 
                    VALUES (:matricule, :email, :motDePasse)";
            
            $stmt = $conn->prepare($sql);
    
            // Lier les valeurs aux paramètres
            $stmt->bindParam(':matricule', $compteData['matricule']);
            $stmt->bindParam(':email', $compteData['email']);
            $stmt->bindParam(':motDePasse', $compteData['password']); // 'password' est bien défini dans le tableau
            
            $success = $stmt->execute();
    
            if (!$success) {
                // Afficher les détails d'erreur
                $errorInfo = $stmt->errorInfo();
                echo "Erreur SQL : " . $errorInfo[2];
                die();
            }
    
            return true;
    
        } catch (\PDOException $e) {
            echo "Erreur PDO : " . $e->getMessage(); die();
            return false;
        }
    }
    
    /**
     * Trouve un utilisateur par son email dans la table gmao_compte
     * 
     * @param string $email L'email à rechercher
     * @return array|false Les données de l'utilisateur ou false si l'utilisateur n'existe pas
     */
    public function findByEmailInCompte($email) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "SELECT * FROM gmao__compte WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            // Log error
            error_log("Erreur lors de la recherche de l'utilisateur par email dans gmao_compte: " . $e->getMessage());
            return false;
        }
    }

   
}
