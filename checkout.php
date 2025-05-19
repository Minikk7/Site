<?php
// Näytä kaikki virheet kehitysympäristössä
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';
require_once 'includes/functions.php';

// Vaaditaan kirjautuminen
requireLogin();

// Tarkistetaan onko ostoskorissa tuotteita
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetch()['count'];
    
    if ($cart_count == 0) {
        $_SESSION['error'] = "Ostoskorisi on tyhjä.";
        header("Location: cart.php");
        exit();
    }
    
    // Haetaan ostoskorin sisältö
    $stmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.stock 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
    
    // Tarkistetaan varastotilanne
    $stock_issues = false;
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $stock_issues = true;
            break;
        }
    }
    
    // Lasketaan ostoskorin summa
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Toimituskulut
    $shipping = 5.90;
    $total = $subtotal + $shipping;
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Ostoskorin tietojen hakeminen epäonnistui.";
    error_log("Ostoskorin hakeminen epäonnistui: " . $e->getMessage());
    header("Location: cart.php");
    exit();
}

// Tilauksen käsittely
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Validointi
    $errors = [];
    
    if (empty($_POST['first_name'])) {
        $errors[] = "Etunimi on pakollinen.";
    }
    
    if (empty($_POST['last_name'])) {
        $errors[] = "Sukunimi on pakollinen.";
    }
    
    if (empty($_POST['address'])) {
        $errors[] = "Osoite on pakollinen.";
    }
    
    if (empty($_POST['postal_code'])) {
        $errors[] = "Postinumero on pakollinen.";
    }
    
    if (empty($_POST['city'])) {
        $errors[] = "Kaupunki on pakollinen.";
    }
    
    if (empty($_POST['phone'])) {
        $errors[] = "Puhelinnumero on pakollinen.";
    }
    
    if (!isset($_POST['terms']) || $_POST['terms'] != 'on') {
        $errors[] = "Sinun täytyy hyväksyä toimitusehdot.";
    }
    
    if (empty($errors)) {
        try {
            // Aloitetaan transaktio
            $pdo->beginTransaction();
            
            // Luodaan tilaus
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, first_name, last_name, address, postal_code, city, phone, 
                                   payment_method, subtotal, shipping, total, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $_POST['first_name'],
                $_POST['last_name'],
                $_POST['address'],
                $_POST['postal_code'],
                $_POST['city'],
                $_POST['phone'],
                $_POST['payment_method'],
                $subtotal,
                $shipping,
                $total
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Lisätään tilauksen tuotteet
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['quantity'],
                    $item['price']
                ]);
                
                // Päivitetään varastosaldo
                $new_stock = $item['stock'] - $item['quantity'];
                $update_stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $update_stmt->execute([$new_stock, $item['product_id']]);
            }
            
            // Tyhjennetään ostoskori
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            // Vahvistetaan transaktio
            $pdo->commit();
            
            $_SESSION['success'] = "Tilauksesi on vastaanotettu! Kiitos ostoksistasi.";
            header("Location: order_confirmation.php?id=" . $order_id);
            exit();
            
        } catch (PDOException $e) {
            // Perutaan transaktio virheen sattuessa
            $pdo->rollBack();
            
            $_SESSION['error'] = "Tilauksen käsittelyssä tapahtui virhe. Yritä uudelleen.";
            error_log("Tilauksen käsittely epäonnistui: " . $e->getMessage());
        }
    } else {
        // Näytetään virheet käyttäjälle
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Sivun otsikko
$page_title = "Kassa - Tietokonekauppa";
include 'includes/header.php';
?>

<div class="container my-4">
    <h1>Kassa</h1>
    
    <?php 
    showError();
    showSuccess();
    ?>
    
    <?php if ($stock_issues): ?>
        <div class="alert alert-warning">
            <p><strong>Huomio!</strong> Joitakin tuotteita ei ole riittävästi varastossa. Ole hyvä ja päivitä ostoskorisi.</p>
            <a href="cart.php" class="btn btn-primary mt-2">Takaisin ostoskoriin</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Toimitusosoite</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" id="checkout-form">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="first_name">Etunimi</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="last_name">Sukunimi</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="address">Katuosoite</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" required>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="postal_code">Postinumero</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>" required>
                                </div>
                                <div class="form-group col-md-8">
                                    <label for="city">Kaupunki</label>
                                    <input type="text" class="form-control" id="city" name="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="phone">Puhelinnumero</label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                            </div>
                            
                            <div class="card mt-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Maksutapa</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_method_1" value="credit_card" <?php echo (!isset($_POST['payment_method']) || $_POST['payment_method'] == 'credit_card') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="payment_method_1">
                                            Luottokortti
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_method_2" value="bank_transfer" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'bank_transfer') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="payment_method_2">
                                            Verkkopankki
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="payment_method" id="payment_method_3" value="invoice" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'invoice') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="payment_method_3">
                                            Lasku
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Hyväksyn <a href="#" target="_blank">toimitusehdot</a> ja <a href="#" target="_blank">tietosuojakäytännön</a>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Tilauksesi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tuote</th>
                                        <th>Määrä</th>
                                        <th class="text-right">Hinta</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item): 
                                        $item_total = $item['price'] * $item['quantity'];
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="text-right"><?php echo number_format($item_total, 2, ',', ' '); ?> €</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="2">Välisumma:</th>
                                        <th class="text-right"><?php echo number_format($subtotal, 2, ',', ' '); ?> €</th>
                                    </tr>
                                    <tr>
                                        <th colspan="2">Toimitus:</th>
                                        <th class="text-right"><?php echo number_format($shipping, 2, ',', ' '); ?> €</th>
                                    </tr>
                                    <tr>
                                        <th colspan="2">Yhteensä:</th>
                                        <th class="text-right"><?php echo number_format($total, 2, ',', ' '); ?> €</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <button type="submit" form="checkout-form" name="place_order" class="btn btn-success btn-block mt-3">
                            <i class="fas fa-check"></i> Vahvista tilaus
                        </button>
                        <a href="cart.php" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-arrow-left"></i> Takaisin ostoskoriin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>