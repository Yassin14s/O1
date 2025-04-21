<?php
// Script pour vider le cache et forcer le rechargement des événements
// À appeler après une mise à jour de statut d'événement
// Placez ce fichier à la racine de votre site

// Empêcher la mise en cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Forcer l'actualisation du fichier CSV des événements
if (file_exists('events.csv')) {
    // Toucher le fichier pour mettre à jour sa date de modification
    touch('events.csv');
    echo "Cache des événements vidé avec succès.";
} else {
    echo "Le fichier d'événements n'existe pas.";
}

// Créer/vider un fichier de log pour le débogage
file_put_contents('cache_clear.log', date('Y-m-d H:i:s') . ' - Cache vidé' . "\n");

// Rediriger vers la page d'accueil après 2 secondes
echo '<script>
    setTimeout(function() {
        window.location.href = "ld7.html";
    }, 2000);
</script>';
?>