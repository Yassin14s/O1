<?php
require_once 'events.php'; // Inclure le fichier de gestion
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des événements</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Liste des événements</h1>
        
        <!-- Afficher les événements avec boutons -->
        <?php displayEventsWithButtons(); ?>
        
        <hr>
        <h2>Ajouter un nouvel événement</h2>
        <form action="addevent.php" method="post">
            <div class="form-group">
                <label for="name">Nom de l'événement:</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" class="form-control" id="date" name="date" required>
            </div>
            <div class="form-group">
                <label for="location">Lieu:</label>
                <input type="text" class="form-control" id="location" name="location" required>
            </div>
            <button type="submit" class="btn btn-success">Ajouter</button>
        </form>
    </div>
</body>
</html>