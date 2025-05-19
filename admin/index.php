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

// Haetaan tilastoja
$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_products FROM products");
$total_products = $stmt->fetch()['total_products'];

$stmt = $pdo->query("SELECT COUNT(*) as verified_users FROM users WHERE is_verified = 1");
$verified_users = $stmt->fetch()['verified_users'];

$stmt = $pdo->query("SELECT SUM(stock) as total_stock FROM products");
$total_stock = $stmt->fetch()['total_stock'];

// Haetaan uusimmat käyttäjät
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$latest_users = $stmt->fetchAll();

// Haetaan tuotteet joiden varasto on vähissä
$stmt = $pdo->query("SELECT * FROM products WHERE stock < 5 ORDER BY stock ASC LIMIT 5");
$low_stock_products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hallintapaneeli - Tietokonekauppa</title>
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
        .stat-card {
            border-left: 4px solid #007bff;
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
                    <a href="index.php" class="list-group-item list-group-item-action active bg-dark">Kojelauta</a>
                    <a href="manage-products.php" class="list-group-item list-group-item-action bg-dark">Tuotteet</a>
                    <a href="#" class="list-group-item list-group-item-action bg-dark">Käyttäjät</a>
                    <a href="#" class="list-group-item list-group-item-action bg-dark">Tilaukset</a>
                    <a href="#" class="list-group-item list-group-item-action bg-dark">Asetukset</a>
                </div>
            </div>
            
            <div class="col-md-10 py-3">
                <h1 class="mb-4">Hallintapaneeli</h1>
                
                <?php 
                showError();
                showSuccess();
                ?>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Käyttäjät</h5>
                                <h2 class="card-text"><?php echo $total_users; ?></h2>
                                <p class="card-text text-muted">Yhteensä</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Vahvistetut käyttäjät</h5>
                                <h2 class="card-text"><?php echo $verified_users; ?></h2>
                                <p class="card-text text-muted"><?php echo round(($verified_users / $total_users) * 100); ?>% käyttäjistä</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Tuotteet</h5>
                                <h2 class="card-text"><?php echo $total_products; ?></h2>
                                <p class="card-text text-muted">Yhteensä</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Varastossa</h5>
                                <h2 class="card-text"><?php echo $total_stock; ?></h2>
                                <p class="card-text text-muted">Tuotteita</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Uusimmat käyttäjät</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Käyttäjänimi</th>
                                                <th>Sähköposti</th>
                                                <th>Rekisteröitynyt</th>
                                                <th>Tila</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($latest_users as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <?php if ($user['is_verified']): ?>
                                                    <span class="badge badge-success">Vahvistettu</span>
                                                    <?php else: ?>
                                                    <span class="badge badge-warning">Vahvistamaton</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Vähissä olevat tuotteet</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tuote</th>
                                                <th>Kategoria</th>
                                                <th>Hinta</th>
                                                <th>Varastossa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($low_stock_products as $product): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                                <td><?php echo number_format($product['price'], 2, ',', ' '); ?> €</td>
                                                <td>
                                                    <?php if ($product['stock'] == 0): ?>
                                                    <span class="badge badge-danger">Loppunut</span>
                                                    <?php else: ?>
                                                    <span class="badge badge-warning"><?php echo $product['stock']; ?> kpl</span>
                                                    <?php endif; ?>
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
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>