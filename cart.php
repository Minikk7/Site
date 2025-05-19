<?php
// Näytä kaikki virheet kehitysympäristössä
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';
require_once 'includes/functions.php';

// Vaaditaan kirjautuminen
requireLogin();

// Ostoskorin päivitys
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $item_id => $quantity) {
        $item_id = intval($item_id);
        $quantity = intval($quantity);
        
        if ($quantity > 0) {
            // Tarkistetaan varastotilanne
            $stmt = $pdo->prepare("
                SELECT p.stock 
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.id = ? AND ci.user_id = ?
            ");
            $stmt->execute([$item_id, $_SESSION['user_id']]);
            $product = $stmt->fetch();
            
            if ($product && $quantity > $product['stock']) {
                $quantity = $product['stock'];
                $_SESSION['warning'] = "Joidenkin tuotteiden määrää rajoitettiin varastotilanteen mukaan.";
            }
            
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $item_id, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
            $stmt->execute([$item_id, $_SESSION['user_id']]);
        }
    }
    
    $_SESSION['success'] = "Ostoskori päivitetty!";
    header("Location: cart.php");
    exit();
}

// Tuotteen poisto ostoskorista
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $item_id = intval($_GET['remove']);
    
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$item_id, $_SESSION['user_id']]);
    
    if ($result) {
        $_SESSION['success'] = "Tuote poistettu ostoskorista!";
    } else {
        $_SESSION['error'] = "Tuotteen poistaminen epäonnistui.";
    }
    
    header("Location: cart.php");
    exit();
}

// Ostoskorin tyhjennys
if (isset($_GET['clear'])) {
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $result = $stmt->execute([$_SESSION['user_id']]);
    
    if ($result) {
        $_SESSION['success'] = "Ostoskori tyhjennetty!";
    } else {
        $_SESSION['error'] = "Ostoskorin tyhjentäminen epäonnistui.";
    }
    
    header("Location: cart.php");
    exit();
}

// Haetaan ostoskorin sisältö
try {
    $stmt = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.image, p.stock 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?
        ORDER BY ci.added_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll();
    
    // Lasketaan ostoskorin summa
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Ostoskorin tietojen hakeminen epäonnistui.";
    error_log("Ostoskorin hakeminen epäonnistui: " . $e->getMessage());
    $cart_items = [];
    $total = 0;
}

// Sivun otsikko
$page_title = "Ostoskori - Tietokonekauppa";
include 'includes/header.php';
?>

<div class="container my-4">
    <h1>Ostoskori</h1>
    
    <?php 
    showError();
    showSuccess();
    
    // Näytetään varoitukset, jos niitä on
    if (isset($_SESSION['warning'])) {
        echo '<div class="alert alert-warning">' . $_SESSION['warning'] . '</div>';
        unset($_SESSION['warning']);
    }
    ?>
    
    <?php if (count($cart_items) > 0): ?>
        <form method="post" action="cart.php">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tuote</th>
                            <th>Hinta</th>
                            <th>Määrä</th>
                            <th>Yhteensä</th>
                            <th>Toiminnot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail mr-3" style="max-width: 60px;">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/60x60" alt="Placeholder" class="img-thumbnail mr-3">
                                        <?php endif; ?>
                                        <div>
                                            <a href="products.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                                            <?php if ($item['stock'] < $item['quantity']): ?>
                                                <div class="text-danger small">Varastossa vain <?php echo $item['stock']; ?> kpl</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo number_format($item['price'], 2, ',', ' '); ?> €</td>
                                <td>
                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="0" max="<?php echo $item['stock']; ?>" class="form-control form-control-sm" style="width: 70px;">
                                </td>
                                <td><?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?> €</td>
                                <td>
                                    <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Haluatko varmasti poistaa tämän tuotteen ostoskorista?')">
                                        <i class="fas fa-trash"></i> Poista
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-right">Yhteensä:</th>
                            <th><?php echo number_format($total, 2, ',', ' '); ?> €</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="d-flex justify-content-between mt-3">
                <div>
                    <button type="submit" name="update_cart" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Päivitä ostoskori
                    </button>
                    <a href="cart.php?clear=1" class="btn btn-secondary ml-2" onclick="return confirm('Haluatko varmasti tyhjentää ostoskorin?')">
                        <i class="fas fa-trash"></i> Tyhjennä ostoskori
                    </a>
                </div>
                <div>
                    <a href="checkout.php" class="btn btn-success">
                        <i class="fas fa-shopping-cart"></i> Siirry kassalle
                    </a>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-info">
            <p>Ostoskorisi on tyhjä.</p>
            <a href="products.php" class="btn btn-primary mt-2">Jatka ostoksia</a>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>