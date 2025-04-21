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
$event = getEventById($eventId);

// Vérifier si l'événement existe
if (!$event) {
    echo "<script>alert('Événement introuvable.'); window.location.href='view.php?tab=events';</script>";
    exit;
}

// Traiter le formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $date = $_POST['date'];
    $location = $_POST['location'];
    $status = $_POST['status'];
    
    if (updateEvent($eventId, $name, $date, $location, $status)) {
        echo "<script>alert('Événement mis à jour avec succès.'); window.location.href='view.php?tab=events';</script>";
        exit;
    } else {
        echo "<script>alert('Une erreur s'est produite lors de la mise à jour.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un événement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background: linear-gradient(to right, #d3d3d3, #f5f5f5);
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .logo {
            width: 180px;
            margin-bottom: 20px;
        }
        h2 {
            color: #d32f2f;
            margin-bottom: 20px;
        }
        .form-container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px auto;
            max-width: 500px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .button-group {
            margin-top: 20px;
        }
        .button-group button {
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            margin: 5px;
            border: none;
        }
        .button-primary {
            background: #007bff;
            color: white;
        }
        .button-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <img src="https://www.enabel.be/app/uploads/2023/04/Enabel_Logo_Color_RGB.png" alt="Enabel Logo" class="logo">
    <h2>Modifier un événement</h2>
    
    <div class="form-container">
        <form method="post">
            <div class="form-group">
                <label for="name">Nom de l'événement:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($event[1]); ?>" required>
            </div>
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" value="<?php echo $event[2]; ?>" required>
            </div>
            <div class="form-group">
                <label for="location">Lieu:</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($event[3]); ?>" required>
            </div>
            <div class="form-group">
                <label for="status">Statut:</label>
                <select id="status" name="status">
                    <option value="En cours" <?php echo ($event[4] == "En cours") ? "selected" : ""; ?>>En cours</option>
                    <option value="Terminé" <?php echo ($event[4] == "Terminé") ? "selected" : ""; ?>>Terminé</option>
                </select>
            </div>
            <div class="button-group">
                <button type="submit" class="button-primary">Mettre à jour</button>
                <a href="view.php?tab=events"><button type="button" class="button-secondary">Annuler</button></a>
            </div>
        </form>
    </div>
</body>
</html>