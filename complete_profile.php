<?php
// Traiter la recherche d'un utilisateur
$userFound = false;
$userData = null;
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Si le formulaire de recherche a été soumis
    if (isset($_POST['search_email'])) {
        $searchEmail = trim($_POST['search_email']);
        $dataFile = "database/data.csv";
        
        if (file_exists($dataFile)) {
            $file = fopen($dataFile, "r");
            $allData = [];
            $userIndex = -1;
            
            $index = 0;
            while (($data = fgetcsv($file)) !== FALSE) {
                $allData[] = $data;
                if (isset($data[5]) && strtolower($data[5]) === strtolower($searchEmail)) {
                    $userFound = true;
                    $userData = $data;
                    $userIndex = $index;
                }
                $index++;
            }
            fclose($file);
            
            if (!$userFound) {
                $error = "Aucun utilisateur trouvé avec cet email.";
            }
        } else {
            $error = "La base de données n'existe pas encore.";
        }
    }
    
    // Si le formulaire de mise à jour a été soumis
    if (isset($_POST['update_profile']) && isset($_POST['participant_id'])) {
        $participantId = $_POST['participant_id'];
        $age = isset($_POST['age']) ? trim($_POST['age']) : "";
        $signature = isset($_POST['signature']) ? $_POST['signature'] : "";
        
        $dataFile = "database/data.csv";
        
        if (file_exists($dataFile)) {
            $file = fopen($dataFile, "r");
            $allData = [];
            $userIndex = -1;
            
            $index = 0;
            while (($data = fgetcsv($file)) !== FALSE) {
                if (isset($data[11]) && $data[11] === $participantId) {
                    $userIndex = $index;
                    // Mettre à jour l'âge et la signature
                    $data[6] = $age;              // Âge
                    $data[9] = $signature;        // Signature
                }
                $allData[] = $data;
                $index++;
            }
            fclose($file);
            
            if ($userIndex >= 0) {
                // Réécrire le fichier avec les données mises à jour
                $file = fopen($dataFile, "w");
                foreach ($allData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
                
                $message = "Profil mis à jour avec succès ! Votre QR code est maintenant complet.";
                
                // Rediriger vers la page d'affichage du QR code
                header("Location: view_participant_qr.php?id=" . $participantId);
                exit();
            } else {
                $error = "Impossible de trouver l'utilisateur à mettre à jour.";
            }
        } else {
            $error = "La base de données n'existe pas.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compléter votre profil</title>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            background: linear-gradient(to right, #d3d3d3, #f5f5f5);
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        .logo {
            width: 180px;
            margin-bottom: 20px;
        }
        h1 {
            color: #d32f2f;
            margin-top: 10px;
            font-size: 28px;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .search-form, .update-form {
            margin: 20px 0;
        }
        input[type="email"], input[type="number"] {
            width: 80%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            background: #ffcc00;
            color: #333;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
            margin: 10px 0;
        }
        button:hover {
            background: #e6b800;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .user-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            text-align: left;
        }
        .user-info p {
            margin: 5px 0;
        }
        #signature-pad {
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
            height: 200px;
            background-color: #fff;
            margin: 15px 0;
        }
        .signature-actions {
            margin: 10px 0;
        }
        .clear-signature {
            background-color: #f44336;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://www.enabel.be/app/uploads/2023/04/Enabel_Logo_Color_RGB.png" alt="Enabel Logo" class="logo">
        <h1>Compléter votre profil</h1>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$userFound): ?>
            <div class="search-form">
                <h2>Veuillez entrer votre email pour trouver votre profil</h2>
                <form method="POST">
                    <input type="email" name="search_email" placeholder="Votre adresse email" required>
                    <button type="submit">Rechercher</button>
                </form>
            </div>
        <?php else: ?>
            <div class="user-info">
                <h2>Bonjour, <?php echo htmlspecialchars($userData[1] . ' ' . $userData[0]); ?></h2>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($userData[5]); ?></p>
                <p><strong>Organisme:</strong> <?php echo htmlspecialchars($userData[2]); ?></p>
                <p><strong>Fonction:</strong> <?php echo htmlspecialchars($userData[3]); ?></p>
            </div>
            
            <div class="update-form">
                <h3>Compléter votre profil</h3>
                <p>Veuillez fournir les informations manquantes:</p>
                
                <form method="POST" onsubmit="return prepareSignature()">
                    <input type="hidden" name="update_profile" value="1">
                    <input type="hidden" name="participant_id" value="<?php echo $userData[11]; ?>">
                    <input type="hidden" name="signature" id="signature-data">
                    
                    <div class="form-group">
                        <label for="age">Votre âge:</label>
                        <input type="number" id="age" name="age" min="18" max="120" value="<?php echo !empty($userData[6]) ? $userData[6] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="signature-pad">Votre signature:</label>
                        <div id="signature-pad-container">
                            <canvas id="signature-pad"></canvas>
                        </div>
                        <div class="signature-actions">
                            <button type="button" class="clear-signature" onclick="clearSignature()">Effacer</button>
                        </div>
                    </div>
                    
                    <button type="submit">Mettre à jour mon profil</button>
                </form>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="ld7.html" style="color: #007bff; text-decoration: none;">Retour à l'accueil</a>
        </div>
    </div>

    <script>
        var canvas = document.getElementById('signature-pad');
        var signaturePad;
        
        // Si le canvas existe (l'utilisateur est trouvé), initialiser le pad de signature
        if (canvas) {
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)'
            });
            
            // Redimensionner le canvas pour qu'il remplisse son conteneur
            function resizeCanvas() {
                var ratio =  Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
                signaturePad.clear(); // Clear la signature
            }
            
            window.onresize = resizeCanvas;
            resizeCanvas();
            
            <?php if (!empty($userData[9])): ?>
            // Si une signature existe déjà, l'afficher
            signaturePad.fromDataURL('<?php echo $userData[9]; ?>');
            <?php endif; ?>
        }
        
        // Fonction pour effacer la signature
        function clearSignature() {
            if (signaturePad) {
                signaturePad.clear();
            }
        }
        
        // Fonction pour préparer les données de la signature avant la soumission
        function prepareSignature() {
            if (signaturePad && !signaturePad.isEmpty()) {
                var signatureData = signaturePad.toDataURL();
                document.getElementById('signature-data').value = signatureData;
                return true;
            } else {
                alert("Veuillez signer avant de soumettre le formulaire.");
                return false;
            }
        }
    </script>
</body>
</html>