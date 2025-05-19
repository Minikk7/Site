<?php
// Näytä kaikki virheet kehitysympäristössä
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/db.php';
require_once 'includes/functions.php';

// Lisää ostoskoriin -toiminto
if (isset($_POST['add_to_cart']) && isLoggedIn()) {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Validointi
    if ($product_id <= 0 || $quantity <= 0) {
        $_SESSION['error'] = "Virheellinen tuote tai määrä.";
        header("Location: products.php");
        exit();
    }
    
    // Tarkistetaan tuotteen olemassaolo ja varastotilanne
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $_SESSION['error'] = "Tuotetta ei löydy.";
        header("Location: products.php");
        exit();
    }
    
    if ($product['stock'] < $quantity) {
        $_SESSION['error'] = "Tuotetta ei ole riittävästi varastossa.";
        header("Location: products.php" . (isset($_GET['id']) ? "?id=" . $_GET['id'] : ""));
        exit();
    }
    
    try {
        // Tarkistetaan onko tuote jo ostoskorissa
        $stmt = $pdo->prepare("SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $existing_item = $stmt->fetch();
        
        if ($existing_item) {
            // Päivitetään määrää
            $new_quantity = $existing_item['quantity'] + $quantity;
            
            // Tarkistetaan vielä varastotilanne
            if ($new_quantity > $product['stock']) {
                $new_quantity = $product['stock'];
                $_SESSION['warning'] = "Ostoskorissa on nyt maksimimäärä tätä tuotetta.";
            }
            
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt->execute([$new_quantity, $existing_item['id']]);
            
            $_SESSION['success'] = "Tuotteen määrää ostoskorissa päivitetty!";
        } else {
            // Lisätään uusi tuote ostoskoriin
            $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            
            $_SESSION['success'] = "Tuote lisätty ostoskoriin!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Tuotteen lisääminen ostoskoriin epäonnistui.";
        error_log("Ostoskoriin lisääminen epäonnistui: " . $e->getMessage());
    }
    
    // Ohjataan takaisin samalle sivulle
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['id']) ? "?id=" . $_GET['id'] : ""));
    exit();
}

// Tuotteen tietojen näyttäminen
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = $_GET['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $_SESSION['error'] = "Tuotetta ei löydy.";
        header("Location: products.php");
        exit();
    }
    
    $page_title = $product['name'] . " - Tietokonekauppa";
} else {
    // Tuotelistaus
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : null;
    
    $query = "SELECT * FROM products WHERE 1=1";
    $params = [];
    
    if ($category) {
        $query .= " AND category = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $query .= " AND (name LIKE ? OR description LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $query .= " ORDER BY name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Haetaan kategoriat
    $stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
    $categories = $stmt->fetchAll();
    
    $page_title = "Tuotteet - Tietokonekauppa";
}

include 'includes/header.php';
?>

<div class="container my-4">
    <?php if (isset($_GET['id'])): ?>
        <!-- Yksittäisen tuotteen näkymä -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Etusivu</a></li>
                <li class="breadcrumb-item"><a href="products.php">Tuotteet</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
        
        <div class="row">
            <div class="col-md-5">
                <?php if (!empty($product['image'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid">
                <?php else: ?>
                    <img src="https://via.placeholder.com/500x400" alt="Placeholder" class="img-fluid">
                <?php endif; ?>
            </div>
            <div class="col-md-7">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-muted">Kategoria: <?php echo htmlspecialchars($product['category']); ?></p>
                
                <div class="my-3">
                    <h3 class="text-primary"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</h3>
                    
                    <?php if ($product['stock'] > 0): ?>
                        <span class="badge badge-success">Varastossa: <?php echo $product['stock']; ?> kpl</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Ei varastossa</span>
                    <?php endif; ?>
                </div>
                
                <div class="my-3">
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <?php if (isLoggedIn()): ?>
                    <form method="post" action="products.php?id=<?php echo $product['id']; ?>" class="my-3">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <div class="form-row align-items-center">
                            <div class="col-auto">
                                <div class="input-group mb-2">
                                    <div class="input-group-prepend">
                                        <div class="input-group-text">Määrä</div>
                                    </div>
                                    <input type="number" class="form-control" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button type="submit" name="add_to_cart" class="btn btn-primary mb-2" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart"></i> Lisää ostoskoriin
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        <a href="login.php">Kirjaudu sisään</a> lisätäksesi tuotteita ostoskoriin.
                    </div>
                <?php endif; ?>
                
                <a href="products.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Takaisin tuotteisiin
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Tuotelistaus -->
        <h1>Tuotteet</h1>
        
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="get" action="products.php" class="form-inline">
                    <div class="input-group w-100">
                        <input type="text" class="form-control" name="search" placeholder="Etsi tuotteita..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i> Hae
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <div class="dropdown float-right">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="categoryDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php echo isset($_GET['category']) ? htmlspecialchars($_GET['category']) : 'Kaikki kategoriat'; ?>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="categoryDropdown">
                        <a class="dropdown-item" href="products.php">Kaikki kategoriat</a>
                        <div class="dropdown-divider"></div>
                        <?php foreach ($categories as $cat): ?>
                            <a class="dropdown-item" href="products.php?category=<?php echo urlencode($cat['category']); ?>"><?php echo htmlspecialchars($cat['category']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                <p>Tuotteita ei löytynyt.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="card-img-top" style="height: 300px; object-fit: cover;">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/300x200" alt="Placeholder" class="card-img-top">
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($product['category']); ?></p>
                                <p class="card-text"><?php echo substr(htmlspecialchars($product['description']), 0, 100) . '...'; ?></p>
                                <h5 class="text-primary"><?php echo number_format($product['price'], 2, ',', ' '); ?> €</h5>
                                
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="badge badge-success">Varastossa: <?php echo $product['stock']; ?> kpl</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Ei varastossa</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="products.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-secondary">Näytä tiedot</a>
                                    
                                    <?php if (isLoggedIn() && $product['stock'] > 0): ?>
                                        <form method="post" action="products.php">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary">
                                                <i class="fas fa-shopping-cart"></i> Lisää koriin
                                            </button>
                                        </form>
                                    <?php elseif (isLoggedIn()): ?>
                                        <button class="btn btn-sm btn-secondary" disabled>Ei varastossa</button>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-sm btn-outline-primary">Kirjaudu sisään</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>