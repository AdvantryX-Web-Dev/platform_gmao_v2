<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load helpers
require_once __DIR__ . '/helpers.php';

// Load Composer autoload if available (for external libs like PhpSpreadsheet)
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

/**
 * Fonction d'autoload pour charger automatiquement les classes
 */
spl_autoload_register(function ($class) {
    // Préfixe de base pour l'espace de noms
    $prefix = 'App\\';
    
    // Longueur du préfixe
    $prefixLength = strlen($prefix);
    
    // Vérifier si la classe commence par le préfixe
    if (strncmp($prefix, $class, $prefixLength) !== 0) {
        // Non, passer au prochain autoloader enregistré
        return;
    }
    
    // Obtenir le chemin relatif de la classe
    $relativeClass = substr($class, $prefixLength);
    
    // Convertir les séparateurs d'espace de noms en séparateurs de répertoires
    // et ajouter .php
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Si le fichier existe, le charger
    if (file_exists($file)) {
        require $file;
    }
}); 