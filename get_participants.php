<?php
// Vérifier si le fichier de données existe
$dataFile = "database/data.csv";
$participants = [];

if (file_exists($dataFile)) {
    $file = fopen($dataFile, "r");
    while (($data = fgetcsv($file)) !== false) {
        // S'assurer que nous avons assez de colonnes
        if (count($data) >= 7) {
            $participants[] = [
                'nom' => $data[0],
                'prenom' => $data[1],
                'organisme' => $data[2],
                'fonction' => $data[3],
                'portable' => $data[4],
                'email' => $data[5],
                'age' => $data[6]
            ];
        }
    }
    fclose($file);
}

// Définir l'en-tête pour indiquer que la réponse est du JSON
header('Content-Type: application/json');

// Renvoyer les participants au format JSON
echo json_encode($participants);
?>