<?php
session_start();
require_once 'events.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['access_granted']) || $_SESSION['access_granted'] !== true) {
    header("Location: view.php");
    exit();
}

// Traiter l'ajout d'événement
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Vérifier que tous les champs requis sont présents
    if (isset($_POST["name"]) && isset($_POST["date"]) && isset($_POST["location"])) {
        $name = trim($_POST["name"]);
        $date = trim($_POST["date"]);
        $location = trim($_POST["location"]);
        
        // Validation de base
        if (empty($name) || empty($date) || empty($location)) {
            $_SESSION['event_error'] = "Tous les champs sont obligatoires.";
            header("Location: view.php?tab=events");
            exit();
        }
        
        // Ajouter l'événement
        $eventId = addEvent($name, $date, $location);
        
        // Rediriger vers la page des événements
        header("Location: view.php?tab=events");
        exit();
    } else {
        $_SESSION['event_error'] = "Formulaire incomplet.";
        header("Location: view.php?tab=events");
        exit();
    }
} else {
    // Si ce n'est pas une requête POST, rediriger vers la page principale
    header("Location: view.php");
    exit();
}
?>