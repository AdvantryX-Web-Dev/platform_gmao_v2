<?php

namespace App\Controllers;

use App\Models\Auth_model;

class AuthController
{
    private $authModel;

    public function __construct()
    {
        $this->authModel = new Auth_model();
    }

    /**
     * Affiche la page de connexion
     */
    public function showLoginForm()
    {
        require_once __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Affiche la page d'inscription
     */
    public function showRegisterForm()
    {
        require_once __DIR__ . '/../views/auth/register.php';
    }

    /**
     * Affiche le tableau de bord
     */
   

    /**
     * Traite la demande de connexion
     */
    public function login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérifier si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            // Validation des champs
            $errors = [];

            if (empty($email)) {
                $errors[] = "L'adresse e-mail est requise.";
            }

            if (empty($password)) {
                $errors[] = "Le mot de passe est requis.";
            }

            // S'il n'y a pas d'erreurs, tenter la connexion
            if (empty($errors)) {
                // Rechercher l'utilisateur par email dans la table gmao_compte
                $user = $this->authModel->findByEmailInCompte($email);

                if ($user) {

                    // Vérifier le mot de passe
                    if (password_verify($password, $user['motDePasse'])) {
                        // Récupérer les informations de l'employé depuis init_employe
                        $employe = $this->authModel->findInInitEmploye($user['matricule']);


                        if ($employe) {

                            // Connexion réussie
                            $_SESSION['is_logged_in'] = true;
                            $_SESSION['user'] = [
                                'matricule' => $user['matricule'],
                                'email' => $user['email'],
                                'firstname' => $employe['prenom'] ?? '',
                                'lastname' => $employe['nom'] ?? '',
                                'role' => $employe['qualification'] ?? 'user'
                            ];

                            $_SESSION['email'] = $user['email'];
                            $_SESSION['qualification'] = $employe['qualification'] ?? 'user';

                            // Rediriger selon le rôle de l'utilisateur
                            switch (strtolower($employe['qualification'])) {
                                case 'maintenancier ||chefmaintenancier ||magasinier ||responsableparcmachine ||responsableservachat':
                                   // header("Location: index.php?route=HomeMainte");
                                   header("Location: index.php?route=dashboard");

                                    break;

                                // case 'chefmaintenancier':
                                //    header("Location: index.php?route=HomeChefmaintenancier");
                                //     break;

                                // case 'magasinier':
                                //     header("Location: index.php?route=StockArticles");
                                //     break;

                                // case 'administrateur':
                                    
                                //     require_once __DIR__ . '/../views/Employes.php';

                                //   //  header("Location: index.php?route=employes");
                                //     break;

                                // case 'responsableparcmachine':
                                //     header("Location: index.php?route=ParcMachine");
                                //     break;

                                // case 'responsableservachat':
                                //     header("Location: index.php?route=HistoriqueCommInter");
                                //     break;

                                default:
                                    header("Location: index.php?route=dashboard");
                                    break;
                            }
                            exit;
                        } else {
                            // Employé non trouvé dans init_employe
                            $errors[] = "Erreur: informations d'employé introuvables.";
                        }
                    } else {
                        // Mot de passe incorrect
                        $errors[] = "Adresse e-mail ou mot de passe incorrect.";
                    }
                } else {
                    // Utilisateur non trouvé
                    $errors[] = "Adresse e-mail ou mot de passe incorrect.";
                }
            }

            // S'il y a des erreurs, les stocker dans la session et rediriger
            if (!empty($errors)) {
                $_SESSION['login_errors'] = $errors;
                $_SESSION['login_old_values'] = [
                    'email' => $email
                ];

                header("Location: index.php?route=login");
                exit;
            }
        } else {
            // Si ce n'est pas une requête POST, rediriger vers le formulaire de connexion
            header("Location: index.php?route=login");
            exit;
        }
    }

    /**
     * Traite la demande d'inscription
     */
  
    public function register() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Vérifier si le formulaire a été soumis
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Ajouter'])) {
            // Récupérer les données du formulaire
            $matricule = $_POST['matricule'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // Validation des champs
            $errors = [];
            
            if (empty($matricule)) {
                $errors[] = "Le matricule est requis.";
            }
            
            if (empty($email)) {
                $errors[] = "L'adresse e-mail est requise.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'adresse e-mail n'est pas valide.";
            }
            
            if (empty($password)) {
                $errors[] = "Le mot de passe est requis.";
            } elseif (strlen($password) < 6) {
                $errors[] = "Le mot de passe doit contenir au moins 6 caractères.";
            }
            
            
            
            // Vérifier si l'email existe déjà dans la table gmao_compte
            $emailExists = $this->authModel->emailExistsInCompte($email);
            if ($emailExists) {
                $errors[] = "Cette adresse email est déjà utilisée.";
            }
            
            // S'il n'y a pas d'erreurs, vérifier si le matricule existe dans la table init_employe
            if (empty($errors)) {
                $employe = $this->authModel->findInInitEmploye($matricule);
                
                if ($employe) {
                    // Le matricule existe dans la table init_employe
                    // Vérifier si un compte existe déjà pour ce matricule
                    $compteExists = $this->authModel->matriculeExistsInCompte($matricule);
                    
                    if (!$compteExists) {
                        // Créer un nouveau compte dans la table gmao_compte
                        $compteData = [
                            'matricule' => $matricule,
                            'email' => $email,
                            'password' => password_hash($password, PASSWORD_DEFAULT)
                        ];
                        
                        $result = $this->authModel->createCompte($compteData);
                        
                        if ($result) {
                            // Création réussie - Connecter automatiquement l'utilisateur
                            $_SESSION['is_logged_in'] = true;
                            $_SESSION['user'] = [
                                'matricule' => $matricule,
                                'email' => $email,
                                'firstname' => $employe['prenom'] ?? '',
                                'lastname' => $employe['nom'] ?? '',
                                'role' => $employe['role'] ?? 'user'
                            ];
                            
                            // Rediriger vers le tableau de bord
                            header("Location: index.php?route=dashboard");
                            exit;
                        } else {
                            // Échec de la création
                            $errors[] = "Une erreur s'est produite lors de la création du compte.";
                        }
                    } else {
                        // Un compte existe déjà pour ce matricule
                        $errors[] = "Un compte existe déjà pour ce matricule.";
                    }
                } else {
                    // Le matricule n'existe pas dans la table init_employe
                    $errors[] = "Le matricule fourni n'existe pas dans notre système. Veuillez contacter votre administrateur.";
                }
            }
            
            // S'il y a des erreurs, les stocker dans la session et rediriger
            if (!empty($errors)) {
                $_SESSION['register_errors'] = $errors;
                $_SESSION['register_old_values'] = [
                    'matricule' => $matricule,
                    'email' => $email
                ];
                
                header("Location: index.php?route=register");
                exit;
            }
        } else {
            // Si ce n'est pas une requête POST, rediriger vers le formulaire d'inscription
            header("Location: index.php?route=register");
            exit;
        }
    }
    /**
     * Déconnecte l'utilisateur
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Détruire toutes les données de session
        $_SESSION = [];

        // Détruire le cookie de session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Détruire la session
        session_destroy();

        // Rediriger vers la page de connexion
        header("Location: index.php?route=login");
        exit;
    }

    public function updateCompte() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $matricule = $_POST['matricule'] ?? null;
        $email = $_POST['email'] ?? null;
        $motDePasse = $_POST['motDePasse'] ?? null;
        $success = false;
        $error = '';
        if ($matricule && $email) {
            $db = new \App\Models\Database();
            $conn = $db->getConnection();
            try {
                if (!empty($motDePasse)) {
                    $motDePasseHash = password_hash($motDePasse, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE gmao__compte SET email = ?, motDePasse = ? WHERE matricule = ?");
                    $stmt->execute([$email, $motDePasseHash, $matricule]);
                } else {
                    $stmt = $conn->prepare("UPDATE gmao__compte SET email = ? WHERE matricule = ?");
                    $stmt->execute([$email, $matricule]);
                }
                $_SESSION['email'] = $email;
                $success = true;
            } catch (\PDOException $e) {
                $error = "Erreur lors de la mise à jour du compte : " . $e->getMessage();
            }
        } else {
            $error = "Champs manquants.";
        }
        if ($success) {
            $_SESSION['compte_update_success'] = "Compte mis à jour avec succès.";
        } else {
            $_SESSION['compte_update_error'] = $error;
        }
        header('Location: ../../platform_gmao/public/index.php?route=compte/update_compte&id=' . urlencode($matricule));
        exit;
    }
}
