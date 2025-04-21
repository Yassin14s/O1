<?php
session_start();
require_once 'events.php';

// Vérification du code d'accès
if (!isset($_SESSION['access_granted']) || $_SESSION['access_granted'] !== true) {
    // Rediriger vers la page de connexion
    header('Location: view.php');
    exit();
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $date = $_POST['date'];
    $location = $_POST['location'];
    
    // Ajouter l'événement
    $eventId = addEvent($name, $date, $location);
    
    if ($eventId) {
        echo "<script>alert('Événement ajouté avec succès.'); window.location.href='view.php?tab=events';</script>";
    } else {
        echo "<script>alert('Une erreur s'est produite lors de l'ajout de l'événement.'); window.location.href='view.php?tab=events';</script>";
    }
    exit;
} else {
    // Si la page est accédée directement sans POST, rediriger
    header('Location: view.php?tab=events');
    exit;
}
?>