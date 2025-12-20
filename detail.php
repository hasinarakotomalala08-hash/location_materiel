<?php
// Page détail matériel
require_once 'includes/config.php';

// Récupérer l'ID du matériel
$id_materiel = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Si pas d'ID, rediriger vers catalogue
if ($id_materiel <= 0) {
    header('Location: catalogue.php');
    exit;
}

// Récupérer les infos du matériel
$stmt = $pdo->prepare("
    SELECT m.*, c.nom_categorie 
    FROM materiel m 
    INNER JOIN categorie c ON m.id_categorie = c.id_categorie 
    WHERE m.id_materiel = :id
");
$stmt->execute(['id' => $id_materiel]);
$materiel = $stmt->fetch();

// Si matériel n'existe pas
if (!$materiel) {
    header('Location: catalogue.php');
    exit;
}

// Vérifier si l'image existe
$image_path = 'images/' . $materiel['photo'];
$image_exists = file_exists($image_path);

require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($materiel['nom_materiel']); ?> - PLUTINA EVENT</title>

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


    <!-- Détail Matériel -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <!-- Image -->
            <div class="col-md-5 mb-4">
                <div class="card card-gold">
                    <?php if ($image_exists): ?>
                        <!-- Image cliquable avec effet hover -->
                        <div style="position: relative; cursor: pointer; overflow: hidden;"
                            data-bs-toggle="modal"
                            data-bs-target="#imageModal">
                            <img src="<?php echo htmlspecialchars($image_path); ?>"
                                class="card-img-top"
                                alt="<?php echo htmlspecialchars($materiel['nom_materiel']); ?>"
                                style="width: 100%; height: 400px; object-fit: cover; transition: transform 0.3s;">
                            <!-- Overlay avec icône zoom -->
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                                        background: rgba(212, 175, 55, 0.8); color: white; padding: 15px; 
                                        border-radius: 50%; opacity: 0; transition: opacity 0.3s;"
                                class="zoom-overlay">
                                <i class="fas fa-search-plus fa-2x"></i>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-light text-center d-flex align-items-center justify-content-center"
                            style="height: 400px;">
                            <i class="fas fa-image fa-5x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Informations -->
            <div class="col-md-5">
                <span class="badge bg-secondary mb-2">
                    <?php echo htmlspecialchars($materiel['nom_categorie']); ?>
                </span>

                <h1 class="mb-3"><?php echo htmlspecialchars($materiel['nom_materiel']); ?></h1>

                <div class="card card-gold mb-3">
                    <div class="card-body">
                        <h2 class="text-gold mb-0">
                            <?php echo number_format($materiel['prix_journalier'], 0, ',', ' '); ?> Ar
                            <small class="text-muted fs-6">/jour</small>
                        </h2>
                    </div>
                </div>

                <!-- Disponibilité -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-box"></i> Disponibilité</h5>
                    <div class="progress" style="height: 25px;">
                        <?php
                        $pourcentage = ($materiel['quantite_disponible'] / $materiel['quantite_totale']) * 100;
                        $color = $pourcentage > 50 ? 'success' : ($pourcentage > 20 ? 'warning' : 'danger');
                        ?>
                        <div class="progress-bar bg-<?php echo $color; ?>"
                            style="width: <?php echo $pourcentage; ?>%">
                            <?php echo $materiel['quantite_disponible']; ?> / <?php echo $materiel['quantite_totale']; ?> disponible(s)
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="card card-gold mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-info-circle icon-gold"></i> Description</h5>
                        <p class="card-text"><?php echo nl2br(htmlspecialchars($materiel['description'])); ?></p>
                    </div>
                </div>

                <!-- Boutons Action -->
                <div class="d-grid gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Si connecté: formulaire ajout panier -->
                        <form method="POST" action="panier.php" id="addToCartForm">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id_materiel" value="<?php echo $materiel['id_materiel']; ?>">

                            <!-- Quantité -->
                            <div class="mb-3">
                                <label for="quantite" class="form-label fw-bold">
                                    <i class="fas fa-sort-numeric-up icon-gold"></i> Quantité
                                </label>
                                <input type="number"
                                    class="form-control form-control-lg"
                                    id="quantite"
                                    name="quantite"
                                    value="1"
                                    min="1"
                                    max="<?php echo $materiel['quantite_disponible']; ?>"
                                    required>
                                <small class="text-muted">
                                    Maximum: <?php echo $materiel['quantite_disponible']; ?> disponible(s)
                                </small>
                            </div>

                            <!-- Dates -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="date_debut" class="form-label fw-bold">
                                        <i class="fas fa-calendar-alt icon-gold"></i> Date début
                                    </label>
                                    <input type="date"
                                        class="form-control"
                                        id="date_debut"
                                        name="date_debut"
                                        min="<?php echo date('Y-m-d'); ?>"
                                        required>
                                </div>
                                <div class="col-md-6">
                                    <label for="date_fin" class="form-label fw-bold">
                                        <i class="fas fa-calendar-check icon-gold"></i> Date fin
                                    </label>
                                    <input type="date"
                                        class="form-control"
                                        id="date_fin"
                                        name="date_fin"
                                        min="<?php echo date('Y-m-d'); ?>"
                                        required>
                                </div>
                            </div>

                            <!-- Calcul prix -->
                            <div class="alert alert-info mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><strong>Prix unitaire:</strong></span>
                                    <span class="text-gold fw-bold"><?php echo number_format($materiel['prix_journalier'], 0, ',', ' '); ?> Ar/jour</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center" id="totalPreview">
                                    <span><strong>Total estimé:</strong></span>
                                    <span class="text-gold fw-bold">-</span>
                                </div>
                            </div>

                            <!-- Bouton -->
                            <button type="submit" class="btn btn-gold btn-lg w-100">
                                <i class="fas fa-shopping-cart"></i> Ajouter au panier
                            </button>
                        </form>
                    <?php else: ?>
                        <!-- Si non connecté: message -->
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            <strong>Connexion requise</strong> pour réserver du matériel.
                        </div>
                        <a href="connexion.php" class="btn btn-gold btn-lg">
                            <i class="fas fa-sign-in-alt"></i> Se connecter
                        </a>
                    <?php endif; ?>

                    <a href="catalogue.php" class="btn btn-outline-gold">
                        <i class="fas fa-arrow-left"></i> Retour au catalogue
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Image Zoom -->
    <?php if ($image_exists): ?>
        <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><?php echo htmlspecialchars($materiel['nom_materiel']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-0">
                        <img src="<?php echo htmlspecialchars($image_path); ?>"
                            class="img-fluid"
                            alt="<?php echo htmlspecialchars($materiel['nom_materiel']); ?>"
                            style="max-height: 80vh; width: auto;">
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hover effect image zoom
            const imageContainer = document.querySelector('[data-bs-target="#imageModal"]');
            if (imageContainer) {
                const img = imageContainer.querySelector('img');
                const overlay = imageContainer.querySelector('.zoom-overlay');

                imageContainer.addEventListener('mouseenter', function() {
                    img.style.transform = 'scale(1.05)';
                    overlay.style.opacity = '1';
                });

                imageContainer.addEventListener('mouseleave', function() {
                    img.style.transform = 'scale(1)';
                    overlay.style.opacity = '0';
                });
            }

            // Calcul prix automatique (panier)
            const quantiteInput = document.getElementById('quantite');
            const dateDebutInput = document.getElementById('date_debut');
            const dateFinInput = document.getElementById('date_fin');
            const totalPreview = document.getElementById('totalPreview');
            const prixJournalier = <?php echo $materiel['prix_journalier']; ?>;

            function calculerTotal() {
                const quantite = parseInt(quantiteInput?.value) || 0;
                const dateDebut = new Date(dateDebutInput?.value);
                const dateFin = new Date(dateFinInput?.value);

                if (dateDebut && dateFin && dateFin >= dateDebut && quantite > 0) {
                    const nbJours = Math.ceil((dateFin - dateDebut) / (1000 * 60 * 60 * 24)) + 1;
                    const total = quantite * prixJournalier * nbJours;

                    totalPreview.innerHTML = `
                <span><strong>Total estimé:</strong> <small>(${quantite} × ${nbJours} jour(s))</small></span>
                <span class="text-gold fw-bold">${total.toLocaleString('fr-FR')} Ar</span>
            `;
                }
            }

            if (quantiteInput) {
                quantiteInput.addEventListener('input', calculerTotal);
                dateDebutInput.addEventListener('change', calculerTotal);
                dateFinInput.addEventListener('change', calculerTotal);
            }
        });
    </script>

    <?php include 'includes/footer.php'; ?>