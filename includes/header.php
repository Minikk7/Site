<?php require_once 'includes/functions.php'; ?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Tietokonekauppa</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Lisätään Font Awesome -ikonit ostoskoria varten -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Tietokonekauppa</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Etusivu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Tuotteet</a>
                    </li>
                    <?php if (isLoggedIn() && isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">Hallintapaneeli</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                    <!-- Lisätään ostoskori-linkki -->
                    <li class="nav-item">
                        <?php
                        // Haetaan ostoskorin tuotteiden määrä
                        $cart_count = 0;
                        try {
                            require_once 'config/db.php';
                            $cart_stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?");
                            $cart_stmt->execute([$_SESSION['user_id']]);
                            $cart_result = $cart_stmt->fetch();
                            $cart_count = $cart_result['total'] ?? 0;
                        } catch (PDOException $e) {
                            // Virheenkäsittely - ei näytetä käyttäjälle
                            error_log("Ostoskorin laskeminen epäonnistui: " . $e->getMessage());
                        }
                        ?>
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Ostoskori
                            <?php if ($cart_count > 0): ?>
                                <span class="badge badge-pill badge-primary"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">Hei, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Kirjaudu ulos</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Kirjaudu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Rekisteröidy</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php 
        showError();
        showSuccess();
        
        // Näytetään varoitukset, jos niitä on
        if (isset($_SESSION['warning'])) {
            echo '<div class="alert alert-warning">' . $_SESSION['warning'] . '</div>';
            unset($_SESSION['warning']);
        }
        ?>