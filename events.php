<?php
// Fichier de gestion des événements
// Vérifier si le fichier d'événements existe, sinon le créer avec un événement par défaut
function checkEventsFile() {
    $eventsFile = "events.csv";
    
    if (!file_exists($eventsFile)) {
        $defaultEvent = [
            "1",                                  // ID de l'événement
            "Programme de coopération bilatérale 2025", // Nom de l'événement
            date("Y-m-d"),                        // Date
            "Enabel",                             // Lieu
            "En cours"                            // Statut (En cours, Terminé)
        ];
        
        $file = fopen($eventsFile, "w");
        fputcsv($file, $defaultEvent);
        fclose($file);
    }
    
    return $eventsFile;
}

// Récupérer la liste des événements
function getEvents() {
    $eventsFile = checkEventsFile();
    $events = [];
    
    $file = fopen($eventsFile, "r");
    while (($event = fgetcsv($file)) !== false) {
        $events[] = $event;
    }
    fclose($file);
    
    return $events;
}

// Ajouter un nouvel événement
function addEvent($name, $date, $location) {
    $eventsFile = checkEventsFile();
    $events = getEvents();
    
    // Trouver le prochain ID disponible
    $maxId = 0;
    foreach ($events as $event) {
        if (intval($event[0]) > $maxId) {
            $maxId = intval($event[0]);
        }
    }
    
    $newId = $maxId + 1;
    
    $newEvent = [
        $newId,                 // ID de l'événement
        $name,                  // Nom de l'événement
        $date,                  // Date
        $location,              // Lieu
        "En cours"              // Statut
    ];
    
    $file = fopen($eventsFile, "a");
    fputcsv($file, $newEvent);
    fclose($file);
    
    return $newId;
}

// Récupérer un événement par son ID
function getEventById($eventId) {
    $events = getEvents();
    
    foreach ($events as $event) {
        if ($event[0] == $eventId) {
            return $event;
        }
    }
    
    return null;
}

// Mettre à jour le statut d'un événement
function updateEventStatus($eventId, $status) {
    $eventsFile = checkEventsFile();
    $events = getEvents();
    $updated = false;
    
    $file = fopen($eventsFile, "w");
    
    foreach ($events as $event) {
        if ($event[0] == $eventId) {
            $event[4] = $status;
            $updated = true;
        }
        fputcsv($file, $event);
    }
    
    fclose($file);
    
    return $updated;
}

// NOUVELLE FONCTION: Supprimer un événement par son ID
function deleteEvent($eventId) {
    $eventsFile = checkEventsFile();
    $events = getEvents();
    $deleted = false;
    
    $file = fopen($eventsFile, "w");
    
    foreach ($events as $event) {
        if ($event[0] == $eventId) {
            $deleted = true;
            continue; // Sauter cet événement pour le supprimer
        }
        fputcsv($file, $event);
    }
    
    fclose($file);
    
    return $deleted;
}

// NOUVELLE FONCTION: Mettre à jour toutes les informations d'un événement
function updateEvent($eventId, $name, $date, $location, $status) {
    $eventsFile = checkEventsFile();
    $events = getEvents();
    $updated = false;
    
    $file = fopen($eventsFile, "w");
    
    foreach ($events as $event) {
        if ($event[0] == $eventId) {
            $event[1] = $name;
            $event[2] = $date;
            $event[3] = $location;
            $event[4] = $status;
            $updated = true;
        }
        fputcsv($file, $event);
    }
    
    fclose($file);
    
    return $updated;
}

// Si ce fichier est appelé directement, vérifier le fichier d'événements
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    checkEventsFile();
    echo "Fichier d'événements vérifié.";
}
?>