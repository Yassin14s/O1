<?php
// Définir le type de contenu de la réponse
header('Content-Type: application/json');

// Initialiser la réponse
$response = [
    'success' => false,
    'message' => ''
];

// Vérifier si la requête est de type POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Vérifier si les données requises sont présentes
    if (isset($_POST['tagId']) && isset($_POST['participantId'])) {
        $tagId = $_POST['tagId'];
        $participantId = $_POST['participantId'];
        
        // Créer le dossier de base de données s'il n'existe pas
        $dbFolder = "database";
        if (!file_exists($dbFolder)) {
            mkdir($dbFolder, 0777, true);
        }
        
        // Vérifier si le participant existe
        $participantExists = false;
        $dataFile = "database/data.csv";
        
        if (file_exists($dataFile)) {
            $file = fopen($dataFile, "r");
            
            while (($data = fgetcsv($file)) !== false) {
                if (isset($data[11]) && $data[11] === $participantId) {
                    $participantExists = true;
                    break;
                }
            }
            fclose($file);
        }
        
        if (!$participantExists) {
            $response['message'] = "Participant non trouvé avec cet ID: " . $participantId;
            echo json_encode($response);
            exit();
        }
        
        // Fichier de mapping NFC
        $nfcMappingFile = "database/nfc_mapping.csv";
        $tagAlreadyAssociated = false;
        $updatedMapping = [];
        
        // Vérifier si le tag est déjà associé
        if (file_exists($nfcMappingFile)) {
            $file = fopen($nfcMappingFile, "r");
            
            while (($data = fgetcsv($file)) !== false) {
                if (isset($data[0]) && $data[0] === $tagId) {
                    // Tag déjà utilisé, mettre à jour l'association
                    $updatedMapping[] = [$tagId, $participantId];
                    $tagAlreadyAssociated = true;
                } else {
                    $updatedMapping[] = $data;
                }
            }
            fclose($file);
        }
        
        // Si le tag n'est pas encore associé, ajouter une nouvelle association
        if (!$tagAlreadyAssociated) {
            $updatedMapping[] = [$tagId, $participantId];
        }
        
        // Écrire le fichier de mapping mis à jour
        $file = fopen($nfcMappingFile, "w");
        foreach ($updatedMapping as $mapping) {
            fputcsv($file, $mapping);
        }
        fclose($file);
        
        $response['success'] = true;
        if ($tagAlreadyAssociated) {
            $response['message'] = "Badge NFC mis à jour avec succès";
        } else {
            $response['message'] = "Badge NFC associé avec succès";
        }
    } else {
        $response['message'] = "Données manquantes pour l'association";
    }
} else {
    $response['message'] = "Méthode non autorisée";
}

// Renvoyer la réponse JSON
echo json_encode($response);
?>