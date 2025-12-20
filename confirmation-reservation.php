<?php
// Page confirmation réservation
require_once 'includes/config.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer ID réservation
$id_reservation = (int)($_GET['id'] ?? 0);

if ($id_reservation === 0) {
    header('Location: mon-compte.php');
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
    header('Location: mon-compte.php');
    exit;
}

// Récupérer lignes réservation (matériels)
$stmt = $pdo->prepare("
    SELECT lr.*, m.nom_materiel, m.photo
    FROM ligne_reservation lr
    JOIN materiel m ON lr.id_materiel = m.id_materiel
    WHERE lr.id_reservation = ?
");
$stmt->execute([$id_reservation]);
$lignes = $stmt->fetchAll();

$page_title = "Confirmation Réservation - PLUTINA EVENT";
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-light py-2">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="mon-compte.php">Mon Compte</a></li>
                <li class="breadcrumb-item active">Confirmation</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Confirmation -->
<div class="container my-5">
    <!-- Message succès -->
    <div class="row justify-content-center mb-4">
        <div class="col-lg-8">
            <div class="card card-gold border-success">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h1 class="text-success mb-3">Réservation enregistrée!</h1>
                    <p class="lead mb-4">
                        Votre demande de réservation a été enregistrée avec succès.
                    </p>
                    <div class="alert alert-info d-inline-block">
                        <strong>Numéro de réservation:</strong>
                        <span class="badge bg-gold fs-5 ms-2">#<?php echo str_pad($reservation['id_reservation'], 6, '0', STR_PAD_LEFT); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Récapitulatif réservation -->
        <div class="col-lg-8">
            <!-- Matériels réservés -->
            <div class="card card-gold mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-box-open icon-gold"></i> Matériels réservés
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($lignes as $ligne): ?>
                        <div class="border-bottom p-3">
                            <div class="row align-items-center">
                                <!-- Image -->
                                <div class="col-md-2">
                                    <?php
                                    $image_path = 'images/' . $ligne['photo'];
                                    if (file_exists($image_path)): ?>
                                        <img src="<?php echo htmlspecialchars($image_path); ?>"
                                            class="img-fluid rounded"
                                            alt="<?php echo htmlspecialchars($ligne['nom_materiel']); ?>">
                                    <?php else: ?>
                                        <div class="bg-light text-center p-3 rounded">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Info -->
                                <div class="col-md-6">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($ligne['nom_materiel']); ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        <?php
                                        $debut = new DateTime($ligne['date_debut']);
                                        $fin = new DateTime($ligne['date_fin']);
                                        $interval = $debut->diff($fin);
                                        $nb_jours = $interval->days + 1;
                                        echo $debut->format('d/m/Y') . ' → ' . $fin->format('d/m/Y');
                                        ?>
                                        (<?php echo $nb_jours; ?> jour<?php echo $nb_jours > 1 ? 's' : ''; ?>)
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-sort-numeric-up"></i> Quantité: <?php echo $ligne['quantite']; ?>
                                    </small>
                                </div>

                                <!-- Prix -->
                                <div class="col-md-4 text-end">
                                    <div class="text-muted small">
                                        <?php echo number_format($ligne['prix_unitaire'], 0, ',', ' '); ?> Ar ×
                                        <?php echo $ligne['quantite']; ?> ×
                                        <?php echo $nb_jours; ?>j
                                    </div>
                                    <div class="fw-bold text-gold">
                                        <?php
                                        $subtotal = $ligne['prix_unitaire'] * $ligne['quantite'] * $nb_jours;
                                        echo number_format($subtotal, 0, ',', ' ');
                                        ?> Ar
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
                            <strong>Nom complet:</strong><br>
                            <?php echo htmlspecialchars($reservation['prenom'] . ' ' . $reservation['nom']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Email:</strong><br>
                            <?php echo htmlspecialchars($reservation['email']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Téléphone:</strong><br>
                            <?php echo htmlspecialchars($reservation['telephone']); ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Date réservation:</strong><br>
                            <?php
                            $date = new DateTime($reservation['date_reservation']);
                            echo $date->format('d/m/Y à H:i');
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails paiement -->
        <div class="col-lg-4">
            <!-- Montants -->
            <div class="card card-gold mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator icon-gold"></i> Détails paiement
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Total -->
                    <div class="d-flex justify-content-between mb-3 pb-3 border-bottom">
                        <strong>Total location:</strong>
                        <strong class="text-gold">
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
                        <strong class="text-danger fs-5">
                            <?php echo number_format($reservation['montant_restant'], 0, ',', ' '); ?> Ar
                        </strong>
                    </div>
                    <small class="text-muted d-block mt-1">
                        À régler lors de la récupération
                    </small>
                </div>
            </div>

            <!-- Statut -->
            <div class="card card-gold mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle icon-gold"></i> Statut
                    </h5>
                </div>
                <div class="card-body text-center">
                    <span class="badge bg-warning text-dark fs-6 px-4 py-2">
                        <i class="fas fa-clock"></i> En attente de validation
                    </span>
                    <p class="text-muted small mt-3 mb-0">
                        Notre équipe va valider votre réservation dans les plus brefs délais.
                    </p>
                </div>
            </div>

            <!-- Prochaines étapes -->
            <div class="card card-gold">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks icon-gold"></i> Prochaines étapes
                    </h5>
                </div>
                <div class="card-body">
                    <ol class="small mb-0 ps-3">
                        <li class="mb-2">Notre équipe va valider votre réservation</li>
                        <li class="mb-2">Vous recevrez une confirmation par email/téléphone</li>
                        <li class="mb-2">Préparez le montant à régler (acompte + caution)</li>
                        <li class="mb-0">Récupération du matériel aux dates convenues</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Boutons actions -->
    <div class="row mt-4">
        <div class="col-12 text-center">
            <a href="mes-reservations.php" class="btn btn-gold btn-lg me-2">
                <i class="fas fa-list"></i> Voir mes réservations
            </a>
            <a href="catalogue.php" class="btn btn-outline-gold btn-lg">
                <i class="fas fa-shopping-bag"></i> Continuer mes achats
            </a>
        </div>
    </div>

    <!-- Contact -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info text-center">
                <i class="fas fa-phone"></i>
                <strong>Besoin d'aide?</strong>
                Contactez-nous au <strong>034 34 661 49 / 032 52 500 60</strong>
                ou par email: <strong>hello@plutina-events.com</strong>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>