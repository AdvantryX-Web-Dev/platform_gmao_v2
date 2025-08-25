<?php

namespace App\Controllers;

class ScanController
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
    }

    /**
     * Handle image upload from tablet camera, save into storage/scan, call Python to decode QR/barcode, return JSON
     * Expects: POST multipart with file field 'photo' and optional 'target' ('equipment'|'machine')
     */
    public function decode()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            return;
        }

        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {

            http_response_code(400);
            $errMap = [
                UPLOAD_ERR_INI_SIZE => 'Taille du fichier dépasse upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'Taille du fichier dépasse MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'Fichier partiellement téléchargé',
                UPLOAD_ERR_NO_FILE => 'Aucun fichier envoyé',
                UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                UPLOAD_ERR_CANT_WRITE => 'Échec d\'écriture sur disque',
                UPLOAD_ERR_EXTENSION => 'Téléchargement stoppé par une extension',
            ];

            $detail = isset($_FILES['photo']['error']) ? ($errMap[$_FILES['photo']['error']] ?? 'Erreur inconnue') : 'Aucune image valide reçue';
            echo json_encode(['success' => false, 'error' => 'Échec upload: ' . $detail]);

            return;
        }

        $uploadDir = __DIR__ . '/../../storage/scan';
        if (!is_dir($uploadDir)) {
            if (!@mkdir($uploadDir, 0775, true)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Impossible de créer le dossier de stockage']);
                return;
            }
        }

        if (!is_writable($uploadDir)) {
            @chmod($uploadDir, 0775);
        }

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        if (!$ext) {
            $ext = 'jpg';
        }
        $filename = 'scan_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
        $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destPath)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Échec de l\'enregistrement du fichier']);
            return;
        }
        // move_uploaded_file($_FILES['photo']['tmp_name'], $destPath);

        $scriptPath = __DIR__ . '/../views/G_equipements/qr_scanner/scan_qr.py';

        // Run local Python script (cv2-based) using configured path or auto-detect
        $configPath = __DIR__ . '/../../config/app.php';
        $configuredPython = null;
        if (file_exists($configPath)) {
            $cfg = include $configPath;
            if (is_array($cfg) && !empty($cfg['python_path'])) {
                $configuredPython = $cfg['python_path'];
            }
        }

        $python = null;
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($configuredPython && is_executable($configuredPython)) {
            $python = $configuredPython;
        } else {
            if ($isWindows) {
                $candidates = ['py', 'python', 'python3'];
                foreach ($candidates as $cand) {
                    $where = @shell_exec('where ' . escapeshellarg($cand));
                    if ($where) {
                        $python = $cand;
                        break;
                    }
                }
            } else {
                // Try common absolute paths first, then which
                $pathCandidates = ['/usr/local/bin/python3', '/usr/bin/python3', '/usr/bin/python', '/usr/local/bin/python'];
                foreach ($pathCandidates as $p) {
                    if (is_executable($p)) {
                        $python = $p;
                        break;
                    }
                }
                if ($python === null) {
                    $candidates = ['python3', 'python'];
                    foreach ($candidates as $cand) {
                        $which = @shell_exec('which ' . escapeshellarg($cand));
                        if ($which) {
                            $python = trim($cand);
                            break;
                        }
                    }
                }
            }
        }

        if ($python === null) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => "Python introuvable. Configurez 'SCANNER_URL' pour le microservice ou installez Python et réglez 'python_path'.",
            ]);
            return;
        }
        // Build command
        if ($isWindows && strtolower($python) === 'py') {
            $cmd = 'py -3 ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($destPath);
        } else {
            $cmd = escapeshellcmd($python) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($destPath);
        }

        $output = @shell_exec($cmd . ' 2>&1');
        $decoded = trim((string) $output);

        if ($decoded === '') {
            echo json_encode([
                'success' => false,
                'error' => 'Aucun code détecté',
                'file' => $filename
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'text' => $decoded,
            'file' => $filename
        ]);
    }
}


