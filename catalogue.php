<?php
// Page catalogue
require_once 'includes/config.php';
require_once 'includes/header.php';

// Récupérer le filtre catégorie
$categorie_filter = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;

// Récupérer le terme de recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construire la requête SQL
$sql = "SELECT m.*, c.nom_categorie 
        FROM materiel m 
        INNER JOIN categorie c ON m.id_categorie = c.id_categorie 
        WHERE 1=1";

// Array pour les paramètres
$params = [];

// Ajouter filtre catégorie
if ($categorie_filter > 0) {
    $sql .= " AND m.id_categorie = :categorie";
    $params['categorie'] = $categorie_filter;
}

// Ajouter filtre recherche
if (!empty($search)) {
    // ✅ FIX: Placeholders différents
    $sql .= " AND (m.nom_materiel LIKE :search1 OR m.description LIKE :search2)";
    $params['search1'] = "%{$search}%";
    $params['search2'] = "%{$search}%";
}

$sql .= " ORDER BY m.nom_materiel ASC";

// Préparer et exécuter la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$materiels = $stmt->fetchAll();

// Récupérer toutes les catégories pour le menu
$stmt_cat = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie");
$categories = $stmt_cat->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue - PLUTINA EVENT</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- Page Header -->
    <div class="hero-gold py-4">
        <div class="container">
            <h1 class="mb-0"><i class="fas fa-box-open"></i> Notre Catalogue</h1>
            <p class="mb-0">Découvrez notre sélection de matériel événementiel</p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <!-- Sidebar Filtres -->
            <div class="col-md-3 mb-4">
                <div class="card card-gold">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-filter icon-gold"></i> Filtres</h5>

                        <!-- Filtre par catégorie -->
                        <h6 class="mt-3">Catégories</h6>
                        <div class="list-group">
                            <a href="catalogue.php" class="list-group-item list-group-item-action <?php echo $categorie_filter == 0 ? 'active' : ''; ?>">
                                Toutes les catégories
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="catalogue.php?categorie=<?php echo $cat['id_categorie']; ?>"
                                    class="list-group-item list-group-item-action <?php echo $categorie_filter == $cat['id_categorie'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste Matériels -->
            <div class="col-md-9">
                <!-- Barre de recherche -->
                <form method="GET" action="catalogue.php" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control"
                            placeholder="Rechercher un matériel..."
                            value="<?php echo htmlspecialchars($search); ?>">
                        <?php if ($categorie_filter > 0): ?>
                            <input type="hidden" name="categorie" value="<?php echo $categorie_filter; ?>">
                        <?php endif; ?>
                        <button class="btn btn-gold" type="submit">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                </form>

                <!-- Nombre de résultats -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong><?php echo count($materiels); ?></strong> matériel(s) trouvé(s)
                    <?php if (!empty($search)): ?>
                        pour "<strong><?php echo htmlspecialchars($search); ?></strong>"
                    <?php endif; ?>
                </div>

                <!-- Grille de matériels -->
                <div class="row">
                    <?php if (count($materiels) > 0): ?>
                        <?php foreach ($materiels as $mat): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card card-gold h-100">
                                    <!-- Image matériel -->
                                    <?php
                                    $image_path = 'images/' . $mat['photo'];
                                    $image_exists = file_exists($image_path);
                                    ?>
                                    <?php if ($image_exists): ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>"
                                            class="card-img-top"
                                            alt="<?php echo htmlspecialchars($mat['nom_materiel']); ?>"
                                            style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light text-center d-flex align-items-center justify-content-center"
                                            style="height: 200px;">
                                            <i class="fas fa-image fa-4x text-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <span class="badge bg-secondary mb-2">
                                            <?php echo htmlspecialchars($mat['nom_categorie']); ?>
                                        </span>
                                        <h5 class="card-title"><?php echo htmlspecialchars($mat['nom_materiel']); ?></h5>
                                        <p class="card-text text-muted small">
                                            <?php
                                            $desc = htmlspecialchars($mat['description']);
                                            echo strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc;
                                            ?>
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="h5 mb-0 text-gold">
                                                <?php echo number_format($mat['prix_journalier'], 0, ',', ' '); ?> Ar
                                            </span>
                                            <small class="text-muted">/jour</small>
                                        </div>

                                        <div class="mb-3">
                                            <small>
                                                <i class="fas fa-box"></i>
                                                Disponible: <strong><?php echo $mat['quantite_disponible']; ?></strong> / <?php echo $mat['quantite_totale']; ?>
                                            </small>
                                        </div>

                                        <a href="detail.php?id=<?php echo $mat['id_materiel']; ?>"
                                            class="btn btn-outline-gold w-100">
                                            <i class="fas fa-eye"></i> Voir détails
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Aucun matériel trouvé. Essayez de modifier vos critères de recherche.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>