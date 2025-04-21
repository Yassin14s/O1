<?php
// Script de migration pour ajouter la colonne genre aux entrées existantes
$dataFile = "database/data.csv";

if (file_exists($dataFile)) {
    // Lire les données
    $rows = [];
    $file = fopen($dataFile, "r");
    while (($data = fgetcsv($file)) !== false) {
        // S'assurer que chaque ligne a au moins 13 colonnes
        if (count($data) < 13) {
            $data = array_pad($data, 13, '');
        }
        $rows[] = $data;
    }
    fclose($file);
    
    // Écrire les données mises à jour
    $file = fopen($dataFile, "w");
    foreach ($rows as $row) {
        fputcsv($file, $row);
    }
    fclose($file);
    
    echo "Migration terminée avec succès. " . count($rows) . " enregistrements mis à jour.";
} else {
    echo "Fichier de données non trouvé.";
}