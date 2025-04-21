<?php
/**
 * deepseek_api.php
 * Script pour gérer les communications avec l'API DeepSeek
 */

// Activer les erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Headers pour CORS et type de contenu
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Clé API DeepSeek (dans un environnement de production, utilisez des variables d'environnement)
$api_key = 'sk-8de4b19f1e9d481cb304de70c5bcc121';

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Méthode non autorisée. Utilisez POST.']);
    exit;
}

// Récupérer le corps de la requête
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// Vérifier si les données sont valides
if (!$data || !isset($data['message']) || empty($data['message'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Données invalides. Le message est requis.']);
    exit;
}

// Récupérer les données contextuelles pour l'IA
function getContextData() {
    $context = [];
    
    // Participants
    $dataFile = "database/data.csv";
    if (file_exists($dataFile)) {
        $participants = [];
        $file = fopen($dataFile, "r");
        while (($data = fgetcsv($file)) !== false) {
            $participants[] = $data;
        }
        fclose($file);
        
        $context['participants'] = [
            'count' => count($participants),
            'sample' => array_slice($participants, 0, 5) // Envoyer seulement un échantillon
        ];
    }
    
    // Présences
    $attendanceFile = "presence/attendance.csv";
    if (file_exists($attendanceFile)) {
        $attendances = [];
        $file = fopen($attendanceFile, "r");
        while (($data = fgetcsv($file)) !== false) {
            $attendances[] = $data;
        }
        fclose($file);
        
        $context['attendances'] = [
            'count' => count($attendances),
            'sample' => array_slice($attendances, 0, 5)
        ];
    }
    
    // Événements
    $eventsFile = "events.csv";
    if (file_exists($eventsFile)) {
        $events = [];
        $file = fopen($eventsFile, "r");
        while (($data = fgetcsv($file)) !== false) {
            $events[] = $data;
        }
        fclose($file);
        
        $context['events'] = [
            'count' => count($events),
            'list' => $events
        ];
    }
    
    return $context;
}

// Récupérer le contexte des données
$contextData = getContextData();

// Préparer les messages pour l'API
$userMessage = $data['message'];
$conversationHistory = isset($data['history']) ? $data['history'] : [];

// Créer le message système avec le contexte
$systemMessage = "Tu es un assistant spécialisé dans l'analyse de données d'événements pour le système de gestion des participants d'Enabel. ";
$systemMessage .= "Tu aides à interpréter les données et à fournir des insights. ";

// Ajouter le contexte des données au message système
if (!empty($contextData)) {
    $systemMessage .= "Voici un résumé des données disponibles:\n\n";
    
    if (isset($contextData['participants'])) {
        $systemMessage .= "PARTICIPANTS: " . $contextData['participants']['count'] . " participants enregistrés.\n";
    } else {
        $systemMessage .= "PARTICIPANTS: Aucune donnée disponible.\n";
    }
    
    if (isset($contextData['attendances'])) {
        $systemMessage .= "PRÉSENCES: " . $contextData['attendances']['count'] . " présences enregistrées.\n";
    } else {
        $systemMessage .= "PRÉSENCES: Aucune donnée disponible.\n";
    }
    
    if (isset($contextData['events'])) {
        $systemMessage .= "ÉVÉNEMENTS: " . $contextData['events']['count'] . " événements enregistrés.\n";
        if (!empty($contextData['events']['list'])) {
            $systemMessage .= "Liste des événements:\n";
            foreach ($contextData['events']['list'] as $event) {
                $systemMessage .= "- " . $event[1] . " (" . $event[2] . ", " . $event[3] . ")\n";
            }
        }
    } else {
        $systemMessage .= "ÉVÉNEMENTS: Aucune donnée disponible.\n";
    }
}

// Préparer la requête pour l'API DeepSeek
$messages = [
    [
        'role' => 'system',
        'content' => $systemMessage
    ]
];

// Ajouter l'historique de conversation
foreach ($conversationHistory as $message) {
    $messages[] = $message;
}

// Ajouter le message de l'utilisateur
$messages[] = [
    'role' => 'user',
    'content' => $userMessage
];

$request_data = [
    'model' => 'deepseek-chat',
    'messages' => $messages,
    'max_tokens' => 1000,
    'temperature' => 0.7
];

// Envoyer la requête à l'API DeepSeek
$ch = curl_init('https://api.deepseek.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Gérer les erreurs de cURL
if ($response === false) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur de communication avec l\'API DeepSeek',
        'details' => $curl_error
    ]);
    exit;
}

// Gérer les erreurs de l'API
if ($http_code !== 200) {
    http_response_code($http_code);
    echo $response; // Passer l'erreur de l'API directement
    exit;
}

// Retourner la réponse
echo $response;
?>