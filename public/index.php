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
use App\Controllers\Motif_mvtController;
use App\Controllers\Intervention_typeController;
use App\Controllers\Machines_statusController;
use App\Controllers\InterventionPlanningController;
use App\Controllers\EquipementController;
use App\Controllers\EquipementsCategoryController;
use App\Controllers\Equipment_MachineController;
use App\Controllers\AccessoryController;
use App\Controllers\Mouvement_equipmentController;
// Créer les instances des contrôleurs
$authController = new AuthController();
$machineController = new MachineController();
$interventionController = new InterventionController();
$maintainerController = new MaintainerController();
$machineBoxController = new Machine_boxController();
$mouvement_machinesController = new Mouvement_machinesController();
$Motif_mvtController = new Motif_mvtController();
$intervention_typeController = new Intervention_typeController();
$machines_statusController = new Machines_statusController();
$intervention_planningController = new InterventionPlanningController();
$equipementController = new EquipementController();
$equipementsCategoryController = new EquipementsCategoryController();
$Equipment_MachineController = new Equipment_MachineController();
$AccessoryController = new AccessoryController();
$Mouvement_equipmentController = new Mouvement_equipmentController();
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

    /** Gestion initiale des machines */
    case 'machines':
        $machineController->list();
        break;

    case 'machines/state':
        $controller = new MachineController();
        $controller->machines_state();
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
    /** Gestion des mainteneurs */
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
    /** Gestion des categories */
    case 'categories':

        $Motif_mvtController->list();
        break;
    case 'category/create':
        $Motif_mvtController->create();
        break;
    case 'category/edit':
        $Motif_mvtController->edit();
        break;
    case 'category/delete':
        $Motif_mvtController->delete();
        break;
    case 'category/audit_trails':
        $Motif_mvtController->auditTrails();
        break;
    /** Gestion initiale des interventions */
    case 'intervention_type/list':
        $intervention_typeController->list();
        break;
    case 'intervention_type/create':
        $intervention_typeController->create();
        break;
    case 'intervention_type/edit':
        $intervention_typeController->edit();
        break;
    case 'intervention_type/delete':
        $intervention_typeController->delete();
        break;
    /** Gestion des équipements */
    case 'equipement/list':
        $equipementController->list();
        break;
    case 'equipement/create':
        $equipementController->create();
        break;
    case 'equipement/edit':
        $equipementController->edit();
        break;
    case 'equipement/delete':
        $equipementController->delete();
        break;
    case 'equipementsCategory/list':
        $equipementsCategoryController->list();
        break;
    case 'equipementsCategory/create':
        $equipementsCategoryController->create();
        break;
    case 'equipementsCategory/edit':
        $equipementsCategoryController->edit();
        break;
    case 'equipementsCategory/delete':
        $equipementsCategoryController->delete();
        break;
    case 'equipement/audit_trails':
    /** Gestion des états des machines */
    case 'machines_status/list':
        $machines_statusController->list();
        break;
    case 'machines_status/create':
        $machines_statusController->create();
        break;
    case 'machines_status/edit':
        $machines_statusController->edit();
        break;
    case 'machines_status/delete':
        $machines_statusController->delete();
        break;


    /** Gestion des machines */
    case 'Gestion_machines/status':
        $machineController->machines_state();
        break;
    case 'machinesbox':
        require_once __DIR__ . '/../src/views/G_machines/Machine_box.php';
        break;
    case 'machine/mouvement':
        require_once __DIR__ . '/../src/views/G_machines/MouvMachines.php';
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

    case 'mouvement_machines/accept':
        $mouvement_machinesController->accept();
        break;
    case 'mouvement_machines/reject':

        $mouvement_machinesController->reject();
        break;

    case 'mouvement_machines/getMachinesByType':
        $mouvement_machinesController->getMachinesByType();
        break;
    case 'mouvement_machines/saveMouvement':
        $mouvement_machinesController->saveMouvement();
        break;
    case 'mouvement_machines/getEquipementsByMachine':
        $equipementController->getEquipementsByMachine();
        break;

    /** Gestion des équipements */
    case 'Gestion_equipements/status':
        $equipementController->equipements_state();
        break;
    case 'mouvement_equipements/history':
        $Mouvement_equipmentController->showHistoryEquipement();
        break;
    case 'equipement_machine':
        require_once __DIR__ . '/../src/views/G_equipements/Equipement_machine.php';
        break;
    case 'equipement_machine/affectation_equipmentMachine':
        $Equipment_MachineController->affectation_equipmentMachine();
        break;
    case 'equipement_machine/affecter':
        $AccessoryController->affecter();
        break;

    case 'mouvement_equipements/entre_magasin':
        $Mouvement_equipmentController->entre_magasin();
        break;

    case 'mouvement_equipements/sortie_magasin':
        $Mouvement_equipmentController->sortie_magasin();
        break;

    case 'mouvement_equipements/accept':
        $Mouvement_equipmentController->accept();
        break;

    case 'mouvement_equipements/saveMouvement':
        $Mouvement_equipmentController->saveMouvement();
        break;
    /** Gestion des interventions */
    case 'intervention_preventive':
        $interventionController->index();
        break;
    case 'intervention/savePreventive':
        $interventionController->savePreventive();
        break;
    case 'intervention_planning/save':
        $interventionController->planningSave();
        break;
    case 'intervention_planning/list':
        $intervention_planningController->list();
        break;
    case 'intervention_curative':
        $interventionController->indexCorrective();
        break;
    case 'intervention_curative/ajouterDemande':
        $interventionController->ajouterDemande();
        break;
    case 'intervention_aleas':
        $interventionController->showAleasProduction();
        break;
    case 'intervention_aleas_machine':
        $interventionController->showAleasByMachine();
        break;
    case 'historique_intervs_mach':
        $interventionController->historiqueIntervsMach();
        break;
    case 'mouvement_machines/history':
        $mouvement_machinesController->showHistoryMachineStatus();
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



    // Route par défaut
    default:
        header("Location: index.php?route=login");
        exit;
}
