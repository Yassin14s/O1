<?php
// Récupération de la liste des événements au format JSON
// Filtrer pour n'afficher que les événements "En cours"
require_once 'events.php';

header('Content-Type: application/json');

$events = getEvents();
$jsonEvents = [];

foreach ($events as $event) {
    // Vérifier si le statut est "En cours" (index 4 correspond au statut)
    if ($event[4] === "En cours") {
        $jsonEvents[] = [
            'id' => $event[0],
            'name' => $event[1],
            'date' => $event[2],
            'location' => $event[3],
            'status' => $event[4]
        ];
    }
}

echo json_encode($jsonEvents);
?>