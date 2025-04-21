<?php
session_start();
require_once 'events.php';

// Vérification de sécurité - Permet d'assurer que seul un utilisateur autorisé peut importer les contacts
if (!isset($_SESSION['access_granted']) || $_SESSION['access_granted'] !== true) {
    // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
    echo "<script>alert('Veuillez vous connecter pour accéder à cette fonctionnalité.'); window.location.href='view.php';</script>";
    exit();
}

// Fonction pour générer un ID unique pour chaque participant
function generateParticipantId() {
    return 'p_' . uniqid();
}

// Création du dossier de base de données s'il n'existe pas
$dbFolder = "database";
if (!file_exists($dbFolder)) {
    mkdir($dbFolder, 0777, true);
}

// Création du dossier QR codes s'il n'existe pas
$qrFolder = "qr_codes";
if (!file_exists($qrFolder)) {
    mkdir($qrFolder, 0777, true);
}

// Vérification si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $importCount = 0;
    $errorCount = 0;
    $errorMessages = [];
    
    // Ouverture du fichier CSV
    $csvFile = 'Contacts_Enabel__avec_Organisme_.csv';
    
    if (file_exists($csvFile)) {
        $file = fopen($csvFile, "r");
        
        // Sauter la première ligne (en-têtes)
        fgetcsv($file);
        
        // Lire chaque ligne du CSV
        while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (count($row) >= 6) { // S'assurer qu'il y a assez de colonnes
                $nom = trim($row[0]);
                $prenom = trim($row[1]);
                $organisme = trim($row[2]);
                $fonction = trim($row[3]);
                $telephone = trim($row[4]);
                $email = trim($row[5]);
                
                // Vérifier si l'email existe déjà dans la base de données
                $existingEmail = false;
                $dataFile = "database/data.csv";
                
                if (file_exists($dataFile)) {
                    $dataHandle = fopen($dataFile, "r");
                    while (($data = fgetcsv($dataHandle)) !== FALSE) {
                        if (isset($data[5]) && strtolower($data[5]) === strtolower($email)) {
                            $existingEmail = true;
                            break;
                        }
                    }
                    fclose($dataHandle);
                }
                
                if (!$existingEmail) {
                    // Générer un ID unique
                    $participantId = generateParticipantId();
                    
                    // Date et heure actuelles
                    $date_inscription = date("Y-m-d");
                    $heure_inscription = date("H:i:s");
                    
                    // Préparer les données du participant
                    $participantData = [
                        $nom,                      // Nom
                        $prenom,                   // Prénom
                        $organisme,                // Organisme
                        $fonction,                 // Fonction
                        $telephone,                // Téléphone
                        $email,                    // Email
                        "",                        // Âge (vide - à compléter par l'utilisateur)
                        $date_inscription,         // Date d'inscription
                        $heure_inscription,        // Heure d'inscription
                        "",                        // Signature (vide - à compléter par l'utilisateur)
                        "1",                       // ID de l'événement (1 par défaut)
                        $participantId             // ID du participant
                    ];
                    
                    // Sauvegarder dans le fichier de la base de données
                    $file_db = fopen($dataFile, "a");
                    fputcsv($file_db, $participantData);
                    fclose($file_db);
                    
                    // Créer les données du QR code
                    $qrData = json_encode([
                        "id" => $participantId,
                        "nom" => $nom,
                        "prenom" => $prenom,
                        "email" => $email
                    ]);
                    
                    // Sauvegarder les données QR au format JSON
                    $qrFilename = $qrFolder . "/" . $participantId . ".json";
                    file_put_contents($qrFilename, $qrData);
                    
                    $importCount++;
                } else {
                    $errorCount++;
                    $errorMessages[] = "L'email $email existe déjà dans la base de données.";
                }
            } else {
                $errorCount++;
                $errorMessages[] = "Ligne ignorée car format incorrect: " . implode(",", $row);
            }
        }
        fclose($file);
        
        // Message de succès
        $message = "Importation terminée! $importCount contacts ajoutés avec succès.";
        if ($errorCount > 0) {
            $message .= " $errorCount erreurs rencontrées.";
        }
        
        // Redirection vers la page de gestion avec message de succès
        echo "<script>alert('$message'); window.location.href='view.php?tab=database';</script>";
    } else {
        echo "<script>alert('Fichier CSV non trouvé.'); window.location.href='view.php';</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importation de Contacts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background: linear-gradient(to right, #d3d3d3, #f5f5f5);
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d32f2f;
        }
        .logo {
            width: 180px;
            margin-bottom: 20px;
        }
        .info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        .info ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn-cancel {
            background-color: #f44336;
        }
        .btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://www.enabel.be/app/uploads/2023/04/Enabel_Logo_Color_RGB.png" alt="Enabel Logo" class="logo">
        <h1>Importation de Contacts</h1>
        
        <div class="info">
            <p><strong>Cette opération va importer les contacts depuis le fichier:</strong> Contacts_Enabel__avec_Organisme_.csv</p>
            <p>Le processus va:</p>
            <ul>
                <li>Vérifier les emails en double (ignorer les contacts déjà existants)</li>
                <li>Ajouter les nouveaux contacts à la base de données</li>
                <li>Générer un QR code unique pour chaque nouveau contact</li>
            </ul>
        </div>
        
        <div class="warning">
            <strong>Note importante:</strong> Les champs "Signature" et "Âge" ne sont pas présents dans le fichier CSV.
            Ces informations seront automatiquement recueillies lorsque les participants s'inscriront à un événement.
        </div>
        
        <form method="POST">
            <button type="submit" class="btn">Importer les Contacts</button>
            <a href="view.php" class="btn btn-cancel">Annuler</a>
        </form>
    </div>
</body>
</html>