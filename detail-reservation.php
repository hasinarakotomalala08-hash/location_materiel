<?php
// Page détail réservation
require_once 'includes/config.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer ID réservation
$id_reservation = (int)($_GET['id'] ?? 0);

if ($id_reservation === 0) {
    header('Location: mes-reservations.php');
    exit;
}

// Récupérer réservation
$stmt = $pdo->prepare("
    SELECT r.*, c.nom, c.prenom, c.email, c.telephone
    FROM reservation r
    JOIN client c ON r.id_client = c.id_client
    WHERE r.id_reservation = ? AND r.id_client = ?
");
$stmt->execute([$id_reservation, $_SESSION['user_id']]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header('Location: mes-reservations.php');
    exit;
}

// Récupérer lignes réservation (matériels)
$stmt = $pdo->prepare("
    SELECT lr.*, m.nom_materiel, m.photo, m.prix_remboursement
    FROM ligne_reservation lr
    JOIN materiel m ON lr.id_materiel = m.id_materiel
    WHERE lr.id_reservation = ?
");
$stmt->execute([$id_reservation]);
$lignes = $stmt->fetchAll();

// Calculer valeur totale matériel (pour caution)
$valeur_materiel = 0;
foreach ($lignes as $ligne) {
    $valeur_materiel += $ligne['prix_remboursement'] * $ligne['quantite'];
}

$page_title = "Détail Réservation - PLUTINA EVENT";
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-light py-2">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="mon-compte.php">Mon Compte</a></li>
                <li class="breadcrumb-item"><a href="mes-reservations.php">Mes Réservations</a></li>
                <li class="breadcrumb-item active">Détail</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Détail Réservation -->
<div class="container my-5">
    <!-- Header avec statut -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1>
                <i class="fas fa-file-invoice icon-gold"></i>
                Réservation #<?php echo str_pad($reservation['id_reservation'], 6, '0', STR_PAD_LEFT); ?>
            </h1>
            <p class="text-muted">
                <i class="fas fa-calendar"></i>
                Créée le
                <?php
                $date = new DateTime($reservation['date_reservation']);
                echo $date->format('d/m/Y à H:i');
                ?>
            </p>
        </div>
        <div class="col-md-6 text-end">
            <?php
            // Badge statut
            $badge_class = [
                'en_attente' => 'bg-warning text-dark',
                'confirme' => 'bg-success',
                'en_cours' => 'bg-primary',
                'termine' => 'bg-secondary',
                'annule' => 'bg-danger'
            ];
            $badge_icon = [
                'en_attente' => 'fa-clock',
                'confirme' => 'fa-check',
                'en_cours' => 'fa-play',
                'termine' => 'fa-check-circle',
                'annule' => 'fa-times'
            ];
            $statut_label = str_replace('_', ' ', ucfirst($reservation['statut']));
            ?>
            <h2>
                <span class="badge <?php echo $badge_class[$reservation['statut']]; ?> fs-5 px-4 py-3">
                    <i class="fas <?php echo $badge_icon[$reservation['statut']]; ?>"></i>
                    <?php echo $statut_label; ?>
                </span>
            </h2>
        </div>
    </div>

    <div class="row">
        <!-- Colonne Gauche -->
        <div class="col-lg-8 mb-4">
            <!-- Matériels réservés -->
            <div class="card card-gold mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-box-open icon-gold"></i> Matériels réservés
                        <span class="badge bg-gold"><?php echo count($lignes); ?></span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($lignes as $ligne): ?>
                        <?php
                        // Calculer nombre de jours
                        $debut = new DateTime($ligne['date_debut']);
                        $fin = new DateTime($ligne['date_fin']);
                        $interval = $debut->diff($fin);
                        $nb_jours = $interval->days + 1;
                        $subtotal = $ligne['prix_unitaire'] * $ligne['quantite'] * $nb_jours;
                        ?>
                        <div class="border-bottom p-3">
                            <div class="row align-items-center">
                                <!-- Image -->
                                <div class="col-md-2">
                                    <?php
                                    $image_path = 'images/' . $ligne['photo'];
                                    if (file_exists($image_path)): ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>"
                                            class="img-fluid rounded"
                                            alt="<?php echo htmlspecialchars($ligne['nom_materiel']); ?>"
                                            style="width: 100%; height: 80px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light text-center p-3 rounded">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Info matériel -->
                                <div class="col-md-5">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($ligne['nom_materiel']); ?></h6>
                                    <div class="small text-muted">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo $debut->format('d/m/Y'); ?> → <?php echo $fin->format('d/m/Y'); ?>
                                        <span class="badge bg-info"><?php echo $nb_jours; ?> jour<?php echo $nb_jours > 1 ? 's' : ''; ?></span>
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fas fa-sort-numeric-up"></i>
                                        Quantité: <strong><?php echo $ligne['quantite']; ?></strong>
                                    </div>
                                </div>

                                <!-- Calcul prix -->
                                <div class="col-md-5">
                                    <div class="text-end">
                                        <div class="text-muted small mb-1">
                                            <?php echo number_format($ligne['prix_unitaire'], 0, ',', ' '); ?> Ar/jour
                                            × <?php echo $ligne['quantite']; ?>
                                            × <?php echo $nb_jours; ?>j
                                        </div>
                                        <div class="fw-bold text-gold fs-5">
                                            <?php echo number_format($subtotal, 0, ',', ' '); ?> Ar
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Informations client -->
            <div class="card card-gold mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user icon-gold"></i> Vos informations
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-user text-muted"></i> Nom complet:</strong><br>
                            <?php echo htmlspecialchars($reservation['prenom'] . ' ' . $reservation['nom']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-envelope text-muted"></i> Email:</strong><br>
                            <a href="mailto:<?php echo htmlspecialchars($reservation['email']); ?>">
                                <?php echo htmlspecialchars($reservation['email']); ?>
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-phone text-muted"></i> Téléphone:</strong><br>
                            <a href="tel:<?php echo htmlspecialchars($reservation['telephone']); ?>">
                                <?php echo htmlspecialchars($reservation['telephone']); ?>
                            </a>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong><i class="fas fa-calendar text-muted"></i> Date réservation:</strong><br>
                            <?php echo $date->format('d/m/Y à H:i'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Valeur matériel (caution info) -->
            <div class="card border-warning mb-4">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-shield-alt text-warning"></i> Valeur du matériel loué
                    </h6>
                    <?php foreach ($lignes as $ligne): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 small">
                            <span class="text-muted">
                                • <?php echo htmlspecialchars($ligne['nom_materiel']); ?>
                                <?php if ($ligne['quantite'] > 1): ?>
                                    <span class="badge bg-secondary"><?php echo $ligne['quantite']; ?>×</span>
                                <?php endif; ?>
                            </span>
                            <span class="text-warning fw-bold">
                                <?php echo number_format($ligne['prix_remboursement'], 0, ',', ' '); ?> Ar/unité
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <strong>Total valeur matériel:</strong>
                        <strong class="text-warning fs-5">
                            <?php echo number_format($valeur_materiel, 0, ',', ' '); ?> Ar
                        </strong>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle"></i>
                        En cas de perte ou casse, le prix de remplacement sera déduit de votre caution (25%).
                    </small>
                </div>
            </div>
        </div>

        <!-- Colonne Droite -->
        <div class="col-lg-4 mb-4">
            <!-- Détails paiement -->
            <div class="card card-gold mb-4 sticky-top" style="top: 20px;">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator icon-gold"></i> Détails paiement
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Total -->
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <div>
                            <strong>Total location</strong>
                            <br><small class="text-muted">Montant total</small>
                        </div>
                        <strong class="text-gold fs-4">
                            <?php echo number_format($reservation['montant_total'], 0, ',', ' '); ?> Ar
                        </strong>
                    </div>

                    <!-- Acompte -->
                    <div class="d-flex justify-content-between mb-2">
                        <span>Acompte versé (50%):</span>
                        <span class="text-primary fw-bold">
                            <?php echo number_format($reservation['acompte_verse'], 0, ',', ' '); ?> Ar
                        </span>
                    </div>

                    <!-- Caution -->
                    <div class="d-flex justify-content-between mb-3">
                        <span>Caution (25%):</span>
                        <span class="text-warning fw-bold">
                            <?php echo number_format($reservation['caution'], 0, ',', ' '); ?> Ar
                        </span>
                    </div>

                    <hr>

                    <!-- Reste -->
                    <div class="d-flex justify-content-between mb-0">
                        <strong>Reste à payer:</strong>
                        <strong class="text-danger fs-4">
                            <?php echo number_format($reservation['montant_restant'], 0, ',', ' '); ?> Ar
                        </strong>
                    </div>
                    <small class="text-muted d-block mt-1">
                        À régler lors de la récupération
                    </small>
                </div>
            </div>

            <!-- Statut & Actions -->
            <div class="card card-gold mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle icon-gold"></i> Statut & Actions
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($reservation['statut'] === 'en_attente'): ?>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-clock"></i>
                            <strong>En attente de validation</strong>
                            <p class="small mb-0 mt-2">
                                Notre équipe va valider votre réservation dans les plus brefs délais.
                                Vous serez contacté par email ou téléphone.
                            </p>
                        </div>
                    <?php elseif ($reservation['statut'] === 'confirme'): ?>
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-check-circle"></i>
                            <strong>Réservation confirmée!</strong>
                            <p class="small mb-0 mt-2">
                                Préparez le montant à régler: acompte (<?php echo number_format($reservation['acompte_verse'], 0, ',', ' '); ?> Ar)
                                + caution (<?php echo number_format($reservation['caution'], 0, ',', ' '); ?> Ar)
                            </p>
                        </div>
                    <?php elseif ($reservation['statut'] === 'en_cours'): ?>
                        <div class="alert alert-primary mb-3">
                            <i class="fas fa-hourglass-half"></i>
                            <strong>Location en cours</strong>
                            <p class="small mb-0 mt-2">
                                Profitez bien de votre matériel! Pensez à le retourner propre et en bon état.
                            </p>
                        </div>
                    <?php elseif ($reservation['statut'] === 'termine'): ?>
                        <div class="alert alert-secondary mb-3">
                            <i class="fas fa-flag-checkered"></i>
                            <strong>Location terminée</strong>
                            <p class="small mb-0 mt-2">
                                Merci pour votre confiance! Votre caution a été remboursée selon l'état du matériel retourné.
                            </p>
                        </div>
                    <?php elseif ($reservation['statut'] === 'annule'): ?>
                        <div class="alert alert-danger mb-3">
                            <i class="fas fa-times-circle"></i>
                            <strong>Réservation annulée</strong>
                            <p class="small mb-0 mt-2">
                                Cette réservation a été annulée. Pour plus d'informations, contactez-nous.
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Boutons actions -->
                    <div class="d-grid gap-2">
                        <a href="generer-pdf.php?id=<?php echo $reservation['id_reservation']; ?>"
                            class="btn btn-gold" target="_blank">
                            <i class="fas fa-file-pdf"></i> Télécharger Facture PDF
                        </a>
                        <button onclick="window.print()" class="btn btn-outline-gold">
                            <i class="fas fa-print"></i> Imprimer
                        </button>
                        <a href="contact.php" class="btn btn-outline-gold">
                            <i class="fas fa-envelope"></i> Contacter le support
                        </a>
                        <a href="mes-reservations.php" class="btn btn-gold">
                            <i class="fas fa-arrow-left"></i> Retour à mes réservations
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="card bg-light border-0">
                <div class="card-body text-center">
                    <h6><i class="fas fa-headset text-gold"></i> Besoin d'aide?</h6>
                    <p class="small mb-2">Contactez-nous</p>
                    <div class="small">
                        <i class="fas fa-phone text-gold"></i> 034 34 661 49<br>
                        <i class="fas fa-phone text-gold"></i> 032 52 500 60<br>
                        <i class="fas fa-envelope text-gold"></i> hello@plutina-events.com
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS pour impression -->
<style>
    @media print {

        .navbar,
        .breadcrumb,
        footer,
        .btn,
        button {
            display: none !important;
        }

        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            break-inside: avoid;
        }

        .sticky-top {
            position: relative !important;
            top: 0 !important;
        }

        body {
            background-color: white !important;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>