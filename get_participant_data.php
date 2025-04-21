<?php
// Définir le type de contenu de la réponse
header('Content-Type: application/json');

// Initialiser la réponse
$response = [
    'success' => false,
    'message' => '',
    'id' => '',
    'nom' => '',
    'prenom' => '',
    'organisme' => '',
    'fonction' => '',
    'email' => ''
];

// Vérifier si l'ID du participant est fourni
if (isset($_GET['id'])) {
    $participantId = $_GET['id'];
    
    // Rechercher le participant dans la base de données
    $dataFile = "database/data.csv";
    
    if (file_exists($dataFile)) {
        $file = fopen($dataFile, "r");
        $found = false;
        
        while (($data = fgetcsv($file)) !== false) {
            // Vérifier si l'ID du participant (index 11) correspond
            if (isset($data[11]) && $data[11] === $participantId) {
                $response['success'] = true;
                $response['id'] = $data[11];
                $response['nom'] = $data[0];
                $response['prenom'] = $data[1];
                $response['organisme'] = $data[2];
                $response['fonction'] = $data[3];
                $response['email'] = $data[5];
                $found = true;
                break;
            }
        }
        fclose($file);
        
        if (!$found) {
            $response['message'] = "Participant non trouvé avec cet ID: " . $participantId;
        }
    } else {
        $response['message'] = "Base de données des participants non trouvée";
    }
} else {
    $response['message'] = "ID de participant manquant";
}

// Renvoyer la réponse JSON
echo json_encode($response);
?>