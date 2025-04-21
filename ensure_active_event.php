<?php
// Fichier ensure_active_event.php
// Ce script vérifie qu'au moins un événement actif existe
// Si aucun n'existe, il réactive l'événement par défaut ou en crée un nouveau

require_once 'events.php';

// Obtenir tous les événements
$events = getEvents();

// Vérifier s'il existe au moins un événement avec le statut "En cours"
$hasActiveEvent = false;
$hasDefaultEvent = false;
$defaultEventId = null;

foreach ($events as $event) {
    if ($event[4] === "En cours") {
        $hasActiveEvent = true;
    }
    
    if ($event[0] === "1") {
        $hasDefaultEvent = true;
        $defaultEventId = $event[0];
    }
}

// Si aucun événement actif n'existe
if (!$hasActiveEvent) {
    // Si l'événement par défaut existe, le réactiver
    if ($hasDefaultEvent && $defaultEventId) {
        updateEventStatus($defaultEventId, "En cours");
        echo "L'événement par défaut (ID: $defaultEventId) a été réactivé.";
    } 
    // Sinon, créer un nouvel événement par défaut
    else {
        $newId = addEvent(
            "Programme de coopération bilatérale 2025", 
            date("Y-m-d"), 
            "Enabel"
        );
        echo "Un nouvel événement par défaut a été créé (ID: $newId).";
    }
} else {
    echo "Au moins un événement actif existe déjà.";
}
?>