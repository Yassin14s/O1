<?php
// Script de test pour vérifier quels événements sont actifs
// Placez ce fichier à la racine du site et accédez-y pour déboguer

require_once 'events.php';

// Désactiver le cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

echo "<h1>Test des événements actifs</h1>";

// Récupérer tous les événements
$events = getEvents();

echo "<h2>Liste complète des événements</h2>";
echo "<pre>";
print_r($events);
echo "</pre>";

// Filtrer les événements actifs
$activeEvents = [];
foreach ($events as $event) {
    if ($event[4] === "En cours") {
        $activeEvents[] = $event;
    }
}

echo "<h2>Événements actifs uniquement</h2>";
echo "<pre>";
print_r($activeEvents);
echo "</pre>";

echo "<h2>Test de l'appel get_events.php?active=1</h2>";
$url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/get_events.php?active=1";
echo "URL testée: " . $url . "<br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
curl_close($ch);

echo "<pre>";
echo htmlspecialchars($response);
echo "</pre>";

// Interpréter le résultat
$jsonData = json_decode($response, true);
if ($jsonData) {
    echo "<h3>Événements retournés par l'API</h3>";
    echo "<ul>";
    foreach ($jsonData as $event) {
        echo "<li>ID: " . $event['id'] . " | Nom: " . $event['name'] . " | Statut: " . $event['status'] . "</li>";
    }
    echo "</ul>";
    
    // Vérifier si des événements non actifs ont été renvoyés
    $nonActiveEvents = array_filter($jsonData, function($event) {
        return $event['status'] !== "En cours";
    });
    
    if (count($nonActiveEvents) > 0) {
        echo "<h3 style='color:red'>PROBLÈME: Des événements non actifs ont été renvoyés!</h3>";
        echo "<ul>";
        foreach ($nonActiveEvents as $event) {
            echo "<li style='color:red'>ID: " . $event['id'] . " | Nom: " . $event['name'] . " | Statut: " . $event['status'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<h3 style='color:green'>OK: Seuls les événements actifs ont été renvoyés</h3>";
    }
} else {
    echo "<h3 style='color:red'>ERREUR: Impossible d'interpréter la réponse JSON</h3>";
}
?>