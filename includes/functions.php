<?php
session_start();

// Tarkistaa onko käyttäjä kirjautunut
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Tarkistaa onko käyttäjä admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Ohjaa kirjautumissivulle jos ei ole kirjautunut
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Sinun täytyy kirjautua sisään nähdäksesi tämän sivun.";
        header("Location: login.php");
        exit();
    }
}

// Tarkistaa onko käyttäjä vahvistettu
function isVerified() {
    return isset($_SESSION['is_verified']) && $_SESSION['is_verified'] == 1;
}

// Ohjaa etusivulle jos käyttäjä ei ole vahvistettu
function requireVerified() {
    if (!isVerified()) {
        $_SESSION['error'] = "Sinun täytyy vahvistaa sähköpostiosoitteesi ennen tämän toiminnon käyttämistä.";
        header("Location: index.php");
        exit();
    }
}

// Luo satunnaisen tokenin
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Lähettää sähköpostin
function sendEmail($to, $subject, $message) {
    // Käytetään PHPMaileria sähköpostien lähettämiseen
    require_once __DIR__ . '/../vendor/PHPMailer-master/src/Exception.php';
    require_once __DIR__ . '/../vendor/PHPMailer-master/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/PHPMailer-master/src/SMTP.php';
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Palvelinasetukset
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // Vaihda tämä SMTP-palvelimesi osoitteeksi
        $mail->SMTPAuth   = true;
        $mail->Username   = 'flidar164@gmail.com'; // Vaihda tämä omaksi sähköpostiksesi
        $mail->Password   = 'voov lqfm unmr kvxm'; // Vaihda tämä omaksi salasanaksesi
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Vastaanottajat
        $mail->setFrom('noreply@tietokonekauppa.fi', 'Tietokonekauppa');
        $mail->addAddress($to);
        
        // Sisältö
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        // Lähetä sähköposti
        $mail->send();
        
        // Näytä käyttäjälle viesti onnistuneesta lähetyksestä
        echo "<div class='alert alert-success'>Sähköposti lähetetty osoitteeseen: $to</div>";
        
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        // Näytä virheviesti jos lähetys epäonnistuu
        echo "<div class='alert alert-danger'>Sähköpostin lähetys epäonnistui: {$mail->ErrorInfo}</div>";
        return false;
    }
}

// Näyttää virheilmoituksen
function showError() {
    if(isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
        unset($_SESSION['error']);
    }
}

// Näyttää onnistumisilmoituksen
function showSuccess() {
    if(isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
        unset($_SESSION['success']);
    }
}

/**
 * Näyttää varoitusviestin
 */
function showWarning() {
    if (isset($_SESSION['warning'])) {
        echo '<div class="alert alert-warning">' . $_SESSION['warning'] . '</div>';
        unset($_SESSION['warning']);
    }
}

/**
 * Palauttaa ostoskorin tuotteiden määrän
 */
function getCartItemCount($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Ostoskorin laskeminen epäonnistui: " . $e->getMessage());
        return 0;
    }
}
?>