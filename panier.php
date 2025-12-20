<?php
// Page panier
require_once 'includes/config.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Initialiser panier dans session si n'existe pas
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Variables
$message = '';
$error = '';

// Traitement actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // AJOUTER AU PANIER
    if ($action === 'add') {
        $id_materiel = (int)($_POST['id_materiel'] ?? 0);
        $quantite = (int)($_POST['quantite'] ?? 1);
        $date_debut = $_POST['date_debut'] ?? '';
        $date_fin = $_POST['date_fin'] ?? '';

        // Validation
        if ($id_materiel > 0 && $quantite > 0 && !empty($date_debut) && !empty($date_fin)) {
            // Vérifier si matériel existe
            $stmt = $pdo->prepare("SELECT * FROM materiel WHERE id_materiel = ?");
            $stmt->execute([$id_materiel]);
            $materiel = $stmt->fetch();

            if ($materiel && $quantite <= $materiel['quantite_disponible']) {
                // Calculer nombre de jours
                $debut = new DateTime($date_debut);
                $fin = new DateTime($date_fin);
                $interval = $debut->diff($fin);
                $nb_jours = $interval->days + 1;

                // Ajouter au panier
                $_SESSION['panier'][] = [
                    'id_materiel' => $id_materiel,
                    'nom' => $materiel['nom_materiel'],
                    'prix_unitaire' => $materiel['prix_journalier'],
                    'quantite' => $quantite,
                    'date_debut' => $date_debut,
                    'date_fin' => $date_fin,
                    'nb_jours' => $nb_jours,
                    'photo' => $materiel['photo']
                ];

                $message = "Matériel ajouté au panier avec succès!";
            } else {
                $error = "Quantité non disponible.";
            }
        } else {
            $error = "Données invalides.";
        }
    }

    // SUPPRIMER DU PANIER
    elseif ($action === 'remove') {
        $index = (int)($_POST['index'] ?? -1);
        if (isset($_SESSION['panier'][$index])) {
            unset($_SESSION['panier'][$index]);
            $_SESSION['panier'] = array_values($_SESSION['panier']); // Réindexer
            $message = "Matériel retiré du panier.";
        }
    }

    // MODIFIER QUANTITÉ
    elseif ($action === 'update') {
        $index = (int)($_POST['index'] ?? -1);
        $quantite = (int)($_POST['quantite'] ?? 1);

        if (isset($_SESSION['panier'][$index]) && $quantite > 0) {
            $_SESSION['panier'][$index]['quantite'] = $quantite;
            $message = "Quantité mise à jour.";
        }
    }

    // VIDER PANIER
    elseif ($action === 'clear') {
        $_SESSION['panier'] = [];
        $message = "Panier vidé.";
    }
}

// Calculer total
$total = 0;
foreach ($_SESSION['panier'] as $item) {
    $total += $item['prix_unitaire'] * $item['quantite'] * $item['nb_jours'];
}

$page_title = "Mon Panier - PLUTINA EVENT";
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-light py-2">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="mon-compte.php">Mon Compte</a></li>
                <li class="breadcrumb-item active">Mon Panier</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Panier -->
<div class="container my-5">
    <h1 class="mb-4">
        <i class="fas fa-shopping-cart icon-gold"></i> Mon Panier
    </h1>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($_SESSION['panier'])): ?>
        <!-- Panier vide -->
        <div class="card card-gold">
            <div class="card-body text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-3"></i>
                <h3 class="text-muted">Votre panier est vide</h3>
                <p class="text-muted mb-4">Ajoutez des matériels pour commencer votre réservation.</p>
                <a href="catalogue.php" class="btn btn-gold btn-lg">
                    <i class="fas fa-shopping-bag"></i> Voir le catalogue
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Liste panier -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-gold mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> Articles (<?php echo count($_SESSION['panier']); ?>)
                            </h5>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Vider le panier?')">
                                    <i class="fas fa-trash"></i> Vider le panier
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($_SESSION['panier'] as $index => $item): ?>
                            <div class="border-bottom p-3">
                                <div class="row align-items-center">
                                    <!-- Image -->
                                    <div class="col-md-2">
                                        <?php
                                        $image_path = 'images/' . $item['photo'];
                                        if (file_exists($image_path)): ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>"
                                                class="img-fluid rounded"
                                                alt="<?php echo htmlspecialchars($item['nom']); ?>">
                                        <?php else: ?>
                                            <div class="bg-light text-center p-3 rounded">
                                                <i class="fas fa-image fa-2x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Info -->
                                    <div class="col-md-4">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['nom']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i>
                                            <?php
                                            $debut = new DateTime($item['date_debut']);
                                            $fin = new DateTime($item['date_fin']);
                                            echo $debut->format('d/m/Y') . ' → ' . $fin->format('d/m/Y');
                                            ?>
                                            (<?php echo $item['nb_jours']; ?> jour<?php echo $item['nb_jours'] > 1 ? 's' : ''; ?>)
                                        </small>
                                    </div>

                                    <!-- Quantité -->
                                    <div class="col-md-2">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                                            <input type="number"
                                                name="quantite"
                                                value="<?php echo $item['quantite']; ?>"
                                                min="1"
                                                class="form-control form-control-sm"
                                                onchange="this.form.submit()">
                                        </form>
                                    </div>

                                    <!-- Prix -->
                                    <div class="col-md-3">
                                        <div class="text-end">
                                            <div class="text-muted small">
                                                <?php echo number_format($item['prix_unitaire'], 0, ',', ' '); ?> Ar/jour
                                            </div>
                                            <div class="fw-bold text-gold">
                                                <?php
                                                $subtotal = $item['prix_unitaire'] * $item['quantite'] * $item['nb_jours'];
                                                echo number_format($subtotal, 0, ',', ' ');
                                                ?> Ar
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Supprimer -->
                                    <div class="col-md-1">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Supprimer">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="d-flex gap-2">
                    <a href="catalogue.php" class="btn btn-outline-gold">
                        <i class="fas fa-arrow-left"></i> Continuer mes achats
                    </a>
                </div>
            </div>

            <!-- Récapitulatif -->
            <div class="col-lg-4">
                <div class="card card-gold sticky-top" style="top: 20px;">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice icon-gold"></i> Récapitulatif</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sous-total:</span>
                            <span class="fw-bold"><?php echo number_format($total, 0, ',', ' '); ?> Ar</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold">Total:</span>
                            <span class="fw-bold text-gold fs-4">
                                <?php echo number_format($total, 0, ',', ' '); ?> Ar
                            </span>
                        </div>


                        <div class="alert alert-info small">
                            <i class="fas fa-info-circle"></i>
                            Montants détaillés (acompte, caution) seront calculés à l'étape suivante.
                        </div>

                        <?php
                        // Calculer valeur totale matériel (prix remboursement)
                        $total_remboursement = 0;
                        foreach ($_SESSION['panier'] as $item) {
                            $stmt = $pdo->prepare("SELECT prix_remboursement FROM materiel WHERE id_materiel = ?");
                            $stmt->execute([$item['id_materiel']]);
                            $prix_rembours = $stmt->fetchColumn();
                            $total_remboursement += $prix_rembours * $item['quantite'];
                        }
                        ?>

                        <!-- Valeur matériel -->
                        <div class="alert alert-warning small mb-3">
                            <div class="mb-2">
                                <strong><i class="fas fa-shield-alt"></i> Valeur du matériel loué:</strong>
                            </div>

                            <?php foreach ($_SESSION['panier'] as $item): ?>
                                <?php
                                // Récupérer prix remboursement unitaire
                                $stmt = $pdo->prepare("SELECT prix_remboursement FROM materiel WHERE id_materiel = ?");
                                $stmt->execute([$item['id_materiel']]);
                                $prix_rembours_unitaire = $stmt->fetchColumn();
                                ?>

                                <div class="d-flex justify-content-between align-items-start mb-1" style="font-size: 0.85rem;">
                                    <span class="text-muted">
                                        • <?php echo htmlspecialchars($item['nom']); ?>
                                        <?php if ($item['quantite'] > 1): ?>
                                            <span class="badge bg-secondary"><?php echo $item['quantite']; ?>×</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="text-warning fw-bold">
                                        <?php echo number_format($prix_rembours_unitaire, 0, ',', ' '); ?> Ar/unité
                                    </span>
                                </div>
                            <?php endforeach; ?>

                            <hr class="my-2">
                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                <i class="fas fa-info-circle"></i>
                                En cas de perte ou casse, le prix de remplacement sera déduit de votre caution.
                            </small>
                        </div>


                        <div class="d-grid">
                            <a href="reservation.php" class="btn btn-gold btn-lg">
                                <i class="fas fa-check-circle"></i> Valider ma réservation
                            </a>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>