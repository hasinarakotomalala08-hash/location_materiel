<?php
session_start();
require_once 'includes/config.php';
$page_title = "Accueil - PLUTINA EVENT";
include 'includes/header.php';
?>

<!-- Section Hero avec background et GRAND logo -->
<div class="hero-background">
    <div class="hero-overlay">
        <div class="container text-center py-5">
            <!-- Grand logo hero (optionnel) -->


            <h1 class="display-3 mb-4 hero-title">PLUTINA EVENT</h1>
            <p class="lead mb-4 hero-subtitle">
                Location de matériel événementiel à Madagascar<br>
                Tables, chaises, vaisselle et décoration pour tous vos événements
            </p>
            <a href="catalogue.php" class="btn btn-gold btn-lg shadow-lg">
                <i class="fas fa-search"></i> Voir le Catalogue
            </a>
        </div>
    </div>
</div>

<!-- Section Catégories -->
<div class="container my-5">
    <h2 class="text-center mb-4 text-gold">Nos Catégories</h2>
    <div class="row">
        <?php
        // Récupérer les catégories depuis la BDD
        $stmt = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie");
        $categories = $stmt->fetchAll();

        // Icônes pour chaque catégorie
        $icons = [
            'Mobilier' => 'fa-chair',
            'Vaisselle' => 'fa-utensils',
            'Décoration' => 'fa-star'
        ];

        foreach ($categories as $cat):
            $icon = $icons[$cat['nom_categorie']] ?? 'fa-box';
        ?>
            <div class="col-md-4 mb-4">
                <div class="card card-gold h-100 text-center">
                    <div class="card-body">
                        <i class="fas <?php echo $icon; ?> fa-3x icon-gold mb-3"></i>
                        <h5 class="card-title"><?php echo htmlspecialchars($cat['nom_categorie']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($cat['description']); ?></p>
                        <a href="catalogue.php?categorie=<?php echo $cat['id_categorie']; ?>" class="btn btn-outline-gold">
                            Voir les articles
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Section Avantages -->
<div class="bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-4 text-gold">Pourquoi nous choisir ?</h2>
        <div class="row text-center">
            <div class="col-md-3 mb-3">
                <i class="fas fa-truck fa-3x icon-gold mb-3"></i>
                <h5>Livraison</h5>
                <p>Service de livraison disponible sur Antananarivo</p>
            </div>
            <div class="col-md-3 mb-3">
                <i class="fas fa-check-circle fa-3x icon-gold mb-3"></i>
                <h5>Qualité</h5>
                <p>Matériel propre et bien entretenu</p>
            </div>
            <div class="col-md-3 mb-3">
                <i class="fas fa-clock fa-3x icon-gold mb-3"></i>
                <h5>Disponibilité 24/7</h5>
                <p>Réservation en ligne à tout moment</p>
            </div>
            <div class="col-md-3 mb-3">
                <i class="fas fa-tag fa-3x icon-gold mb-3"></i>
                <h5>Prix compétitifs</h5>
                <p>Tarifs abordables et transparents</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>