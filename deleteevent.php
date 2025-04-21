<?php
session_start();
require_once 'events.php';

// Vérification du code d'accès
if (!isset($_SESSION['access_granted']) || $_SESSION['access_granted'] !== true) {
    // Rediriger vers la page de connexion
    header('Location: view.php');
    exit();
}

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    header('Location: view.php?tab=events');
    exit;
}

$eventId = $_GET['id'];

// Vérifier si l'événement existe
$event = getEventById($eventId);
if (!$event) {
    echo "<script>alert('Événement introuvable.'); window.location.href='view.php?tab=events';</script>";
    exit;
}

// Supprimer l'événement
if (deleteEvent($eventId)) {
    echo "<script>alert('Événement supprimé avec succès.'); window.location.href='view.php?tab=events';</script>";
} else {
    echo "<script>alert('Une erreur s'est produite lors de la suppression.'); window.location.href='view.php?tab=events';</script>";
}
?>