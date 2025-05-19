<?php include 'includes/header.php'; ?>

<div class="jumbotron">
    <h1 class="display-4">Tervetuloa Tietokonekauppaan!</h1>
    <p class="lead">Meiltä löydät laadukkaat tietokoneet ja komponentit kilpailukykyiseen hintaan.</p>
    <hr class="my-4">
    <p>Tutustu valikoimaamme ja löydä itsellesi sopiva ratkaisu.</p>
    <a class="btn btn-primary btn-lg" href="products.php" role="button">Selaa tuotteita</a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <i class="fas fa-laptop fa-3x mb-3"></i>
                <h3>Kannettavat</h3>
                <p>Laaja valikoima kannettavia tietokoneita eri tarpeisiin.</p>
                <a href="products.php?category=Kannettavat" class="btn btn-outline-primary">Katso lisää</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <i class="fas fa-desktop fa-3x mb-3"></i>
                <h3>Pöytäkoneet</h3>
                <p>Tehokkaat pöytäkoneet työhön ja pelaamiseen.</p>
                <a href="products.php?category=Pöytäkoneet" class="btn btn-outline-primary">Katso lisää</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <i class="fas fa-microchip fa-3x mb-3"></i>
                <h3>Komponentit</h3>
                <p>Kaikki tarvittavat komponentit oman koneen rakentamiseen.</p>
                <a href="products.php?category=Komponentit" class="btn btn-outline-primary">Katso lisää</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <h2>Uusimmat tuotteet</h2>
        <hr>
    </div>
</div>

<div class="row">
    <?php
    require_once 'config/db.php';
    
    // Haetaan 3 uusinta tuotetta
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 3");
    while ($row = $stmt->fetch()) {
    ?>
    <div class="col-md-4 mb-4">
        <div class="card product-card">
            <img src="<?php echo !empty($row['image']) ? $row['image'] : 'https://via.placeholder.com/300x200?text=Tietokonekauppa'; ?>" 
                 class="card-img-top product-img" alt="<?php echo htmlspecialchars($row['name']); ?>">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars(substr($row['description'], 0, 100)) . '...'; ?></p>
                <p class="card-text font-weight-bold"><?php echo number_format($row['price'], 2, ',', ' '); ?> €</p>
                <a href="products.php?id=<?php echo $row['id']; ?>" class="btn btn-primary">Katso tuote</a>
            </div>
        </div>
    </div>
    <?php } ?>
</div>

<?php include 'includes/footer.php'; ?>