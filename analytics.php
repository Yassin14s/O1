<?php
/**
 * analytics.php
 * Ce script analyse les données des participants, présences et événements
 * pour générer des statistiques et visualisations
 */

/**
 * Récupère les statistiques sur les participants
 * @return array Tableau associatif de statistiques
 */
function getParticipantStats() {
    $stats = [];
    $dataFile = "database/data.csv";
    $rows = [];
    
    if (file_exists($dataFile)) {
        $file = fopen($dataFile, "r");
        while (($data = fgetcsv($file)) !== false) {
            $rows[] = $data;
        }
        fclose($file);
    }
    
    // Nombre total de participants
    $stats['total'] = count($rows);
    
    // Répartition par organisme
    $organismes = [];
    foreach ($rows as $row) {
        $organisme = $row[2]; // Index 2 = organisme
        if (!isset($organismes[$organisme])) {
            $organismes[$organisme] = 1;
        } else {
            $organismes[$organisme]++;
        }
    }
    arsort($organismes); // Trier par ordre décroissant
    $stats['organismes'] = $organismes;
    
    // Répartition par fonction
    $fonctions = [];
    foreach ($rows as $row) {
        $fonction = $row[3]; // Index 3 = fonction
        if (!isset($fonctions[$fonction])) {
            $fonctions[$fonction] = 1;
        } else {
            $fonctions[$fonction]++;
        }
    }
    arsort($fonctions); // Trier par ordre décroissant
    $stats['fonctions'] = $fonctions;
    
    // Répartition par genre
    $genres = [
        'H' => 0,
        'F' => 0,
        'Non spécifié' => 0
    ];
    
    foreach ($rows as $row) {
        if (isset($row[12]) && $row[12] === 'H') {
            $genres['H']++;
        } elseif (isset($row[12]) && $row[12] === 'F') {
            $genres['F']++;
        } else {
            $genres['Non spécifié']++;
        }
    }
    $stats['genres'] = $genres;
    
    // Répartition par âge
    $ages = [];
    $ageGroups = [
        '18-25' => 0,
        '26-35' => 0,
        '36-45' => 0,
        '46-55' => 0,
        '56+' => 0
    ];
    
    foreach ($rows as $row) {
        if (isset($row[6]) && is_numeric($row[6])) {
            $age = intval($row[6]);
            
            if ($age >= 18 && $age <= 25) {
                $ageGroups['18-25']++;
            } elseif ($age >= 26 && $age <= 35) {
                $ageGroups['26-35']++;
            } elseif ($age >= 36 && $age <= 45) {
                $ageGroups['36-45']++;
            } elseif ($age >= 46 && $age <= 55) {
                $ageGroups['46-55']++;
            } elseif ($age >= 56) {
                $ageGroups['56+']++;
            }
        }
    }
    $stats['ages'] = $ageGroups;
    
    // Évolution des inscriptions dans le temps
    $inscriptionsDates = [];
    foreach ($rows as $row) {
        $date = isset($row[7]) ? $row[7] : 'Inconnue';
        if (!isset($inscriptionsDates[$date])) {
            $inscriptionsDates[$date] = 1;
        } else {
            $inscriptionsDates[$date]++;
        }
    }
    ksort($inscriptionsDates); // Trier par ordre chronologique
    $stats['inscriptions_dates'] = $inscriptionsDates;
    
    // Distribution par événement
    $evenements = [];
    foreach ($rows as $row) {
        $eventId = isset($row[10]) ? $row[10] : 'Inconnu';
        if (!isset($evenements[$eventId])) {
            $evenements[$eventId] = 1;
        } else {
            $evenements[$eventId]++;
        }
    }
    $stats['evenements'] = $evenements;
    
    return $stats;
}

/**
 * Récupère les statistiques sur les présences
 * @return array Tableau associatif de statistiques
 */
function getAttendanceStats() {
    $stats = [];
    $dataFile = "database/data.csv";
    $participants = [];
    
    if (file_exists($dataFile)) {
        $file = fopen($dataFile, "r");
        while (($data = fgetcsv($file)) !== false) {
            $participants[] = $data;
        }
        fclose($file);
    }
    
    $attendanceFile = "presence/attendance.csv";
    $attendances = [];
    
    if (file_exists($attendanceFile)) {
        $file = fopen($attendanceFile, "r");
        while (($data = fgetcsv($file)) !== false) {
            $attendances[] = $data;
        }
        fclose($file);
    }
    
    // Nombre total de présences
    $stats['total_attendances'] = count($attendances);
    
    // Taux de présence global
    $stats['participation_rate'] = ($stats['total_attendances'] > 0 && count($participants) > 0) ? 
        round(($stats['total_attendances'] / count($participants)) * 100, 2) : 0;
    
    // Présences par genre
    $attendancesByGender = [
        'H' => 0,
        'F' => 0,
        'Non spécifié' => 0
    ];
    
    foreach ($attendances as $attendance) {
        if (isset($attendance[8]) && $attendance[8] === 'H') {
            $attendancesByGender['H']++;
        } elseif (isset($attendance[8]) && $attendance[8] === 'F') {
            $attendancesByGender['F']++;
        } else {
            $attendancesByGender['Non spécifié']++;
        }
    }
    $stats['attendances_by_gender'] = $attendancesByGender;
    
    // Présences par événement
    $attendancesByEvent = [];
    foreach ($attendances as $attendance) {
        $eventId = isset($attendance[7]) ? $attendance[7] : 'Inconnu';
        if (!isset($attendancesByEvent[$eventId])) {
            $attendancesByEvent[$eventId] = 1;
        } else {
            $attendancesByEvent[$eventId]++;
        }
    }
    $stats['attendances_by_event'] = $attendancesByEvent;
    
    // Présences par date
    $attendancesByDate = [];
    foreach ($attendances as $attendance) {
        $date = isset($attendance[1]) ? $attendance[1] : 'Inconnue';
        if (!isset($attendancesByDate[$date])) {
            $attendancesByDate[$date] = 1;
        } else {
            $attendancesByDate[$date]++;
        }
    }
    ksort($attendancesByDate); // Trier par ordre chronologique
    $stats['attendances_by_date'] = $attendancesByDate;
    
    // Participants les plus assidus
    $assiduParticipants = [];
    foreach ($attendances as $attendance) {
        $email = $attendance[0]; // Email du participant
        if (!isset($assiduParticipants[$email])) {
            $assiduParticipants[$email] = [
                'email' => $email,
                'nom' => $attendance[3],
                'prenom' => $attendance[4],
                'count' => 1
            ];
        } else {
            $assiduParticipants[$email]['count']++;
        }
    }
    
    // Trier par nombre de présences (décroissant)
    usort($assiduParticipants, function($a, $b) {
        return $b['count'] - $a['count'];
    });
    
    // Limiter aux 10 premiers
    $assiduParticipants = array_slice($assiduParticipants, 0, 10);
    $stats['assidu_participants'] = $assiduParticipants;
    
    // Répartition des horaires de pointage
    $timeSlots = [
        'Avant 9h' => 0,
        '9h-12h' => 0,
        '12h-14h' => 0,
        '14h-17h' => 0,
        'Après 17h' => 0
    ];
    
    foreach ($attendances as $attendance) {
        if (isset($attendance[2])) {
            $time = strtotime($attendance[2]);
            $hour = date('G', $time); // Heure au format 24h sans leading zero
            
            if ($hour < 9) {
                $timeSlots['Avant 9h']++;
            } elseif ($hour >= 9 && $hour < 12) {
                $timeSlots['9h-12h']++;
            } elseif ($hour >= 12 && $hour < 14) {
                $timeSlots['12h-14h']++;
            } elseif ($hour >= 14 && $hour < 17) {
                $timeSlots['14h-17h']++;
            } else {
                $timeSlots['Après 17h']++;
            }
        }
    }
    $stats['time_slots'] = $timeSlots;
    
    return $stats;
}

/**
 * Récupère les statistiques sur les événements
 * @return array Tableau associatif de statistiques
 */
function getEventStats() {
    $stats = [];
    $eventsFile = "events.csv";
    $events = [];
    
    if (file_exists($eventsFile)) {
        $file = fopen($eventsFile, "r");
        while (($data = fgetcsv($file)) !== false) {
            $events[$data[0]] = $data;
        }
        fclose($file);
    }
    
    $stats['total_events'] = count($events);
    
    // Statut des événements
    $eventStatus = [
        'En cours' => 0,
        'Terminé' => 0
    ];
    
    foreach ($events as $event) {
        $status = isset($event[4]) ? $event[4] : 'Inconnu';
        if (isset($eventStatus[$status])) {
            $eventStatus[$status]++;
        }
    }
    $stats['event_status'] = $eventStatus;
    
    // Obtenir les présences par événement
    $attendanceStats = getAttendanceStats();
    $attendancesByEvent = $attendanceStats['attendances_by_event'];
    
    // Associer les noms d'événements aux statistiques de présence
    $eventParticipation = [];
    foreach ($attendancesByEvent as $eventId => $count) {
        $eventName = isset($events[$eventId]) ? $events[$eventId][1] : "Événement #$eventId";
        $eventParticipation[$eventName] = $count;
    }
    arsort($eventParticipation); // Trier par popularité
    $stats['event_participation'] = $eventParticipation;
    
    return $stats;
}

/**
 * Récupère toutes les statistiques
 * @return array Tableau associatif de toutes les statistiques
 */
function getAllStats() {
    return [
        'participants' => getParticipantStats(),
        'attendances' => getAttendanceStats(),
        'events' => getEventStats()
    ];
}
?>