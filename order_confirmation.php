<?php
// Näytä kaikki virheet kehitysympäristössä
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';
require_once 'includes/functions.php';

// Vaaditaan kirjautuminen
requireLogin();

// Tarkistetaan tilauksen ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Virheellinen tilaus.";
    header("Location: index.php");
    exit();
}

$order_id = intval($_GET['id']);

// Haetaan tilauksen tiedot
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.username, u.email 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?
    ");
    $stmt->execute([$order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $_SESSION['error'] = "Tilausta ei löydy.";
        header("Location: index.php");
        exit();
    }
    
    // Haetaan tilauksen tuotteet
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Tilauksen tietojen hakeminen epäonnistui.";
    error_log("Tilauksen hakeminen epäonnistui: " . $e->getMessage());
    header("Location: index.php");
    exit();
}

// Sivun otsikko
$page_title = "Tilausvahvistus - Tietokonekauppa";
include 'includes/header.php';
?>

<div class="container my-4">
    <div class="jumbotron text-center">
        <h1 class="display-4">Kiitos tilauksestasi!</h1>
        <p class="lead">Tilauksesi on vastaanotettu ja käsitellään pian.</p>
        <hr class="my-4">
        <p>Tilausnumero: <strong><?php echo $order_id; ?></strong></p>
        <p>Vahvistus on lähetetty sähköpostiisi: <strong><?php echo htmlspecialchars($order['email']); ?></strong></p>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Tilauksen tiedot</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Toimitusosoite</h6>
                    <p>
                        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?><br>
                        <?php echo htmlspecialchars($order['address']); ?><br>
                        <?php echo htmlspecialchars($order['postal_code'] . ' ' . $order['city']); ?><br>
                        Puhelin: <?php echo htmlspecialchars($order['phone']); ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6>Tilauksen yhteenveto</h6>
                    <p>
                        Tilausnumero: <?php echo $order_id; ?><br>
                        Tilauspäivä: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?><br>
                        Maksutapa: <?php echo getPaymentMethodName($order['payment_method']); ?><br>
                        Tila: <?php echo getOrderStatusName($order['status']); ?>
                    </p>
                </div>
            </div>
            
            <h6 class="mt-4">Tilatut tuotteet</h6>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Tuote</th>
                            <th>Hinta</th>
                            <th>Määrä</th>
                            <th class="text-right">Yhteensä</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): 
                            $item_total = $item['price'] * $item['quantity'];
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($item['image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-thumbnail mr-3" style="max-width: 60px;">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/60x60" alt="Placeholder" class="img-thumbnail mr-3">
                                        <?php endif; ?>
                                        <div>
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo number_format($item['price'], 2, ',', ' '); ?> €</td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td class="text-right"><?php echo number_format($item_total, 2, ',', ' '); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-right">Välisumma:</th>
                            <th class="text-right"><?php echo number_format($order['subtotal'], 2, ',', ' '); ?> €</th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-right">Toimitus:</th>
                            <th class="text-right"><?php echo number_format($order['shipping'], 2, ',', ' '); ?> €</th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-right">Yhteensä:</th>
                            <th class="text-right"><?php echo number_format($order['total'], 2, ',', ' '); ?> €</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <div class="text-center mb-4">
        <a href="index.php" class="btn btn-primary">Palaa etusivulle</a>
        <a href="products.php" class="btn btn-secondary ml-2">Jatka ostoksia</a>
    </div>
</div>

<?php 
// Apufunktiot
function getPaymentMethodName($method) {
    $methods = [
        'credit_card' => 'Luottokortti',
        'bank_transfer' => 'Verkkopankki',
        'invoice' => 'Lasku'
    ];
    
    return $methods[$method] ?? $method;
}

function getOrderStatusName($status) {
    $statuses = [
        'pending' => 'Odottaa käsittelyä',
        'processing' => 'Käsittelyssä',
        'shipped' => 'Lähetetty',
        'delivered' => 'Toimitettu',
        'cancelled' => 'Peruttu'
    ];
    
    return $statuses[$status] ?? $status;
}

include 'includes/footer.php'; 
?>