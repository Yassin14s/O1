<?php
// Script pour générer un ZIP contenant tous les QR codes
session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['access_granted']) || $_SESSION['access_granted'] !== true) {
    header("Location: view.php");
    exit();
}

// Créer un dossier temporaire pour stocker les QR codes générés
$tempDir = 'temp_qr_codes';
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
} else {
    // Nettoyer le dossier temporaire
    $files = glob($tempDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

// Obtenir tous les QR codes
$qrCodesDir = "qr_codes";
$qrCodes = [];

if (file_exists($qrCodesDir) && is_dir($qrCodesDir)) {
    $qrFiles = scandir($qrCodesDir);
    foreach ($qrFiles as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $participantId = pathinfo($file, PATHINFO_FILENAME);
            $qrData = file_get_contents($qrCodesDir . '/' . $file);
            $qrInfo = json_decode($qrData, true);
            
            if ($qrInfo) {
                $qrCodes[$participantId] = $qrInfo;
            }
        }
    }
}

// Si aucun QR code trouvé
if (empty($qrCodes)) {
    echo "Aucun QR code disponible à télécharger.";
    exit();
}

// Générer un fichier HTML pour chaque QR code
foreach ($qrCodes as $participantId => $qrInfo) {
    $name = isset($qrInfo['prenom']) && isset($qrInfo['nom']) 
        ? $qrInfo['prenom'] . '_' . $qrInfo['nom'] 
        : 'participant_' . $participantId;
    
    // Nettoyer le nom pour le fichier
    $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    
    $fileName = $tempDir . '/' . $name . '.html';
    
    // Créer le contenu HTML du QR code
    $htmlContent = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>QR Code - ' . htmlspecialchars($name) . '</title>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <style>
            body {
                font-family: Arial, sans-serif;
                text-align: center;
                padding: 20px;
            }
            .qr-container {
                margin: 20px auto;
            }
            .participant-info {
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <h1>QR Code - ' . htmlspecialchars($name) . '</h1>
        <div class="participant-info">
            <p><strong>Nom:</strong> ' . htmlspecialchars($qrInfo['nom']) . '</p>
            <p><strong>Prénom:</strong> ' . htmlspecialchars($qrInfo['prenom']) . '</p>
            <p><strong>Email:</strong> ' . htmlspecialchars($qrInfo['email']) . '</p>
        </div>
        <div id="qrcode" class="qr-container"></div>
        <script>
            new QRCode(document.getElementById("qrcode"), {
                text: \'' . addslashes(json_encode($qrInfo)) . '\',
                width: 250,
                height: 250
            });
            
            // Imprimer automatiquement après chargement complet
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>';
    
    file_put_contents($fileName, $htmlContent);
}

// Créer un fichier ZIP contenant tous les QR codes HTML
$zipFile = 'tous_les_qr_codes.zip';
$zip = new ZipArchive();

if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($tempDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($tempDir) + 1);
            
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    $zip->close();
    
    // Télécharger le fichier ZIP
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFile . '"');
    header('Content-Length: ' . filesize($zipFile));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($zipFile);
    
    // Supprimer les fichiers temporaires
    unlink($zipFile);
    
    // Nettoyer le dossier temporaire
    $files = glob($tempDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($tempDir);
    
    exit;
} else {
    echo "Impossible de créer l'archive ZIP.";
}
?>