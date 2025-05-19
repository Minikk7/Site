<?php
// Näytä kaikki virheet
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tietokantayhteys
require_once 'config/db.php';

// Alustetaan viestit
$message = '';
$messageType = '';
$showForm = false;
$debug = true; // Aseta true nähdäksesi debug-tiedot

// Tarkistetaan token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    

    // Normaali tarkistus (ilman vanhentumistarkistusta)
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        $showForm = true;
    } else {
        $message = "Virheellinen nollauslinkki. Token ei löydy tietokannasta.";
        $messageType = "danger";
    }
} else {
    $message = "Virheellinen pyyntö. Token puuttuu.";
    $messageType = "danger";
}

// Tarkistetaan onko lomake lähetetty
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Tarkistetaan löytyykö token tietokannasta
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $message = "Virheellinen nollauslinkki. Token ei löydy tietokannasta.";
        $messageType = "danger";
    } elseif (empty($password)) {
        $message = "Salasana on pakollinen";
        $messageType = "danger";
        $showForm = true;
    } elseif (strlen($password) < 6) {
        $message = "Salasanan tulee olla vähintään 6 merkkiä pitkä";
        $messageType = "danger";
        $showForm = true;
    } elseif ($password !== $confirm_password) {
        $message = "Salasanat eivät täsmää";
        $messageType = "danger";
        $showForm = true;
    } else {
        // Salataan uusi salasana
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Päivitetään käyttäjän salasana ja nollataan token
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $result = $stmt->execute([$hashed_password, $user['id']]);
        
        if ($result) {
            $message = "Salasanasi on nyt vaihdettu! Voit kirjautua sisään.";
            $messageType = "success";
            $showForm = false;
        } else {
            $message = "Salasanan vaihto epäonnistui. Yritä uudelleen.";
            $messageType = "danger";
            $showForm = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salasanan vaihtaminen - Tietokonekauppa</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Salasanan vaihtaminen</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($message)): ?>
                            <div class="alert alert-<?php echo $messageType; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($showForm): ?>
                            <form method="post">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                <div class="form-group">
                                    <label for="password">Uusi salasana</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="form-text text-muted">Salasanan tulee olla vähintään 6 merkkiä pitkä.</small>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password">Vahvista uusi salasana</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Vaihda salasana</button>
                            </form>
                        <?php else: ?>
                            <div class="text-center mt-3">
                                <a href="login.php" class="btn btn-primary">Siirry kirjautumissivulle</a>
                                <a href="reset-password-request.php" class="btn btn-secondary mt-2">Pyydä uusi nollauslinkki</a>
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