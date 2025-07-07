<?php
// Démarrer la session
session_start();

// Charger l'autoloader
require_once __DIR__ . '/../config/autoload.php';

// Importer les contrôleurs
use App\Controllers\AuthController;
use App\Controllers\MachineController;
use App\Controllers\InterventionController;
use App\Controllers\MaintainerController;
use App\Controllers\Machine_boxController;
use App\Controllers\Mouvement_machinesController;
// Créer les instances des contrôleurs
$authController = new AuthController();
$machineController = new MachineController();
$interventionController = new InterventionController();
$maintainerController = new MaintainerController();
$machineBoxController = new Machine_boxController();
$mouvement_machinesController = new Mouvement_machinesController();
// Récupérer la route demandée
$route = $_GET['route'] ?? 'login';

// Router les requêtes vers les contrôleurs appropriés
switch ($route) {
    // Routes d'authentification
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->login();
        } else {
            // Si l'utilisateur est déjà connecté, rediriger vers le dashboard
            if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
                require_once __DIR__ . '/../src/views/dashboard.php';
            } else {
                $authController->showLoginForm();
            }
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->register();
        } else {
            $authController->showRegisterForm();
        }
        break;

    case 'logout':
        $authController->logout();
        break;

    case 'dashboard':
        // Vérifier si l'utilisateur est connecté avant d'afficher le dashboard
        if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
            require_once __DIR__ . '/../src/views/dashboard.php';
        } else {
            header("Location: index.php?route=login");
            exit;
        }
        break;

    case 'machinesbox':
        require_once __DIR__ . '/../src/views/Machine_box.php';
        break;
    case 'machine/mouvement':
        require_once __DIR__ . '/../src/views/MouvMachines.php';
        break;
    case 'machines':
        $machineController->list();
        break;
    case 'machine/create':
        $machineController->create();
        break;
    case 'machine/edit':
        $machineController->edit();
        break;
    case 'machine/delete':
        $machineController->delete();
        break;
    case 'intervention_preventive':
        $interventionController->index();
        break;
    case 'intervention_curative':
        $interventionController->indexCorrective();
        break;
    case 'intervention_curative/ajouterDemande':
        $interventionController->ajouterDemande();
        break;
    case 'historique_intervs_mach':
        $interventionController->historiqueIntervsMach();
        break;
    case 'maintainers':
        $maintainerController->list();
        break;
    case 'maintainer/create':
        $maintainerController->create();
        break;
    case 'maintainer/edit':
        $maintainerController->edit();
        break;
    case 'maintainer/delete':
        $maintainerController->delete();
        break;
    case 'audit_trails_history':
        $maintainerController->auditTrails_history();
        break;
    case 'mouvement_machines':
        $mouvement_machinesController->mouvement_machines();
        break;
    case 'mouvement_machines/inter_chaine':
        $mouvement_machinesController->inter_chaine();
        break;
    case 'mouvement_machines/parc_chaine':
        $mouvement_machinesController->parc_chaine();
        break;
    case 'mouvement_machines/chaine_parc':
        $mouvement_machinesController->chaine_parc();
        break;
    // case 'mouvement_machines/pending_reception':
    //     $mouvement_machinesController->pending_reception();
    //     break;
    case 'mouvement_machines/accept':
        $mouvement_machinesController->accept();
        break;
    case 'mouvement_machines/getMachinesByType':
        $mouvement_machinesController->getMachinesByType();
        break;
    case 'mouvement_machines/saveMouvement':
        $mouvement_machinesController->saveMouvement();
        break;

    case 'compte/update_compte':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController->updateCompte();
        } else {
            $matricule = $_GET['id'] ?? null;
            if (!$matricule && isset($_SESSION['email'])) {
                $db = new \App\Models\Database();
                $conn = $db->getConnection();
                $stmt = $conn->prepare("SELECT matricule FROM gmao__compte WHERE email = ?");
                $stmt->execute([$_SESSION['email']]);
                $matricule = $stmt->fetchColumn();
            }
            if ($matricule) {
                $db = new \App\Models\Database();
                $conn = $db->getConnection();
                $stmt = $conn->prepare("SELECT * FROM gmao__compte WHERE matricule = ?");
                $stmt->execute([$matricule]);
                $compte = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($compte) {
                    include __DIR__ . '/../src/views/auth/update_compte.php';
                } else {
                    header('Location: index.php?route=compte/update_compte');
                    exit;
                }
            } else {
                header('Location: index.php?route=compte/update_compte');
                exit;
            }
        }
        break;

    // Routes pour les machines et leurs emplacements (box et lignes de production)


    case 'machine/stats':
        $machineController->getInterventionStats();
        break;
    // Route par défaut
    default:
        header("Location: index.php?route=login");
        exit;
}
