<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Vaaditaan kirjautuminen ja admin-oikeudet
requireLogin();
if (!isAdmin()) {
    $_SESSION['error'] = "Sinulla ei ole oikeuksia tähän sivuun.";
    header("Location: ../index.php");
    exit();
}

// Tuotteen poistaminen
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        $_SESSION['success'] = "Tuote poistettu onnistuneesti.";
    } else {
        $_SESSION['error'] = "Tuotteen poistaminen epäonnistui.";
    }
    
    header("Location: manage-products.php");
    exit();
}

// Tuotteen lisääminen tai päivittäminen
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = isset($_POST['id']) ? $_POST['id'] : null;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $category = trim($_POST['category']);
    $image = trim($_POST['image']);
    
    // Validointi
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Tuotteen nimi on pakollinen";
    }
    
    if (empty($description)) {
        $errors[] = "Tuotteen kuvaus on pakollinen";
    }
    
    if ($price <= 0) {
        $errors[] = "Hinnan tulee olla suurempi kuin 0";
    }
    
    if ($stock < 0) {
        $errors[] = "Varastomäärä ei voi olla negatiivinen";
    }
    
    if (empty($category)) {
        $errors[] = "Kategoria on pakollinen";
    }
    
    // Jos ei virheitä, lisätään tai päivitetään tuote
    if (empty($errors)) {
        if ($id) {
            // Päivitetään olemassa oleva tuote
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image = ? WHERE id = ?");
            $result = $stmt->execute([$name, $description, $price, $stock, $category, $image, $id]);
            
            if ($result) {
                $_SESSION['success'] = "Tuote päivitetty onnistuneesti.";
                header("Location: manage-products.php");
                exit();
            } else {
                $errors[] = "Tuotteen päivittäminen epäonnistui.";
            }
        } else {
            // Lisätään uusi tuote
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category, image) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$name, $description, $price, $stock, $category, $image]);
            
            if ($result) {
                $_SESSION['success'] = "Tuote lisätty onnistuneesti.";
                header("Location: manage-products.php");
                exit();
            } else {
                $errors[] = "Tuotteen lisääminen epäonnistui.";
            }
        }
    }
    
    // Jos virheitä, näytetään ne käyttäjälle
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

// Haetaan tuote muokkausta varten
$product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = $_GET['edit'];
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        $_SESSION['error'] = "Tuotetta ei löytynyt.";
        header("Location: manage-products.php");
        exit();
    }
}

// Haetaan kaikki tuotteet
$stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
$products = $stmt->fetchAll();

// Haetaan kategoriat
$stmt = $pdo->query("SELECT DISTINCT category FROM products ORDER BY category");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tuotteiden hallinta - Tietokonekauppa</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #343a40;
        }
        .sidebar a {
            color: rgba(255, 255, 255, 0.75);
        }
        .sidebar a:hover {
            color: #fff;
        }
        .sidebar .active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">Tietokonekauppa</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Takaisin kauppaan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Kirjaudu ulos</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 sidebar py-3">
                <div class="list-group">
                    <a href="index.php" class="list-group-item list-group-item-action bg-dark">Kojelauta</a>
                    <a href="manage-products.php" class="list-group-item list-group-item-action active bg-dark">Tuotteet</a>
                    <a href="#" class="list-group-item list-group-item-action bg-dark">Käyttäjät</a>
                    <a href="#" class="list-group-item list-group-item-action bg-dark">Tilaukset</a>
                    <a href="#" class="list-group-item list-group-item-action bg-dark">Asetukset</a>
                </div>
            </div>
            
            <div class="col-md-10 py-3">
                <h1 class="mb-4">Tuotteiden hallinta</h1>
                
                <?php 
                showError();
                showSuccess();
                ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo $product ? 'Muokkaa tuotetta' : 'Lisää uusi tuote'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="manage-products.php" method="post">
                            <?php if ($product): ?>
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="name">Tuotteen nimi</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $product ? htmlspecialchars($product['name']) : ''; ?>" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="price">Hinta (€)</label>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo $product ? $product['price'] : ''; ?>" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="stock">Varastossa (kpl)</label>
                                    <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?php echo $product ? $product['stock'] : '0'; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Kuvaus</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo $product ? htmlspecialchars($product['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="category">Kategoria</label>
                                    <input type="text" class="form-control" id="category" name="category" list="categories" value="<?php echo $product ? htmlspecialchars($product['category']) : ''; ?>" required>
                                    <datalist id="categories">
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="image">Kuvan URL</label>
                                    <input type="text" class="form-control" id="image" name="image" value="<?php echo $product ? htmlspecialchars($product['image']) : ''; ?>">
                                    <small class="form-text text-muted">Jätä tyhjäksi käyttääksesi oletuskuvaa</small>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><?php echo $product ? 'Päivitä tuote' : 'Lisää tuote'; ?></button>
                            <?php if ($product): ?>
                            <a href="manage-products.php" class="btn btn-secondary">Peruuta</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Tuotteet</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nimi</th>
                                        <th>Kategoria</th>
                                        <th>Hinta</th>
                                        <th>Varastossa</th>
                                        <th>Toiminnot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><?php echo $p['id']; ?></td>
                                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                                        <td><?php echo htmlspecialchars($p['category']); ?></td>
                                        <td><?php echo number_format($p['price'], 2, ',', ' '); ?> €</td>
                                        <td>
                                            <?php if ($p['stock'] == 0): ?>
                                            <span class="badge badge-danger">Loppunut</span>
                                            <?php elseif ($p['stock'] < 5): ?>
                                            <span class="badge badge-warning"><?php echo $p['stock']; ?> kpl</span>
                                            <?php else: ?>
                                            <span class="badge badge-success"><?php echo $p['stock']; ?> kpl</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="manage-products.php?edit=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary">Muokkaa</a>
                                            <a href="manage-products.php?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Haluatko varmasti poistaa tämän tuotteen?')">Poista</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
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