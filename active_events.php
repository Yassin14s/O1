<?php
// Fichier spécial pour ld7.html qui renvoie uniquement les événements actifs
require_once 'events.php';

// Désactiver le cache
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header('Content-Type: application/json');

// Récupérer tous les événements
$events = getEvents();
$result = [];

// Filtrer uniquement les événements "En cours"
foreach ($events as $event) {
    if ($event[4] === "En cours") {
        $result[] = [
            'id' => $event[0],
            'name' => $event[1],
            'date' => $event[2],
            'location' => $event[3]
        ];
    }
}

// Renvoyer le résultat en JSON
echo json_encode($result);
?>