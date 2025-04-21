<?php
session_start();
require_once 'events.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['access_granted']) || $_SESSION['access_granted'] !== true) {
    header('Location: view.php');
    exit();
}

// Vérifier si les paramètres nécessaires sont présents
if (isset($_POST['event_id']) && isset($_POST['status'])) {
    $eventId = $_POST['event_id'];
    $newStatus = $_POST['status'];
    
    // Mettre à jour le statut de l'événement
    $updated = updateEventStatus($eventId, $newStatus);
    
    if ($updated) {
        // Journaliser l'action pour déboguer
        file_put_contents('events_log.txt', date('Y-m-d H:i:s') . " - Événement ID: $eventId - Nouveau statut: $newStatus\n", FILE_APPEND);
        
        // Rediriger vers la page d'administration
        header('Location: view.php?tab=events&success=1');
    } else {
        // Rediriger avec un message d'erreur
        header('Location: view.php?tab=events&error=1');
    }
} else {
    // Rediriger avec un message d'erreur
    header('Location: view.php?tab=events&error=2');
}
exit();
?>