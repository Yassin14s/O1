<?php
// Page d'affichage du QR code d'un participant

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "Identifiant participant manquant.";
    exit;
}

$participantId = $_GET['id'];
$qrFile = "qr_codes/" . $participantId . ".json";

if (!file_exists($qrFile)) {
    echo "QR code introuvable pour ce participant.";
    exit;
}

$qrData = file_get_contents($qrFile);
$participantData = json_decode($qrData, true);

// Rechercher les informations du participant dans data.csv
$dataFile = "database/data.csv";
$participantDetails = null;

if (file_exists($dataFile)) {
    $file = fopen($dataFile, "r");
    
    while (($data = fgetcsv($file)) !== false) {
        // Vérifier si l'ID du participant (index 11) correspond
        if (isset($data[11]) && $data[11] === $participantId) {
            $participantDetails = [
                'nom' => $data[0],
                'prenom' => $data[1],
                'organisme' => $data[2],
                'fonction' => $data[3],
                'email' => $data[5]
            ];
            break;
        } 
        // Sinon, vérifier par l'email (pour compatibilité)
        elseif (isset($participantData['email']) && isset($data[5]) && $data[5] === $participantData['email']) {
            $participantDetails = [
                'nom' => $data[0],
                'prenom' => $data[1],
                'organisme' => $data[2],
                'fonction' => $data[3],
                'email' => $data[5]
            ];
            break;
        }
    }
    fclose($file);
}

// Si pas de détails trouvés, utiliser les données du QR code
if (!$participantDetails && isset($participantData['nom']) && isset($participantData['prenom'])) {
    $participantDetails = [
        'nom' => $participantData['nom'],
        'prenom' => $participantData['prenom'],
        'email' => isset($participantData['email']) ? $participantData['email'] : ''
    ];
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Participant</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #eef2f7 100%);
            color: #333;
            line-height: 1.6;
        }
        
        .header {
            background-color: white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            text-align: center;
        }
        
        .logo {
            width: 180px;
            height: auto;
        }
        
        .container {
            max-width: 500px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.05);
            text-align: center;
        }
        
        h1 {
            color: #d32f2f;
            font-size: 24px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .qr-container {
            margin: 30px auto;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            width: 250px;
            height: 250px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border: 1px dashed #ddd;
        }
        
        .participant-info {
            margin: 25px 0;
            text-align: left;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #d32f2f;
        }
        
        .participant-info p {
            margin: 10px 0;
            display: flex;
            align-items: center;
        }
        
        .participant-info p i {
            margin-right: 10px;
            color: #d32f2f;
            width: 20px;
            text-align: center;
        }
        
        .print-button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 50px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 3px 10px rgba(76, 175, 80, 0.3);
        }
        
        .print-button:hover {
            background: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }
        
        .print-button i {
            margin-right: 8px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 25px;
            color: #007bff;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        
        .qr-note {
            margin-top: 20px;
            padding: 15px;
            background-color: #e8f4fd;
            border-radius: 8px;
            color: #0277bd;
            font-size: 14px;
            display: flex;
            align-items: center;
            border-left: 4px solid #0277bd;
        }
        
        .qr-note i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        @media (max-width: 600px) {
            .container {
                width: 90%;
                padding: 20px;
            }
        }
        
        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            justify-content: center;
        }
        
        .card-header .icon {
            background-color: #d32f2f;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .sharing-options {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        
        .share-button {
            background: #f0f0f0;
            color: #333;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .share-button:hover {
            background: #e0e0e0;
        }
        
        .share-button i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="https://www.enabel.be/app/uploads/2023/04/Enabel_Logo_Color_RGB.png" alt="Enabel Logo" class="logo">
    </div>
    
    <div class="container">
        <div class="card-header">
            <div class="icon">
                <i class="fas fa-qrcode"></i>
            </div>
            <h1>QR Code du Participant</h1>
        </div>
        
        <?php if ($participantDetails): ?>
        <div class="participant-info">
            <p><i class="fas fa-user"></i> <strong>Nom:</strong> <?php echo htmlspecialchars($participantDetails['nom']); ?></p>
            <p><i class="fas fa-user"></i> <strong>Prénom:</strong> <?php echo htmlspecialchars($participantDetails['prenom']); ?></p>
            <?php if (isset($participantDetails['organisme'])): ?>
            <p><i class="fas fa-building"></i> <strong>Organisme:</strong> <?php echo htmlspecialchars($participantDetails['organisme']); ?></p>
            <?php endif; ?>
            <?php if (isset($participantDetails['fonction'])): ?>
            <p><i class="fas fa-briefcase"></i> <strong>Fonction:</strong> <?php echo htmlspecialchars($participantDetails['fonction']); ?></p>
            <?php endif; ?>
            <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <?php echo htmlspecialchars($participantDetails['email']); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="qr-container" id="qrcode"></div>
        
        <div class="qr-note">
            <i class="fas fa-info-circle"></i>
            <span>Scannez ce QR code pour marquer votre présence lors des prochains événements.</span>
        </div>
        
        <button class="print-button" onclick="printQRCode()">
            <i class="fas fa-print"></i> Imprimer le QR Code
        </button>
        
        <div class="sharing-options">
            <button class="share-button" onclick="saveQrCodeImage()">
                <i class="fas fa-download"></i> Télécharger
            </button>
            <button class="share-button" onclick="shareQrCode()">
                <i class="fas fa-share-alt"></i> Partager
            </button>
        </div>
        
        <br>
        <a href="ld7.html" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour à l'accueil
        </a>
    </div>
    
    <script>
        // Générer le QR code
        var qrcode = new QRCode(document.getElementById("qrcode"), {
            text: <?php echo json_encode($qrData); ?>,
            width: 250,
            height: 250,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Fonction pour imprimer le QR code
        function printQRCode() {
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>QR Code - <?php echo isset($participantDetails) ? htmlspecialchars($participantDetails['prenom'] . ' ' . $participantDetails['nom']) : 'Participant'; ?></title>
                    <style>
                        body {
                            font-family: 'Poppins', sans-serif;
                            text-align: center;
                            padding: 20px;
                        }
                        h1 {
                            font-size: 18px;
                            color: #d32f2f;
                        }
                        .logo {
                            max-width: 150px;
                            margin-bottom: 20px;
                        }
                        .participant-info {
                            margin: 15px 0;
                            text-align: left;
                            padding: 15px;
                            border: 1px solid #eee;
                            border-radius: 8px;
                        }
                        .participant-info p {
                            margin: 5px 0;
                            font-size: 14px;
                        }
                        .qr-note {
                            margin-top: 15px;
                            font-size: 12px;
                            color: #666;
                        }
                    </style>
                </head>
                <body>
                    <img src="https://www.enabel.be/app/uploads/2023/04/Enabel_Logo_Color_RGB.png" alt="Enabel Logo" class="logo">
                    <h1>QR Code du Participant</h1>
                    
                    <?php if ($participantDetails): ?>
                    <div class="participant-info">
                        <p><strong>Nom:</strong> <?php echo htmlspecialchars($participantDetails['nom']); ?></p>
                        <p><strong>Prénom:</strong> <?php echo htmlspecialchars($participantDetails['prenom']); ?></p>
                        <?php if (isset($participantDetails['organisme'])): ?>
                        <p><strong>Organisme:</strong> <?php echo htmlspecialchars($participantDetails['organisme']); ?></p>
                        <?php endif; ?>
                        <?php if (isset($participantDetails['fonction'])): ?>
                        <p><strong>Fonction:</strong> <?php echo htmlspecialchars($participantDetails['fonction']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div>
                        <img src="${document.querySelector('#qrcode img').src}" alt="QR Code">
                    </div>
                    
                    <p class="qr-note">Scannez ce QR code pour marquer votre présence aux événements.</p>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            // Imprimer après un court délai pour s'assurer que le contenu est chargé
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }
        
        // Fonction pour télécharger le QR code comme image
        function saveQrCodeImage() {
            const canvas = document.querySelector("#qrcode canvas");
            if (canvas) {
                const link = document.createElement('a');
                link.download = 'qrcode_<?php echo isset($participantDetails) ? preg_replace('/[^a-zA-Z0-9]/', '_', $participantDetails['prenom'] . '_' . $participantDetails['nom']) : 'participant'; ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            }
        }
        
        // Fonction pour partager le QR code (utilisera l'API Web Share si disponible)
        function shareQrCode() {
            const canvas = document.querySelector("#qrcode canvas");
            if (canvas && navigator.share) {
                canvas.toBlob(function(blob) {
                    const file = new File([blob], 'qrcode.png', { type: 'image/png' });
                    navigator.share({
                        title: 'Mon QR Code Enabel',
                        text: 'Voici mon QR Code pour les événements Enabel',
                        files: [file]
                    }).catch(console.error);
                });
            } else {
                alert("Le partage n'est pas pris en charge par votre navigateur.");
            }
        }
    </script>
</body>
</html>