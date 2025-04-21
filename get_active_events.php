<?php
// Nouveau fichier spécifique pour récupérer UNIQUEMENT les événements actifs
// À utiliser dans ld7.html à la place de get_events.php

require_once 'events.php';

// Désactiver le cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

// Récupérer tous les événements
$events = getEvents();
$activeEvents = [];

// Ne garder que les événements avec le statut "En cours"
foreach ($events as $event) {
    // Vérification stricte du statut
    if ($event[4] === "En cours") {
        $activeEvents[] = [
            'id' => $event[0],
            'name' => $event[1],
            'date' => $event[2],
            'location' => $event[3],
            'status' => $event[4]
        ];
    }
}

// Écrire un log pour débogage
file_put_contents('active_events_log.txt', date('Y-m-d H:i:s') . ' - Active events: ' . count($activeEvents) . "\n", FILE_APPEND);

// Renvoyer uniquement les événements actifs
echo json_encode($activeEvents);
?>