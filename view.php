<?php
session_start();
require_once 'events.php';

// Vérification du code d'accès
if (!isset($_SESSION['access_granted']) || $_SESSION['access_granted'] !== true) {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["code"])) {
        if ($_POST["code"] === "1414") {
            $_SESSION['access_granted'] = true;
        } else {
            echo "<script>alert('Code incorrect !'); window.location.href='view.php';</script>";
            exit();
        }
    } else {
        echo '<form method="POST" style="text-align: center; margin-top: 50px;">
                <h2>🔒 Accès Restreint</h2>
                <input type="password" name="code" placeholder="Entrez le code" required>
                <button type="submit">Valider</button>
              </form>';
        exit();
    }
}

// -- PARTIE 1: BASE DE DONNÉES --
// Lecture du fichier CSV des inscriptions
$dataFile = "database/data.csv";
$rows = [];

if (file_exists($dataFile)) {
    $file = fopen($dataFile, "r");
    while (($data = fgetcsv($file)) !== false) {
        $rows[] = $data;
    }
    fclose($file);
}

// -- PARTIE 3: PRÉSENCE --
// Lecture du fichier CSV des présences
$attendanceFile = "presence/attendance.csv";
$attendanceRows = [];

if (file_exists($attendanceFile)) {
    $file = fopen($attendanceFile, "r");
    while (($data = fgetcsv($file)) !== false) {
        $attendanceRows[] = $data;
    }
    fclose($file);
    
    // Trier les présences par date (descendant) puis par heure (descendant)
    usort($attendanceRows, function($a, $b) {
        // Comparer les dates
        $dateCompare = strcmp($b[1], $a[1]);
        if ($dateCompare !== 0) {
            return $dateCompare;
        }
        // Si même date, comparer les heures
        return strcmp($b[2], $a[2]);
    });
}

// -- PARTIE 4: ÉVÉNEMENTS --
// Récupérer les événements
$events = getEvents();
$eventMap = [];
foreach ($events as $event) {
    $eventMap[$event[0]] = $event[1]; // Mapper l'ID à son nom
}

// Filtrer par événement si spécifié
$selectedEvent = isset($_GET["event"]) ? $_GET["event"] : (count($events) > 0 ? $events[0][0] : "1");
$filteredAttendance = [];

foreach ($attendanceRows as $row) {
    // Vérifier si l'événement est spécifié (compatible avec l'ancienne structure)
    if (isset($row[7]) && $row[7] === $selectedEvent) {
        $filteredAttendance[] = $row;
    } elseif (!isset($row[7]) && $selectedEvent === "1") {
        // Si pas d'événement spécifié dans la ligne et qu'on demande l'événement 1 (par défaut)
        $filteredAttendance[] = $row;
    }
}

// Filtrer également par date si spécifié
$uniqueDates = [];
foreach ($filteredAttendance as $row) {
    if (!in_array($row[1], $uniqueDates)) {
        $uniqueDates[] = $row[1];
    }
}
rsort($uniqueDates); // Trier les dates par ordre décroissant

$selectedDate = isset($_GET["date"]) ? $_GET["date"] : (count($uniqueDates) > 0 ? $uniqueDates[0] : date("Y-m-d"));
$dateFilteredAttendance = [];

foreach ($filteredAttendance as $row) {
    if ($row[1] === $selectedDate) {
        $dateFilteredAttendance[] = $row;
    }
}

// -- PARTIE 2: STOCKAGE QR CODES --
// Lecture des QR codes disponibles
$qrCodesDir = "qr_codes";
$qrCodes = [];

if (file_exists($qrCodesDir) && is_dir($qrCodesDir)) {
    $qrFiles = scandir($qrCodesDir);
    foreach ($qrFiles as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
            $participantId = pathinfo($file, PATHINFO_FILENAME);
            $qrData = file_get_contents($qrCodesDir . '/' . $file);
            $qrInfo = json_decode($qrData, true);
            
            if ($qrInfo) {
                $qrCodes[$participantId] = $qrInfo;
            }
        }
    }
}

// Suppression sécurisée des données
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_code"])) {
    if ($_POST["delete_code"] === "1414") {
        if (isset($_POST["delete_type"])) {
            switch ($_POST["delete_type"]) {
                case "attendance":
                    if (file_exists($attendanceFile)) {
                        file_put_contents($attendanceFile, ""); // Efface le fichier des présences
                    }
                    echo "<script>alert('Toutes les données de présence ont été supprimées avec succès.'); window.location.href='view.php?tab=attendances';</script>";
                    break;
                case "database":
                    if (file_exists($dataFile)) {
                        file_put_contents($dataFile, ""); // Efface le fichier de la base de données
                    }
                    echo "<script>alert('Toutes les inscriptions ont été supprimées avec succès.'); window.location.href='view.php?tab=database';</script>";
                    break;
                case "qrcodes":
                    // Supprimer tous les fichiers QR
                    if (file_exists($qrCodesDir) && is_dir($qrCodesDir)) {
                        $qrFiles = scandir($qrCodesDir);
                        foreach ($qrFiles as $file) {
                            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                                unlink($qrCodesDir . '/' . $file);
                            }
                        }
                    }
                    echo "<script>alert('Tous les QR codes ont été supprimés avec succès.'); window.location.href='view.php?tab=qrcodes';</script>";
                    break;
                case "all":
                    // Supprimer toutes les données
                    if (file_exists($dataFile)) {
                        file_put_contents($dataFile, "");
                    }
                    if (file_exists($attendanceFile)) {
                        file_put_contents($attendanceFile, "");
                    }
                    if (file_exists($qrCodesDir) && is_dir($qrCodesDir)) {
                        $qrFiles = scandir($qrCodesDir);
                        foreach ($qrFiles as $file) {
                            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                                unlink($qrCodesDir . '/' . $file);
                            }
                        }
                    }
                    echo "<script>alert('Toutes les données ont été supprimées avec succès.'); window.location.href='view.php';</script>";
                    break;
            }
        }
        exit();
    } else {
        echo "<script>alert('Code incorrect. Suppression annulée.');</script>";
    }
}

// Définir l'onglet actif
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'database';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des participants</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        table {
            width: 90%;
            margin: auto;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #ffcc00;
            color: #333;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        .button-group {
            margin-top: 20px;
        }
        .button-group button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            border-radius: 5px;
            margin: 5px;
            font-size: 14px;
        }
        .button-group button:hover {
            background: #0056b3;
        }
        .logout {
            background: red;
            color: white;
            padding: 10px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .delete-form {
            margin-top: 20px;
        }
        .delete-form input {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .delete-button {
            background: #d32f2f;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .delete-button:hover {
            background: #a52828;
        }
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .tab {
            padding: 10px 20px;
            background: #eee;
            border: none;
            cursor: pointer;
            margin: 5px;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
        }
        .tab.active {
            background: #ffcc00;
            font-weight: bold;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .filter-container {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-container select {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .highlight {
            background-color: #fffacd !important;
        }
        .time-badge {
            background-color: #ff9800;
            color: white;
            font-size: 12px;
            padding: 2px 5px;
            border-radius: 3px;
            margin-left: 5px;
        }
        .event-badge {
            background-color: #3f51b5;
            color: white;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            margin-bottom: 10px;
            display: inline-block;
        }
        .event-selector {
            margin-bottom: 20px;
        }
        .event-card {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin: 15px auto;
            width: 80%;
            max-width: 600px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: left;
        }
        .event-card h3 {
            margin-top: 0;
            color: #d32f2f;
        }
        .event-card p {
            margin: 5px 0;
        }
        .event-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active {
            background-color: #4CAF50;
            color: white;
        }
        .status-completed {
            background-color: #9E9E9E;
            color: white;
        }
        .add-event-form {
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
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .qr-code-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        .qr-code-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            width: 220px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .qr-code-card h4 {
            margin-top: 0;
            margin-bottom:margin-bottom: 10px;
            color: #333;
            text-align: center;
        }
        .qr-code-card p {
            margin: 5px 0;
            font-size: 14px;
        }
        .qr-code-display {
            margin: 10px 0;
        }
        .qr-code-actions {
            margin-top: 10px;
        }
        .section-title {
            margin-top: 30px;
            color: #d32f2f;
            border-bottom: 2px solid #ffcc00;
            padding-bottom: 10px;
            display: inline-block;
        }
        .signature-img {
            max-width: 100px;
            max-height: 80px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        /* Styles pour la section Analytique */
        .kpi-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin: 20px 0 30px;
        }

        .kpi-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 200px;
            display: flex;
            align-items: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .kpi-icon {
            background-color: #f0f0f0;
            color: #555;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
        }

        .kpi-card:nth-child(1) .kpi-icon {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .kpi-card:nth-child(2) .kpi-icon {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .kpi-card:nth-child(3) .kpi-icon {
            background-color: #fff8e1;
            color: #f57c00;
        }

        .kpi-card:nth-child(4) .kpi-icon {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .kpi-content {
            flex: 1;
        }

        .kpi-title {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        .kpi-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .analytics-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .wide-card {
            grid-column: span 2;
        }

        .chart-container {
            height: 250px;
            position: relative;
        }

        .analytics-controls {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .date-filter select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            font-size: 14px;
        }

        .analytics-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .analytics-table th, 
        .analytics-table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }

        .analytics-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .analytics-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .analytics-table tr:hover {
            background-color: #f0f0f0;
        }
        
        /* Styles spécifiques pour la carte d'insights */
        .insights-card {
            background: linear-gradient(135deg, #4527a0 0%, #673ab7 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
            box-shadow: 0 8px 25px rgba(103, 58, 183, 0.3);
        }
        
        .insights-card h4 {
            color: white;
            margin-top: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .powered-by {
            font-size: 12px;
            opacity: 0.7;
            font-weight: normal;
        }
        
        .insights-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        
        .insight-item {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            padding: 15px;
            flex: 1;
            min-width: 250px;
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .insight-number {
            background: white;
            color: #673ab7;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .insight-text {
            font-size: 15px;
            line-height: 1.4;
        }
        
        /* Progress bar styles */
        .progress-bar-container {
            width: 100%;
            background-color: #f0f0f0;
            border-radius: 4px;
            height: 18px;
            position: relative;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background-color: #4CAF50;
            border-radius: 4px;
        }
        
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 12px;
            color: #333;
        }
        
        .export-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            float: right;
        }
        
        .export-btn:hover {
            background-color: #0056b3;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .text-center {
            text-align: center;
        }

        @media (max-width: 768px) {
            .kpi-container {
                flex-direction: column;
                align-items: center;
            }
            
            .analytics-grid {
                grid-template-columns: 1fr;
            }
            
            .wide-card {
                grid-column: 1;
            }
            
            .insights-container {
                flex-direction: column;
            }
            
            .insight-item {
                min-width: auto;
            }
        }

        .btn-edit {
            background-color: #2196F3;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn-delete {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
        }

        .btn-edit:hover {
            background-color: #0b7dda;
        }

        .btn-delete:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>

<img src="https://www.enabel.be/app/uploads/2023/04/Enabel_Logo_Color_RGB.png" alt="Enabel Logo" class="logo">
<h2>Gestion des Participants</h2>

<!-- Ajoutez cet onglet dans la section des onglets (tabs) -->
<div class="tabs">
    <a class="tab <?php echo $activeTab === 'database' ? 'active' : ''; ?>" href="?tab=database">Base de données</a>
    <a class="tab <?php echo $activeTab === 'qrcodes' ? 'active' : ''; ?>" href="?tab=qrcodes">QR Codes</a>
    <a class="tab <?php echo $activeTab === 'attendances' ? 'active' : ''; ?>" href="?tab=attendances">Présences</a>
    <a class="tab <?php echo $activeTab === 'events' ? 'active' : ''; ?>" href="?tab=events">Événements</a>
    <a class="tab <?php echo $activeTab === 'analytics' ? 'active' : ''; ?>" href="?tab=analytics">Analytique</a>
</div>

<!-- PARTIE 1: BASE DE DONNÉES -->
<div id="database" class="tab-content <?php echo $activeTab === 'database' ? 'active' : ''; ?>">
    <h3 class="section-title">Base de données des inscrits</h3>
    
    <?php if (count($events) > 0) : ?>
    <div class="event-selector">
        <form method="GET">
            <input type="hidden" name="tab" value="database">
            <label for="event-select">Filtrer par événement :</label>
            <select id="event-select" name="event" onchange="this.form.submit()">
                <?php foreach ($events as $event) : ?>
                    <option value="<?php echo $event[0]; ?>" <?php echo $selectedEvent === $event[0] ? 'selected' : ''; ?>>
                        <?php echo $event[1]; ?> (<?php echo $event[2]; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($rows)) : ?>
        <div class="event-badge">
            <?php 
                $currentEvent = isset($eventMap[$selectedEvent]) ? $eventMap[$selectedEvent] : 'Événement inconnu';
                echo $currentEvent; 
            ?>
        </div>
        
        <table id="databaseTable">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Organisme</th>
                    <th>Fonction</th>
                    <th>Téléphone</th>
                    <th>Email</th>
                    <th>Âge</th>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Genre</th>
                    <th>Signature</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Afficher tous les participants sans filtrage par événement
                $filteredRows = $rows; // Utiliser tous les participants sans filtrage

                foreach ($filteredRows as $index => $row) : 
                ?>
<tr>
    <td><?php echo htmlspecialchars($row[0]); ?></td>
    <td><?php echo htmlspecialchars($row[1]); ?></td>
    <td><?php echo htmlspecialchars($row[2]); ?></td>
    <td><?php echo htmlspecialchars($row[3]); ?></td>
    <td><?php echo htmlspecialchars($row[4]); ?></td>
    <td><?php echo htmlspecialchars($row[5]); ?></td>
    <td><?php echo htmlspecialchars($row[6]); ?></td>
    <td><?php echo htmlspecialchars($row[7]); ?></td>
    <td><?php echo htmlspecialchars($row[8]); ?></td>
    <td><?php echo isset($row[12]) ? ($row[12] === "H" ? "Homme" : ($row[12] === "F" ? "Femme" : "")) : ""; ?></td>
    <td><img src="<?php echo htmlspecialchars($row[9]); ?>" alt="Signature" class="signature-img"></td>
</tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="button-group">
            <button onclick="exportPDF('database')">📄 Exporter en PDF</button>
        </div>

        <form class="delete-form" method="POST">
            <input type="password" name="delete_code" placeholder="Code d'accès" required>
            <input type="hidden" name="delete_type" value="database">
            <button type="submit" class="delete-button">🗑️ Effacer la base de données</button>
        </form>

    <?php else : ?>
        <p>Aucune inscription enregistrée.</p>
    <?php endif; ?>

<button onclick="window.location.href='import_contacts.php'" style="background: #4CAF50;">📥 Importer des Contacts</button>
</div>

<!-- PARTIE 2: QR CODES -->
<div id="qrcodes" class="tab-content <?php echo $activeTab === 'qrcodes' ? 'active' : ''; ?>">
    <h3 class="section-title">Stockage des QR Codes</h3>
    
    <?php if (!empty($qrCodes)) : ?>
        <p>Chaque participant dispose d'un QR code unique qu'il peut scanner pour marquer sa présence</p>
        
        <div class="qr-code-container">
            <?php foreach ($qrCodes as $participantId => $qrInfo) : ?>
                <div class="qr-code-card">
                    <h4><?php echo htmlspecialchars($qrInfo['prenom'] . ' ' . $qrInfo['nom']); ?></h4>
                    <p><?php echo htmlspecialchars($qrInfo['email']); ?></p>
                    <div class="qr-code-display" id="qr-<?php echo $participantId; ?>"></div>
                    <div class="qr-code-actions">
                        <a href="view_participant_qr.php? <a href="view_participant_qr.php?id=<?php echo $participantId; ?>" target="_blank">
                            <button>Voir</button>
                        </a>
                    </div>
                </div>
                <script>
                    new QRCode(document.getElementById("qr-<?php echo $participantId; ?>"), {
                        text: '<?php echo json_encode($qrInfo); ?>',
                        width: 100,
                        height: 100
                    });
                </script>
            <?php endforeach; ?>
        </div>

        <div class="button-group">
            <button onclick="window.location.href='download_all_qr.php'">📥 Télécharger tous les QR Codes</button>
        </div>

        <form class="delete-form" method="POST">
            <input type="password" name="delete_code" placeholder="Code d'accès" required>
            <input type="hidden" name="delete_type" value="qrcodes">
            <button type="submit" class="delete-button">🗑️ Effacer tous les QR Codes</button>
        </form>
    <?php else : ?>
        <p>Aucun QR code enregistré.</p>
    <?php endif; ?>
</div>

<!-- PARTIE 3: PRÉSENCES -->
<div id="attendances" class="tab-content <?php echo $activeTab === 'attendances' ? 'active' : ''; ?>">
    <h3 class="section-title">Liste des Présences</h3>
    
    <?php if (count($events) > 0) : ?>
    <div class="filter-container">
        <form method="GET">
            <input type="hidden" name="tab" value="attendances">
            <label for="event-select-att">Événement :</label>
            <select id="event-select-att" name="event" onchange="this.form.submit()">
                <?php foreach ($events as $event) : ?>
                    <option value="<?php echo $event[0]; ?>" <?php echo $selectedEvent === $event[0] ? 'selected' : ''; ?>>
                        <?php echo $event[1]; ?> (<?php echo $event[2]; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        
        <?php if (!empty($uniqueDates)) : ?>
        <form method="GET">
            <input type="hidden" name="tab" value="attendances">
            <input type="hidden" name="event" value="<?php echo $selectedEvent; ?>">
            <label for="date">Date :</label>
            <select name="date" id="date" onchange="this.form.submit()">
                <?php foreach ($uniqueDates as $date) : ?>
                    <option value="<?php echo $date; ?>" <?php echo ($date === $selectedDate) ? 'selected' : ''; ?>>
                        <?php echo $date; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="event-badge">
        <?php 
            $currentEvent = isset($eventMap[$selectedEvent]) ? $eventMap[$selectedEvent] : 'Événement inconnu';
            echo $currentEvent . ' - ' . $selectedDate; 
        ?>
    </div>
    
    <?php if (!empty($dateFilteredAttendance)) : ?>
        <table id="attendanceTable">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Organisme</th>
                    <th>Fonction</th>
                    <th>Email</th>
                    <th>Heure</th>
                    <th>Statut</th>
                    <th>Genre</th>
                    <th>Signature</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Tracker pour les participants uniques
                $uniqueParticipants = [];
                $participantStatus = [];
                
                // Premier passage pour enregistrer le dernier statut de chaque participant
                foreach ($dateFilteredAttendance as $row) {
                    $email = $row[0];
                    $time = $row[2];
                    
                    if (!isset($participantStatus[$email]) || $time > $participantStatus[$email]['time']) {
                        $participantStatus[$email] = [
                            'time' => $time,
                            'row' => $row
                        ];
                    }
                }
                
                // Récupérer les signatures des participants
                $participantSignatures = [];
                foreach ($rows as $row) {
                    if (isset($row[5])) { // Email à l'index 5
                        $participantSignatures[$row[5]] = isset($row[9]) ? $row[9] : null; // Signature à l'index 9
                    }
                }
                
                // Afficher les participants avec leur dernier statut
                foreach ($participantStatus as $email => $data) : 
                    $row = $data['row'];
                    $signature = isset($participantSignatures[$email]) ? $participantSignatures[$email] : null;
                    $genre = isset($row[8]) ? ($row[8] === 'H' ? 'Homme' : ($row[8] === 'F' ? 'Femme' : 'Non spécifié')) : 'Non spécifié';
                ?>
                    <tr class="highlight">
                        <td><?php echo htmlspecialchars($row[3]); ?></td>
                        <td><?php echo htmlspecialchars($row[4]); ?></td>
                        <td><?php echo htmlspecialchars($row[5]); ?></td>
                        <td><?php echo htmlspecialchars($row[6]); ?></td>
                        <td><?php echo htmlspecialchars($row[0]); ?></td>
                        <td><?php echo htmlspecialchars($row[2]); ?></td>
                        <td>Présent</td>
                        <td><?php echo $genre; ?></td>
                        <td>
                            <?php if ($signature): ?>
                                <img src="<?php echo htmlspecialchars($signature); ?>" alt="Signature" class="signature-img">
                            <?php else: ?>
                                <span>Non disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="button-group">
            <button onclick="exportPDF('attendance')">📄 Exporter en PDF</button>
        </div>
    <?php else : ?>
        <p>Aucune présence enregistrée pour cette date.</p>
    <?php endif; ?>
    
    <form class="delete-form" method="POST">
        <input type="password" name="delete_code" placeholder="Code d'accès" required>
        <input type="hidden" name="delete_type" value="attendance">
        <button type="submit" class="delete-button">🗑️ Effacer les présences</button>
    </form>
</div>

<!-- PARTIE 4: ÉVÉNEMENTS -->
<div id="events" class="tab-content <?php echo $activeTab === 'events' ? 'active' : ''; ?>">
    <h3 class="section-title">Gestion des Événements</h3>

    <div class="add-event-form">
        <h4>Ajouter un nouvel événement</h4>
        <form action="addevent.php" method="POST">
            <div class="form-group">
                <label for="event-name">Nom de l'événement</label>
                <input type="text" id="event-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="event-date">Date</label>
                <input type="date" id="event-date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-group">
                <label for="event-location">Lieu</label>
                <input type="text" id="event-location" name="location" required>
            </div>
            <button type="submit">Ajouter</button>
        </form>
    </div>

    <h4>Liste des événements</h4>
    <?php if (!empty($events)) : ?>
        <?php foreach ($events as $event) : ?>
            <div class="event-card">
                <h3><?php echo htmlspecialchars($event[1]); ?></h3>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($event[2]); ?></p>
                <p><strong>Lieu:</strong> <?php echo htmlspecialchars($event[3]); ?></p>
                <p>
                    <span class="event-status <?php echo $event[4] === 'En cours' ? 'status-active' : 'status-completed'; ?>">
                        <?php echo htmlspecialchars($event[4]); ?>
                    </span>
                </p>
                <div class="button-group">
                    <a href="?tab=attendances&event=<?php echo $event[0]; ?>">
                        <button>Voir les présences</button>
                    </a>
                    <a href="?tab=database&event=<?php echo $event[0]; ?>">
                        <button>Voir les inscrits</button>
                    </a>
                    <form method="POST" action="update_event_status.php" style="display: inline;">
                        <input type="hidden" name="event_id" value="<?php echo $event[0]; ?>">
                        <input type="hidden" name="status" value="<?php echo $event[4] === 'En cours' ? 'Terminé' : 'En cours'; ?>">
                        <button type="submit"><?php echo $event[4] === 'En cours' ? 'Terminer' : 'Réactiver'; ?></button>
                    </form>
                    <!-- Nouveaux boutons pour éditer et supprimer -->
                    <a href="editevent.php?id=<?php echo $event[0]; ?>">
                        <button type="button" class="btn-edit">Modifier</button>
                    </a>
                    <a href="deleteevent.php?id=<?php echo $event[0]; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement?')">
                        <button type="button" class="btn-delete">Supprimer</button>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p>Aucun événement enregistré.</p>
    <?php endif; ?>
</div>

<!-- PARTIE 5: ANALYTIQUE -->
<div id="analytics" class="tab-content <?php echo $activeTab === 'analytics' ? 'active' : ''; ?>">
    <h3 class="section-title">Tableau de Bord Analytique</h3>
    
    <?php
    // Inclure le fichier analytics.php
    require_once 'analytics.php';
    
    // Récupérer toutes les statistiques
    $allStats = getAllStats();
    
    // Intégration avec l'API DeepSeek
    function getDeepSeekInsights($data) {
        $apiKey = 'sk-8de4b19f1e9d481cb304de70c5bcc121';
        $apiUrl = 'https://api.deepseek.com/v1/chat/completions';
        
        // Préparer les données à analyser
        $participantsData = isset($data['participants']) ? $data['participants'] : [];
        $attendancesData = isset($data['attendances']) ? $data['attendances'] : [];
        $eventsData = isset($data['events']) ? $data['events'] : [];
        
        // Préparer le prompt pour l'API DeepSeek
        $prompt = "Analyse les données suivantes et fournit trois insights importants, pertinents et concis (max 30 mots chacun).
        Données sur les participants: " . json_encode($participantsData) . "
        Données sur les présences: " . json_encode($attendancesData) . "
        Données sur les événements: " . json_encode($eventsData);
        
        // Préparer la requête API
        $postData = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un analyste de données expert. Analyse les données fournies et donne 3 insights pertinents, courts et utiles.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 300
        ];
        
        // Initialiser cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        
        // Exécuter la requête
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            // En cas d'erreur, retourner des insights par défaut
            return [
                "Le taux de participation moyen aux événements est de " . $data['attendances']['participation_rate'] . "%.",
                "L'organisme le plus représenté est " . array_key_first($data['participants']['organismes']) . ".",
                "La tranche d'âge dominante est " . array_search(max($data['participants']['ages']), $data['participants']['ages']) . "."
            ];
        }
        
        // Décoder la réponse
        $result = json_decode($response, true);
        
        // Extraire les insights
        $insights = [];
        if (isset($result['choices'][0]['message']['content'])) {
            $content = $result['choices'][0]['message']['content'];
            
            // Extraire les insights du texte de réponse
            preg_match_all('/\d+\.\s+(.*?)(?=\d+\.|$)/s', $content, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $insight) {
                    $insights[] = trim($insight);
                    if (count($insights) >= 3) break;
                }
            }
            
            // Si les insights sont vides ou insuffisants, utiliser des valeurs par défaut
            while (count($insights) < 3) {
                if (count($insights) == 0) {
                    $insights[] = "Le taux de participation moyen aux événements est de " . $data['attendances']['participation_rate'] . "%.";
                } else if (count($insights) == 1) {
                    $topOrg = !empty($data['participants']['organismes']) ? array_key_first($data['participants']['organismes']) : 'N/A';
                    $insights[] = "L'organisme le plus représenté est " . $topOrg . ".";
                } else {
                    $topAge = !empty($data['participants']['ages']) ? array_search(max($data['participants']['ages']), $data['participants']['ages']) : 'N/A';
                    $insights[] = "La tranche d'âge dominante est " . $topAge . ".";
                }
            }
        } else {
            // Insights par défaut si la réponse de l'API est invalide
            $insights = [
                "Le taux de participation moyen aux événements est de " . $data['attendances']['participation_rate'] . "%.",
                "L'organisme le plus représenté est " . array_key_first($data['participants']['organismes']) . ".",
                "La tranche d'âge dominante est " . array_search(max($data['participants']['ages']), $data['participants']['ages']) . "."
            ];
        }
        
        return $insights;
    }
    
    // Obtenir les insights de l'API DeepSeek
    $insights = getDeepSeekInsights($allStats);
    
    // KPIs principaux
    $totalParticipants = $allStats['participants']['total'];
    $totalEvents = $allStats['events']['total_events'];
    $totalAttendances = $allStats['attendances']['total_attendances'];
    $participationRate = $allStats['attendances']['participation_rate'];
    
    // Données pour les graphiques
    $ageDistribution = json_encode($allStats['participants']['ages']);
    $attendancesByDate = json_encode($allStats['attendances']['attendances_by_date']);
    $eventParticipation = json_encode($allStats['events']['event_participation']);
    $timeSlots = json_encode($allStats['attendances']['time_slots']);
    $organismes = json_encode($allStats['participants']['organismes']);
    $genderDistribution = json_encode($allStats['participants']['genres']);
    
    // Trouver l'événement le plus populaire
    $mostPopularEvent = 'Aucun';
    $maxAttendees = 0;
    foreach ($allStats['events']['event_participation'] as $event => $count) {
        if ($count > $maxAttendees) {
            $maxAttendees = $count;
            $mostPopularEvent = $event;
        }
    }
    ?>
    
    <!-- Cartes KPI -->
    <div class="kpi-container">
        <div class="kpi-card">
            <div class="kpi-icon">👥</div>
            <div class="kpi-content">
                <div class="kpi-title">Total Participants</div>
                <div class="kpi-value"><?php echo $totalParticipants; ?></div>
            </div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-icon">📅</div>
            <div class="kpi-content">
                <div class="kpi-title">Événements</div>
                <div class="kpi-value"><?php echo $totalEvents; ?></div>
            </div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-icon">✓</div>
            <div class="kpi-content">
                <div class="kpi-title">Présences</div>
                <div class="kpi-value"><?php echo $totalAttendances; ?></div>
            </div>
        </div>
        
        <div class="kpi-card">
            <div class="kpi-icon">📊</div>
            <div class="kpi-content">
                <div class="kpi-title">Taux Participation</div>
                <div class="kpi-value"><?php echo $participationRate; ?>%</div>
            </div>
        </div>
    </div>
    
    <!-- Carte d'insights DeepSeek -->
    <div class="insights-card">
        <h4>Insights Analytiques <span class="powered-by">Propulsé par DeepSeek AI</span></h4>
        <div class="insights-container">
            <?php foreach ($insights as $index => $insight): ?>
                <div class="insight-item">
                    <div class="insight-number"><?php echo $index + 1; ?></div>
                    <div class="insight-text"><?php echo htmlspecialchars($insight); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Grille de graphiques et tableaux -->
    <div class="analytics-grid">
        <!-- Graphique 1: Répartition par âge -->
        <div class="analytics-card">
            <h4>Répartition par Âge</h4>
            <div class="chart-container">
                <canvas id="ageChart"></canvas>
            </div>
        </div>
        
        <!-- Graphique 2: Présences par date -->
        <div class="analytics-card">
            <h4>Évolution des Présences</h4>
            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
        
        <!-- Graphique 3: Participation par événement -->
        <div class="analytics-card wide-card">
            <h4>Participation par Événement</h4>
            <div class="chart-container">
                <canvas id="eventChart"></canvas>
            </div>
        </div>
        
        <!-- Graphique 4: Répartition par genre -->
        <div class="analytics-card">
            <h4>Répartition par Genre</h4>
            <div class="chart-container">
                <canvas id="genderChart"></canvas>
            </div>
        </div>
        
        <!-- Tableau de résumé des chiffres clés -->
        <div class="analytics-card wide-card">
            <h4>Résumé des Chiffres Clés</h4>
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Métrique</th>
                        <th>Valeur</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Total des participants inscrits</td>
                        <td><strong><?php echo $totalParticipants; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Total des événements</td>
                        <td><strong><?php echo $totalEvents; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Total des présences enregistrées</td>
                        <td><strong><?php echo $totalAttendances; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Taux de participation moyen</td>
                        <td><strong><?php echo $participationRate; ?>%</strong></td>
                    </tr>
                    <tr>
                        <td>Événement le plus populaire</td>
                        <td><strong><?php echo htmlspecialchars($mostPopularEvent); ?> (<?php echo $maxAttendees; ?> participants)</strong></td>
                    </tr>
                    <tr>
                        <td>Tranche d'âge dominante</td>
                        <td><strong><?php echo array_search(max($allStats['participants']['ages']), $allStats['participants']['ages']); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Répartition horaire principale</td>
                        <td><strong><?php echo array_search(max($allStats['attendances']['time_slots']), $allStats['attendances']['time_slots']); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Répartition par genre</td>
                        <td><strong>Hommes: <?php echo $allStats['participants']['genres']['H']; ?>, Femmes: <?php echo $allStats['participants']['genres']['F']; ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Tableau récapitulatif des organismes -->
        <div class="analytics-card wide-card">
            <h4>Récapitulatif des Organismes <button class="export-btn" onclick="exportOrganismesPDF()">📄 Exporter PDF</button></h4>
            <div class="table-responsive">
                <table class="analytics-table" id="organismesTable">
                    <thead>
                        <tr>
                            <th>Organisme</th>
                            <th>Nombre de Participants</th>
                            <th>Pourcentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $organismes = $allStats['participants']['organismes'];
                        $totalOrganismes = array_sum($organismes);
                        $count = 0;
                        
                        foreach ($organismes as $organisme => $nombre) :
                            $pourcentage = ($totalOrganismes > 0) ? round(($nombre / $totalOrganismes) * 100, 1) : 0;
                            // Limiter à 10 organismes maximum, avec une ligne "Autres" pour le reste
                            if ($count < 10 || count($organismes) <= 10) :
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($organisme); ?></td>
                                <td><?php echo $nombre; ?></td>
                                <td>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?php echo $pourcentage; ?>%"></div>
                                        <span class="progress-text"><?php echo $pourcentage; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            $count++;
                            else :
                                // Si on a déjà affiché 10 organismes, on regroupe le reste
                                if ($count === 10) :
                                    $autresNombre = array_sum(array_slice($organismes, 10));
                                    $autresPourcentage = ($totalOrganismes > 0) ? round(($autresNombre / $totalOrganismes) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td>Autres organismes</td>
                                <td><?php echo $autresNombre; ?></td>
                                <td>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width: <?php echo $autresPourcentage; ?>%"></div>
                                        <span class="progress-text"><?php echo $autresPourcentage; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                                    $count++;
                                endif;
                            endif;
                        endforeach; 
                        
                        // Si aucun organisme n'est présent
                        if (empty($organismes)) :
                        ?>
                            <tr>
                                <td colspan="3" class="text-center">Aucun organisme enregistré</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    // Fonction pour exporter le tableau des organismes en PDF
    function exportOrganismesPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        doc.text("Récapitulatif des Organismes", 14, 15);
        doc.autoTable({
            html: "#organismesTable",
            startY: 25,
            theme: "striped",
            styles: { fontSize: 10 },
            columnStyles: {
                0: { cellWidth: 'auto' },
                1: { cellWidth: 40, halign: 'center' },
                2: { cellWidth: 40, halign: 'center' }
            },
            // Formatter pour ne pas inclure la barre de progression dans le PDF
            didParseCell: function(data) {
                if (data.column.index === 2) {
                    // Pour la colonne pourcentage, on récupère juste la valeur
                    const cell = data.cell.raw;
                    if (cell && cell.textContent) {
                        const percentage = cell.textContent.trim();
                        data.cell.text = percentage;
                    }
                }
            }
        });
        doc.save("Recapitulatif_Organismes.pdf");
    }
    
    // Configuration des graphiques
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier si l'onglet analytique est actif
        if (!document.getElementById('analytics').classList.contains('active')) {
            return;
        }
        
        // Configuration des couleurs
        const colors = {
            blue: '#1976d2',
            green: '#388e3c',
            orange: '#f57c00',
            purple: '#7b1fa2',
            red: '#d32f2f',
            background: {
                blue: 'rgba(25, 118, 210, 0.1)',
                green: 'rgba(56, 142, 60, 0.1)',
                orange: 'rgba(245, 124, 0, 0.1)',
                purple: 'rgba(123, 31, 162, 0.1)',
                red: 'rgba(211, 47, 47, 0.1)'
            }
        };
        
        // Graphique 1: Répartition par âge (Doughnut chart)
        const ageData = <?php echo $ageDistribution; ?>;
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        new Chart(ageCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(ageData),
                datasets: [{
                    data: Object.values(ageData),
                    backgroundColor: [
                        colors.blue,
                        colors.green,
                        colors.orange,
                        colors.purple,
                        colors.red
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // Graphique 2: Présences par date (Line chart)
        const attendanceData = <?php echo $attendancesByDate; ?>;
        const attendanceDates = Object.keys(attendanceData);
        const attendanceCounts = Object.values(attendanceData);
        
        const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
        new Chart(attendanceCtx, {
            type: 'line',
            data: {
                labels: attendanceDates,
                datasets: [{
                    label: 'Nombre de présences',
                    data: attendanceCounts,
                    backgroundColor: colors.background.blue,
                    borderColor: colors.blue,
                    borderWidth: borderWidth: 2,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: colors.blue
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Graphique 3: Participation par événement (Bar chart)
        const eventData = <?php echo $eventParticipation; ?>;
        const eventNames = Object.keys(eventData);
        const eventCounts = Object.values(eventData);
        
        const eventCtx = document.getElementById('eventChart').getContext('2d');
        new Chart(eventCtx, {
            type: 'bar',
            data: {
                labels: eventNames,
                datasets: [{
                    label: 'Nombre de participants',
                    data: eventCounts,
                    backgroundColor: colors.orange,
                    borderColor: colors.orange,
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false,
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Graphique 4: Répartition par genre (Pie chart)
        const genderData = <?php echo $genderDistribution; ?>;
        const genderCtx = document.getElementById('genderChart').getContext('2d');
        new Chart(genderCtx, {
            type: 'pie',
            data: {
                labels: Object.keys(genderData),
                datasets: [{
                    data: Object.values(genderData),
                    backgroundColor: [
                        colors.blue,
                        colors.purple,
                        colors.green
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 15,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
</div>

<form method="POST" action="logout.php">
    <button class="logout" type="submit">🔒 Déconnexion</button>
</form>

<script>
    // PDF Export Functions
    function exportPDF(type) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        if (type === 'database') {
            doc.text("Base de données des participants", 14, 15);
            doc.autoTable({
                html: "#databaseTable",
                startY: 25,
                theme: "striped",
                styles: { fontSize: 8 },
                columnStyles: {
                    9: { cellWidth: 0 } // Hide signature column
                }
            });
            doc.save("Base_Donnees_Participants.pdf");
        } else if (type === 'attendance') {
            doc.text("Liste des Présences", 14, 15);
            doc.autoTable({
                html: "#attendanceTable",
                startY: 25,
                theme: "striped",
                styles: { fontSize: 10 },
                columnStyles: {
                    8: { cellWidth: 0 } // Hide signature column
                }
            });
            doc.save("Liste_Presences.pdf");
        }
    }
    
    // Générer des QR codes pour la page des QR codes
    document.addEventListener('DOMContentLoaded', function() {
        // S'assurer que tous les QR codes sont générés
        if (document.getElementById('qrcodes').classList.contains('active')) {
            const qrElements = document.querySelectorAll('.qr-code-display');
            if (qrElements.length > 0) {
                console.log('QR codes générés avec succès.');
            }
        }
    });
</script>

</body>
</html>