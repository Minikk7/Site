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
    $password = $_POST['password'];
    
    // Validointi
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Käyttäjänimi tai sähköposti on pakollinen";
    }
    
    if (empty($password)) {
        $errors[] = "Salasana on pakollinen";
    }
    
    // Jos ei virheitä, yritetään kirjautua
    if (empty($errors)) {
        // Haetaan käyttäjä tietokannasta käyttäjänimen tai sähköpostin perusteella
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Tarkistetaan onko käyttäjä vahvistanut sähköpostiosoitteensa
            if ($user['is_verified'] == 0) {
                $_SESSION['error'] = "Vahvista sähköpostiosoitteesi ennen kirjautumista. Tarkista sähköpostisi.";
                header("Location: login.php");
                exit();
            }
            
            // Kirjataan käyttäjä sisään
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_verified'] = $user['is_verified'];
            
            // Tarkistetaan onko käyttäjä admin (tässä esimerkissä id 1 on admin)
            $_SESSION['is_admin'] = ($user['id'] == 1) ? 1 : 0;
            
            $_SESSION['success'] = "Tervetuloa, " . $user['username'] . "!";
            header("Location: index.php");
            exit();
        } else {
            $errors[] = "Virheellinen käyttäjänimi/sähköposti tai salasana";
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
                <h4 class="mb-0">Kirjaudu sisään</h4>
            </div>
            <div class="card-body">
                <form action="login.php" method="post">
                    <div class="form-group">
                        <label for="username">Käyttäjänimi tai sähköposti</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Salasana</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <a href="reset-password-request.php">Unohditko salasanasi?</a>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Kirjaudu</button>
                </form>
                <div class="mt-3 text-center">
                    <p>Eikö sinulla ole vielä tiliä? <a href="register.php">Rekisteröidy</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>