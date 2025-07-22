<?php

namespace App\Controllers;

use App\Models\Database;
use App\Models\Intervention_model;
use App\Models\Demande_maintenancier;

class InterventionController
{
    /**
     * Display the preventive intervention page
     */
    public function index()
    {
        include(__DIR__ . '/../views/intervention/InterventionPreventive.php');
    }
    public function preventiveByChaine($prodline_id, $nomCh)
    {
        $machines = Intervention_model::preventiveByChaine($prodline_id, $nomCh);

        return $machines;
    }
    /**
     * Display the preventive curative page
     */
    public function indexCorrective()
    {
        include(__DIR__ . '/../views/intervention/InterventionCurative.php');
    }
    /**
     * Display the curative by chaine
     */
    public function curativeByChaine()
    {

        $machines = Intervention_model::curativeByChaine();

        return $machines;
    }

    /**
     * Display the number of intervention by machine
     */
    public function getNbInterPannMach($id_machine)
    {
        return Intervention_model::nbInterPanneMachine($id_machine);
    }


    public function ajouterDemande()
    {
        try {
            if (isset($_POST['Ajouter'])) {
                $machine_id = $_POST['machines'] ?? null;
                $intervention_date = $_POST['intervention_date'] ?? null;
                $maintainer_matricule = $_POST['maintenanciers'] ?? null;
                $intervention_type_id = $_POST['intervention_type_id'] ?? null;
                $machine_status = $_POST['machine_status'] ?? null;

                // Valider les données requises
                if (!$machine_id || !$intervention_type_id || !$maintainer_matricule || !$intervention_date || !$machine_status) {
                    header('Location: index.php?route=intervention_curative&status=error');
                    exit;
                }

                // Convertir la date d'intervention en format date (sans heure)
                $intervention_date_sql = date('Y-m-d', strtotime($intervention_date));

                // Connexion à la base de données
                $db = new \App\Models\Database();
                $conn = $db->getConnection();

                try {
                    // Démarrer une transaction
                    $conn->beginTransaction();

                    // Convertir le matricule du maintenancier en ID
                    $stmt = $conn->prepare("SELECT id FROM init__employee WHERE matricule = :matricule");
                    $stmt->bindParam(':matricule', $maintainer_matricule, \PDO::PARAM_STR);
                    $stmt->execute();
                    $maintainer_id = $stmt->fetchColumn();

                    if (!$maintainer_id) {
                        throw new \PDOException("Impossible de trouver l'ID de l'employé avec le matricule: " . $maintainer_matricule);
                    }

                    // 1. Insérer dans gmao__intervention_action avec des paramètres nommés
                    $stmt = $conn->prepare("
                        INSERT INTO gmao__intervention_action 
                        (machine_id, intervention_date, maintenance_by, intervention_type_id, created_at) 
                        VALUES (:machine_id, :intervention_date, :maintenance_by, :intervention_type_id, NOW())
                    ");

                    $stmt->bindParam(':machine_id', $machine_id, \PDO::PARAM_INT);
                    $stmt->bindParam(':intervention_date', $intervention_date_sql);
                    $stmt->bindParam(':maintenance_by', $maintainer_id, \PDO::PARAM_INT);
                    $stmt->bindParam(':intervention_type_id', $intervention_type_id, \PDO::PARAM_INT);

                    $stmt->execute();
                    $intervention_id = $conn->lastInsertId();

                    // 2. Mettre à jour le statut de la machine
                    $stmt = $conn->prepare("
                        UPDATE init__machine 
                        SET machines_status_id = :status_id, updated_at = NOW() 
                        WHERE id = :machine_id
                    ");

                    $stmt->bindParam(':status_id', $machine_status, \PDO::PARAM_INT);
                    $stmt->bindParam(':machine_id', $machine_id, \PDO::PARAM_INT);
                    $stmt->execute();

                    // Commit si tout s'est bien passé
                    $conn->commit();
                    header('Location: index.php?route=intervention_curative&status=succ');
                    exit;
                } catch (\PDOException $e) {
                    // Rollback en cas d'erreur
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }

                    // Rediriger avec le message d'erreur et le code
                    header('Location: index.php?route=intervention_curative&status=error');
                    exit;
                }
            } else {
                header('Location: index.php?route=intervention_curative');
                exit;
            }
        } catch (\Exception $e) {
            error_log("Exception dans ajouterDemande: " . $e->getMessage());
            header('Location: index.php?route=intervention_curative&status=error');
            exit;
        }
    }
    function maint_dispo()
    {
        return Intervention_model::maint_dispo();
    }
    /**
     * Display the historique of intervention by machine
     */
    public function historiqueIntervsMach()
    {
        include(__DIR__ . '/../views/intervention/HistoriqueIntervsMach.php');
    }


    public function planningSave()
    {
        return Intervention_model::planningSave();
    }

    /**
     * Save a preventive intervention
     */
    public function savePreventive()
    {

        return Intervention_model::savePreventive();
    }

    /**
     * Récupère la liste des aléas en production
     * 
     * @param string|null $prodline_id Filtre optionnel par chaîne de production
     * @return array Liste des aléas en production
     */
    public function getAleasProduction($nomCh = null)
    {
        $db = new \App\Models\Database();
        $conn = $db->getConnection();

        $query = "
            SELECT 
                ari.machine_id,
                MIN(ari.smartbox) AS smartbox,
                COUNT(ari.id) AS nb_aleas,
                MIN(at.designation) AS aleas_type_name,
                GROUP_CONCAT(DISTINCT em.first_name) AS operator_name,
                MAX(ari.created_at) AS created_at,
                GROUP_CONCAT(DISTINCT em2.first_name) AS monitor_name,
                MIN(pi.prod_line) AS prod_line,
                CASE 
                    WHEN SUM(CASE WHEN aemi.created_at IS NOT NULL THEN 1 ELSE 0 END) = COUNT(ari.id) THEN 'Terminé'
                    WHEN SUM(CASE WHEN ami.monitor IS NOT NULL THEN 1 ELSE 0 END) > 0 THEN 'En cours'
                    ELSE 'En attente'
                END AS status
            FROM 
                aleas__req_interv ari
            LEFT JOIN 
                aleas__mon_interv ami ON ari.id = ami.req_interv_id
            LEFT JOIN 
                init__aleas_type at ON ami.aleas_type_id = at.id
            LEFT JOIN 
                aleas__end_mon_interv aemi ON ari.id = aemi.req_interv_id
            LEFT JOIN
                prod__implantation pi ON ari.machine_id = pi.machine_id
            LEFT JOIN 
                init__employee em ON ari.operator = em.matricule
            LEFT JOIN 
                init__employee em2 ON ami.monitor = em2.matricule
            WHERE 
                ari.machine_id IS NOT NULL 
                AND ari.machine_id != ''
        ";

        // Ajout du paramètre s'il est spécifié
        $params = [];
        if (!empty($nomCh)) {
            $query .= " AND pi.prod_line = :nomCh";
            $params[':nomCh'] = $nomCh;
        }

        // Grouper par machine_id
        $query .= " GROUP BY ari.machine_id";

        // Limiter et trier les résultats
        $query .= " ORDER BY MAX(ari.created_at) DESC";

        try {
            $stmt = $conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des aléas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les aléas pour une machine spécifique
     * 
     * @param string $machine_id ID de la machine
     * @return array Liste des aléas pour la machine spécifiée
     */
    public function getAleasByMachine($machine_id)
    {
        $db = new \App\Models\Database();
        $conn = $db->getConnection();

        $query = "
            SELECT 
                ari.id,
                ari.machine_id,
                ari.operator,
                ari.smartbox,
                ari.group,
                ari.created_at,
                ami.id AS mon_interv_id,
                ami.monitor,
                ami.aleas_type_id,
                at.call_maint,
                aemi.created_at AS end_date,
                pi.prod_line,
                at.designation as aleas_type_name,
                concat(em.last_name, ' ', em.first_name) as operator_name,
                concat(em2.last_name, ' ', em2.first_name) as monitor_name
            FROM 
                aleas__req_interv ari
            LEFT JOIN 
                aleas__mon_interv ami ON ari.id = ami.req_interv_id
            LEFT JOIN 
                init__aleas_type at ON ami.aleas_type_id = at.id
            LEFT JOIN 
                aleas__end_mon_interv aemi ON ari.id = aemi.req_interv_id
            LEFT JOIN
                prod__implantation pi ON ari.machine_id = pi.machine_id
            LEFT JOIN 
                init__employee em on ari.operator = em.matricule
            LEFT JOIN 
                init__employee em2 on ami.monitor = em2.matricule
            WHERE 
                ari.machine_id = :machine_id
            ORDER BY ari.created_at DESC
        ";

        try {
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':machine_id', $machine_id, \PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\PDOException $e) {
            error_log("Erreur lors de la récupération des aléas par machine: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Affiche la page des aléas d'une machine spécifique
     */
    public function showAleasByMachine()
    {
        $machine_id = $_GET['machine_id'] ?? '';

        if (empty($machine_id)) {
            header('Location: index.php?route=intervention_aleas');
            exit;
        }

        $aleas = $this->getAleasByMachine($machine_id);
        include(__DIR__ . '/../views/intervention/InterventionAleasMachine.php');
    }

    /**
     * Affiche la page des aléas en production
     */
    public function showAleasProduction()
    {
        include(__DIR__ . '/../views/intervention/InterventionAleas.php');
    }
}
