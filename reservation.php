<?php
// Page validation réservation
require_once 'includes/config.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Vérifier si panier non vide
if (empty($_SESSION['panier'])) {
    header('Location: panier.php');
    exit;
}

// Variables
$message = '';
$error = '';

// Calculer totaux
$total_location = 0;
foreach ($_SESSION['panier'] as $item) {
    $total_location += $item['prix_unitaire'] * $item['quantite'] * $item['nb_jours'];
}

// Calculs selon règles métier
$acompte = $total_location * 0.50;      // 50% du total
$caution = $total_location * 0.25;      // 25% du total
$montant_a_regler = $acompte + $caution; // Acompte + Caution
$reste_a_payer = $total_location - $acompte; // Reste après acompte

// Traitement confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer'])) {
    try {
        // Commencer transaction
        $pdo->beginTransaction();

        // 1. Créer réservation
        $stmt = $pdo->prepare("
            INSERT INTO reservation (
                id_client, 
                date_reservation, 
                statut, 
                montant_total,
                acompte_verse,
                caution,
                montant_restant
            ) VALUES (?, NOW(), 'en_attente', ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['user_id'],
            $total_location,
            $acompte,
            $caution,
            $reste_a_payer
        ]);

        $id_reservation = $pdo->lastInsertId();

        // 2. Ajouter lignes réservation (matériels)
        $stmt_ligne = $pdo->prepare("
            INSERT INTO ligne_reservation (
                id_reservation,
                id_materiel,
                quantite,
                prix_unitaire,
                date_debut,
                date_fin
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        // 3. Update quantité disponible matériels
        $stmt_update = $pdo->prepare("
            UPDATE materiel 
            SET quantite_disponible = quantite_disponible - ? 
            WHERE id_materiel = ?
        ");

        foreach ($_SESSION['panier'] as $item) {
            // Insérer ligne
            $stmt_ligne->execute([
                $id_reservation,
                $item['id_materiel'],
                $item['quantite'],
                $item['prix_unitaire'],
                $item['date_debut'],
                $item['date_fin']
            ]);

            // Décrémenter stock
            $stmt_update->execute([
                $item['quantite'],
                $item['id_materiel']
            ]);
        }

        // Commit transaction
        $pdo->commit();

        // Vider panier
        $_SESSION['panier'] = [];

        // Rediriger vers confirmation
        header('Location: confirmation-reservation.php?id=' . $id_reservation);
        exit;
    } catch (Exception $e) {
        // Rollback en cas d'erreur
        $pdo->rollBack();
        $error = "Erreur lors de l'enregistrement de la réservation. Veuillez réessayer.";
    }
}

$page_title = "Validation Réservation - PLUTINA EVENT";
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-light py-2">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="mon-compte.php">Mon Compte</a></li>
                <li class="breadcrumb-item"><a href="panier.php">Panier</a></li>
                <li class="breadcrumb-item active">Validation</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Validation Réservation -->
<div class="container my-5">
    <h1 class="mb-4">
        <i class="fas fa-check-circle icon-gold"></i> Validation de la réservation
    </h1>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Récapitulatif matériels -->
        <div class="col-lg-7">
            <div class="card card-gold mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list-ul"></i> Récapitulatif de votre commande
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($_SESSION['panier'] as $item): ?>
                        <div class="border-bottom p-3">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <?php
                                    $image_path = 'images/' . $item['photo'];
                                    if (file_exists($image_path)): ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>"
                                            class="img-fluid rounded"
                                            alt="<?php echo htmlspecialchars($item['nom']); ?>">
                                    <?php else: ?>
                                        <div class="bg-light text-center p-2 rounded">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['nom']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        <?php
                                        $debut = new DateTime($item['date_debut']);
                                        $fin = new DateTime($item['date_fin']);
                                        echo $debut->format('d/m/Y') . ' → ' . $fin->format('d/m/Y');
                                        ?>
                                        (<?php echo $item['nb_jours']; ?> jour<?php echo $item['nb_jours'] > 1 ? 's' : ''; ?>)
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-sort-numeric-up"></i> Quantité: <?php echo $item['quantite']; ?>
                                    </small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="text-muted small">
                                        <?php echo number_format($item['prix_unitaire'], 0, ',', ' '); ?> Ar ×
                                        <?php echo $item['quantite']; ?> ×
                                        <?php echo $item['nb_jours']; ?>j
                                    </div>
                                    <div class="fw-bold text-gold">
                                        <?php
                                        $subtotal = $item['prix_unitaire'] * $item['quantite'] * $item['nb_jours'];
                                        echo number_format($subtotal, 0, ',', ' ');
                                        ?> Ar
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Bouton retour -->
            <a href="panier.php" class="btn btn-outline-gold">
                <i class="fas fa-arrow-left"></i> Modifier mon panier
            </a>
        </div>

        <!-- Détail paiement -->
        <div class="col-lg-5">
            <!-- Conditions paiement -->
            <div class="card card-gold mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator icon-gold"></i> Détail des montants
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Total location -->
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <div>
                            <strong>Total location</strong>
                            <br><small class="text-muted">Montant total du matériel</small>
                        </div>
                        <div class="text-end">
                            <strong class="text-gold fs-5">
                                <?php echo number_format($total_location, 0, ',', ' '); ?> Ar
                            </strong>
                        </div>
                    </div>

                    <!-- Acompte -->
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <strong class="text-primary">Acompte (50%)</strong>
                            <br><small class="text-muted">À verser maintenant</small>
                        </div>
                        <div class="text-end">
                            <strong class="text-primary fs-5">
                                <?php echo number_format($acompte, 0, ',', ' '); ?> Ar
                            </strong>
                        </div>
                    </div>

                    <!-- Caution -->
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <strong class="text-warning">Caution (25%)</strong>
                            <br><small class="text-muted">Remboursable après retour</small>
                        </div>
                        <div class="text-end">
                            <strong class="text-warning fs-5">
                                <?php echo number_format($caution, 0, ',', ' '); ?> Ar
                            </strong>
                        </div>
                    </div>

                    <hr class="my-3">

                    <!-- Montant à régler -->
                    <div class="d-flex justify-content-between mb-3 p-3 bg-light rounded">
                        <div>
                            <strong class="fs-5">À régler aujourd'hui</strong>
                            <br><small class="text-muted">Acompte + Caution</small>
                        </div>
                        <div class="text-end">
                            <strong class="text-gold" style="font-size: 1.8rem;">
                                <?php echo number_format($montant_a_regler, 0, ',', ' '); ?> Ar
                            </strong>
                        </div>
                    </div>

                    <!-- Reste à payer -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Reste à payer:</strong>
                        <?php echo number_format($reste_a_payer, 0, ',', ' '); ?> Ar
                        <br><small>À régler lors de la récupération du matériel</small>
                    </div>
                </div>
            </div>

            <!-- Confirmation -->
            <div class="card card-gold">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-handshake icon-gold"></i> Conditions générales
                    </h6>
                    <ul class="small text-muted mb-3">
                        <li><strong>Propreté:</strong> Le matériel vous est livré propre et doit être retourné dans le même état</li>
                        <li><strong>Caution:</strong> La caution sera remboursée après vérification du matériel retourné</li>
                        <li><strong>En cas de perte ou casse:</strong>
                            <ul class="mt-1">
                                <li>Le prix de remboursement sera déduit de la caution (25%)</li>
                                <li>Si le montant de la caution est supérieur: la différence vous sera remboursée</li>
                                <li>Si le montant est insuffisant: un complément vous sera demandé</li>
                            </ul>
                        </li>
                        <li><strong>Solde restant:</strong> <?php echo number_format($reste_a_payer, 0, ',', ' '); ?> Ar à régler lors de la récupération du matériel</li>
                        <li>Votre réservation sera confirmée après validation par notre équipe</li>
                        <ul>

                            <form method="POST">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="accepte" required>
                                    <label class="form-check-label small" for="accepte">
                                        J'accepte les conditions générales de location
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" name="confirmer" class="btn btn-gold btn-lg">
                                        <i class="fas fa-check-circle"></i> Confirmer ma réservation
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-lock"></i> Paiement sécurisé
                                </small>
                            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>