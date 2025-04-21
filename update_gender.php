<?php
/**
 * update_gender.php
 * Script pour mettre à jour le genre d'un participant dans la base de données CSV
 */

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérification des paramètres requis
if (!isset($_POST['email']) || !isset($_POST['gender'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

// Récupération des paramètres
$email = $_POST['email'];
$gender = $_POST['gender'];

// Vérification de la validité du genre
if ($gender !== 'H' && $gender !== 'F') {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Valeur de genre invalide']);
    exit;
}

// Chemin vers le fichier CSV
$dataFile = "database/data.csv";

// Vérification de l'existence du fichier
if (!file_exists($dataFile)) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Fichier de base de données introuvable']);
    exit;
}

// Lire le fichier CSV
$rows = [];
if (($handle = fopen($dataFile, "r")) !== FALSE) {
    while (($data = fgetcsv($handle)) !== FALSE) {
        $rows[] = $data;
    }
    fclose($handle);
}

// Rechercher le participant par email et mettre à jour son genre
$updated = false;
foreach ($rows as $key => $row) {
    // L'email est à l'index 5 dans le CSV
    if (isset($row[5]) && $row[5] === $email) {
        // Si l'index 11 n'existe pas, agrandir le tableau
        if (!isset($row[11])) {
            $rows[$key] = array_pad($row, 12, '');
        }
        // Mettre à jour le genre à l'index 11
        $rows[$key][11] = $gender;
        $updated = true;
    }
}

// Si aucun participant n'a été trouvé avec cet email
if (!$updated) {
    http_response_code(404); // Not Found
    echo json_encode(['error' => 'Participant non trouvé']);
    exit;
}

// Écrire les modifications dans le fichier CSV
if (($handle = fopen($dataFile, "w")) !== FALSE) {
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);
    
    // Réponse de succès
    http_response_code(200); // OK
    echo json_encode(['success' => true, 'message' => 'Genre mis à jour avec succès']);
} else {
    // Erreur d'écriture dans le fichier
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Erreur lors de la mise à jour du fichier']);
}