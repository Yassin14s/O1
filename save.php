<?php
require_once 'events.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $date_inscription = date("Y-m-d");
    $heure_inscription = date("H:i:s");
    $eventId = isset($_POST["eventId"]) ? $_POST["eventId"] : "1"; // Utiliser l'événement 1 par défaut si non spécifié
    
    // Si l'événement spécifié n'existe pas, utiliser l'événement 1
    if (getEventById($eventId) === null) {
        $eventId = "1";
    }
    
    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    
    // Vérifier si l'utilisateur existe déjà dans la base de données
    $existingUser = false;
    $existingUserData = null;
    $allData = [];
    $userIndex = -1;
    
    // Créer le dossier de base de données s'il n'existe pas
    $dbFolder = "database";
    if (!file_exists($dbFolder)) {
        mkdir($dbFolder, 0777, true);
    }
    
    $dataFile = $dbFolder . "/data.csv";
    if (file_exists($dataFile)) {
        $file = fopen($dataFile, "r");
        $index = 0;
        while (($data = fgetcsv($file)) !== FALSE) {
            $allData[] = $data;
            if (isset($data[5]) && strtolower($data[5]) === strtolower($email)) {
                $existingUser = true;
                $existingUserData = $data;
                $userIndex = $index;
            }
            $index++;
        }
        fclose($file);
    }
    
    // Générer un identifiant unique pour ce participant si c'est un nouvel utilisateur
    $participantId = $existingUser ? $existingUserData[11] : uniqid('p_');
    
    if ($existingUser) {
        // Mettre à jour les données de l'utilisateur existant
        $allData[$userIndex][0] = $nom;                   // Nom
        $allData[$userIndex][1] = $prenom;                // Prénom
        $allData[$userIndex][2] = $_POST["organisme"];    // Organisme
        $allData[$userIndex][3] = $_POST["fonction"];     // Fonction
        $allData[$userIndex][4] = $_POST["portable"];     // Téléphone
        $allData[$userIndex][6] = $_POST["age"];          // Âge
        $allData[$userIndex][7] = $date_inscription;      // Date d'inscription mise à jour
        $allData[$userIndex][8] = $heure_inscription;     // Heure d'inscription mise à jour
        $allData[$userIndex][9] = $_POST["signature"];    // Signature mise à jour
        $allData[$userIndex][10] = $eventId;              // ID de l'événement
        
        // Sauvegarder les données mises à jour
        $file = fopen($dataFile, "w");
        foreach ($allData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
    } else {
        // Créer un nouvel utilisateur
        $data = [
            $nom,                   // Nom
            $prenom,                // Prénom
            $_POST["organisme"],    // Organisme
            $_POST["fonction"],     // Fonction
            $_POST["portable"],     // Téléphone
            $email,                 // Email
            $_POST["age"],          // Âge
            $date_inscription,      // Date d'inscription
            $heure_inscription,     // Heure d'inscription
            $_POST["signature"],    // Signature
            $eventId,               // ID de l'événement d'inscription
            $participantId          // ID unique du participant
        ];

        // Sauvegarder dans le fichier principal de la base de données
        $file = fopen($dataFile, "a");
        fputcsv($file, $data);
        fclose($file);
    }
    
 // Remplacer la section de création du QR code dans save.php par ce code
// 2. CRÉATION ET STOCKAGE DU QR CODE
// Créer les données du QR code avec toutes les informations pertinentes
$qrData = json_encode([
    "id" => $participantId,
    "nom" => $nom,
    "prenom" => $prenom,
    "email" => $email,
    "organisme" => $_POST["organisme"],
    "fonction" => $_POST["fonction"],
    "age" => $_POST["age"],
    "updated" => time()
]);

// Sauvegarder le QR code dans un fichier
$qrFolder = "qr_codes";
if (!file_exists($qrFolder)) {
    mkdir($qrFolder, 0777, true);
}

// Sauvegarder les données QR au format JSON
$qrFilename = $qrFolder . "/" . $participantId . ".json";
file_put_contents($qrFilename, $qrData);
    
    // 3. ENREGISTREMENT DE LA PRÉSENCE
    markAttendance($email, $eventId);
    
    // Obtenir le nom de l'événement
    $event = getEventById($eventId);
    $eventName = $event[1];
    
    // Créer l'URL pour afficher le QR code
    $qrUrl = "view_participant_qr.php?id=" . $participantId;
    
    // Réponse avec lien vers le QR code
    echo "<div>Inscription enregistrée avec succès pour l'événement : " . $eventName . "</div>";
    echo "<div style='margin-top: 15px;'><a href='" . $qrUrl . "' target='_blank' style='background-color: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Voir mon QR code</a></div>";
}

// Fonction pour marquer la présence
function markAttendance($email, $eventId) {
    $dataFile = "database/data.csv";
    $found = false;
    $userData = null;
    
    // Rechercher l'utilisateur par son email
    if (file_exists($dataFile)) {
        $file = fopen($dataFile, "r");
        
        while (($data = fgetcsv($file)) !== FALSE) {
            if (isset($data[5]) && $data[5] === $email) {
                $found = true;
                $userData = $data;
                break;
            }
        }
        fclose($file);
    }
    
    if ($found) {
        $presenceFolder = "presence";
        if (!file_exists($presenceFolder)) {
            mkdir($presenceFolder, 0777, true);
        }
        
        $attendanceFile = $presenceFolder . "/attendance.csv";
        $today = date("Y-m-d");
        $now = date("H:i:s");
        
        // Vérifier si l'utilisateur a déjà une présence enregistrée pour cet événement aujourd'hui
        $alreadyPresent = false;
        $allAttendance = [];
        
        if (file_exists($attendanceFile)) {
            $file = fopen($attendanceFile, "r");
            while (($data = fgetcsv($file)) !== FALSE) {
                // Si même email, même date et même événement, ne pas l'ajouter
                if (isset($data[0]) && $data[0] === $email && 
                    isset($data[1]) && $data[1] === $today &&
                    isset($data[7]) && $data[7] === $eventId) {
                    $alreadyPresent = true;
                } else {
                    $allAttendance[] = $data;
                }
            }
            fclose($file);
        }
        
        // Préparer l'entrée de présence
        $attendanceEntry = [
            $email,             // Email (identifiant unique)
            $today,             // Date
            $now,               // Heure
            $userData[0],       // Nom
            $userData[1],       // Prénom
            $userData[2],       // Organisme
            $userData[3],       // Fonction
            $eventId            // ID de l'événement
        ];
        
        // Ajouter la nouvelle entrée
        $allAttendance[] = $attendanceEntry;
        
        // Sauvegarder toutes les présences
        $file = fopen($attendanceFile, "w");
        foreach ($allAttendance as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
        
        return true;
    }
    
    return false;
}
?>