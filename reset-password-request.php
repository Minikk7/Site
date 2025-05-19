<?php
// Näytä kaikki virheet
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tietokantayhteys ja funktiot
require_once 'config/db.php';
require_once 'includes/functions.php';

// Alustetaan viestit
$message = '';
$messageType = '';
$showForm = true;

// Tarkistetaan onko lomake lähetetty
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Validointi
    if (empty($email)) {
        $message = "Sähköposti on pakollinen";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Virheellinen sähköpostiosoite";
        $messageType = "danger";
    } else {
        // Tarkistetaan löytyykö sähköposti tietokannasta
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Luodaan nollaustoken käyttäen functions.php:n generateToken-funktiota
            $token = generateToken();
            
            // Asetetaan tokenin vanhentumisaika (1 tunti)
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Päivitetään käyttäjän tiedot
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $result = $stmt->execute([$token, $expires, $user['id']]);
            
            if ($result) {
                // Lähetetään nollauslinkki sähköpostiin käyttäen functions.php:n sendEmail-funktiota
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/tietokonekauppa/reset-password.php?token=" . $token;
                
                // HTML-muotoinen viesti
                $html_message = '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <h2>Salasanan nollaus</h2>
                        <p>Hei ' . htmlspecialchars($user['username']) . ',</p>
                        <p>Olet pyytänyt salasanan nollausta Tietokonekauppa-sivustolla.</p>
                        <p>Nollaa salasanasi klikkaamalla alla olevaa painiketta:</p>
                        <p><a href="' . $reset_link . '" class="btn">Nollaa salasana</a></p>
                        <p>Tai kopioi tämä linkki selaimesi osoiteriville:</p>
                        <p>' . $reset_link . '</p>
                        <p>Linkki on voimassa yhden tunnin.</p>
                        <p>Jos et pyytänyt salasanan nollausta, voit jättää tämän viestin huomiotta.</p>
                        <p>Terveisin,<br>Tietokonekauppa</p>
                    </div>
                </body>
                </html>';
                
                // Lähetetään sähköposti
                $mail_sent = sendEmail($email, 'Salasanan nollaus - Tietokonekauppa', $html_message);
                
                // Näytetään viesti käyttäjälle
                if ($mail_sent) {
                    $message = "Salasanan nollauslinkki on lähetetty sähköpostiisi.";
                    $messageType = "success";
                    $showForm = false;
                } else {
                    $message = "Sähköpostin lähetys epäonnistui. Kokeile uudelleen myöhemmin.";
                    $messageType = "danger";
                }
                
                // Näytetään linkki myös kehitysympäristössä
                $showResetLink = true;
            } else {
                $message = "Salasanan nollaus epäonnistui. Yritä uudelleen.";
                $messageType = "danger";
            }
        } else {
            // Turvallisuussyistä näytetään sama viesti vaikka käyttäjää ei löytyisi
            $message = "Jos sähköpostiosoite on rekisteröity, salasanan nollauslinkki on lähetetty.";
            $messageType = "success";
            $showForm = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salasanan nollaus - Tietokonekauppa</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Salasanan nollaus</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($showForm): ?>
                            <p>Syötä sähköpostiosoitteesi, niin lähetämme sinulle linkin salasanan nollausta varten.</p>
                            <form method="post">
                                <div class="form-group">
                                    <label for="email">Sähköposti</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Lähetä nollauslinkki</button>
                            </form>
                        <?php else: ?>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">Siirry kirjautumissivulle</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>