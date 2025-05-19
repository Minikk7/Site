<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Jos käyttäjä on jo kirjautunut, ohjataan etusivulle
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validointi
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Käyttäjänimi on pakollinen";
    } elseif (strlen($username) < 3) {
        $errors[] = "Käyttäjänimen tulee olla vähintään 3 merkkiä pitkä";
    }
    
    if (empty($email)) {
        $errors[] = "Sähköposti on pakollinen";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Virheellinen sähköpostiosoite";
    }
    
    if (empty($password)) {
        $errors[] = "Salasana on pakollinen";
    } elseif (strlen($password) < 8) {
        $errors[] = "Salasanan tulee olla vähintään 8 merkkiä pitkä";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Salasanat eivät täsmää";
    }
    
    // Tarkistetaan onko käyttäjänimi tai sähköposti jo käytössä
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['username'] === $username) {
                $errors[] = "Käyttäjänimi on jo käytössä";
            }
            if ($user['email'] === $email) {
                $errors[] = "Sähköpostiosoite on jo käytössä";
            }
        }
    }
    
    // Jos ei virheitä, rekisteröidään käyttäjä
    if (empty($errors)) {
        // Luodaan vahvistustoken
        $verification_token = generateToken();
        
        // Salataan salasana
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Lisätään käyttäjä tietokantaan
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_token) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$username, $email, $hashed_password, $verification_token]);
        
        if ($result) {
            // Lähetetään vahvistussähköposti
            $verification_link = "http://" . $_SERVER['HTTP_HOST'] . "/tietokonekauppa/verify.php?token=" . $verification_token;
            
            $subject = "Vahvista sähköpostiosoitteesi - Tietokonekauppa";
            $message = "
            <html>
            <head>
                <title>Vahvista sähköpostiosoitteesi</title>
            </head>
            <body>
                <h2>Kiitos rekisteröitymisestä Tietokonekauppaan!</h2>
                <p>Vahvista sähköpostiosoitteesi klikkaamalla alla olevaa linkkiä:</p>
                <p><a href='$verification_link'>$verification_link</a></p>
                <p>Jos et rekisteröitynyt Tietokonekauppaan, voit jättää tämän viestin huomiotta.</p>
            </body>
            </html>
            ";
            
            sendEmail($email, $subject, $message);
            
            $_SESSION['success'] = "Rekisteröityminen onnistui! Vahvistusviesti on lähetetty sähköpostiisi.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Rekisteröityminen epäonnistui. Yritä uudelleen.";
        }
    }
    
    // Jos virheitä, näytetään ne käyttäjälle
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Rekisteröidy</h4>
            </div>
            <div class="card-body">
                <form action="register.php" method="post">
                    <div class="form-group">
                        <label for="username">Käyttäjänimi</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Sähköposti</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Salasana</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small id="password-feedback" class="form-text"></small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Vahvista salasana</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Rekisteröidy</button>
                </form>
                <div class="mt-3 text-center">
                    <p>Onko sinulla jo tili? <a href="login.php">Kirjaudu sisään</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>