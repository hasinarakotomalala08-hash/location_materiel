<?php
// Page mes réservations
require_once 'includes/config.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Filtre par statut (optionnel)
$filtre_statut = $_GET['statut'] ?? 'tous';

// Requête réservations
if ($filtre_statut === 'tous') {
    $stmt = $pdo->prepare("
        SELECT * FROM reservation 
        WHERE id_client = ? 
        ORDER BY date_reservation DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM reservation 
        WHERE id_client = ? AND statut = ? 
        ORDER BY date_reservation DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $filtre_statut]);
}

$reservations = $stmt->fetchAll();

// Compter par statut
$stmt = $pdo->prepare("
    SELECT statut, COUNT(*) as nb 
    FROM reservation 
    WHERE id_client = ? 
    GROUP BY statut
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$page_title = "Mes Réservations - PLUTINA EVENT";
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-light py-2">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="mon-compte.php">Mon Compte</a></li>
                <li class="breadcrumb-item active">Mes Réservations</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Mes Réservations -->
<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-calendar-alt icon-gold"></i> Mes Réservations
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="catalogue.php" class="btn btn-gold">
                <i class="fas fa-plus"></i> Nouvelle réservation
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card card-gold mb-4">
        <div class="card-body">
            <div class="btn-group w-100" role="group">
                <a href="?statut=tous"
                    class="btn <?php echo $filtre_statut === 'tous' ? 'btn-gold' : 'btn-outline-gold'; ?>">
                    <i class="fas fa-list"></i> Toutes
                    <?php if (count($reservations) > 0): ?>
                        <span class="badge bg-dark"><?php echo count($reservations); ?></span>
                    <?php endif; ?>
                </a>
                <a href="?statut=en_attente"
                    class="btn <?php echo $filtre_statut === 'en_attente' ? 'btn-gold' : 'btn-outline-gold'; ?>">
                    <i class="fas fa-clock"></i> En attente
                    <?php if (isset($stats['en_attente'])): ?>
                        <span class="badge bg-warning"><?php echo $stats['en_attente']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?statut=confirme"
                    class="btn <?php echo $filtre_statut === 'confirme' ? 'btn-gold' : 'btn-outline-gold'; ?>">
                    <i class="fas fa-check"></i> Confirmé
                    <?php if (isset($stats['confirme'])): ?>
                        <span class="badge bg-success"><?php echo $stats['confirme']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?statut=en_cours"
                    class="btn <?php echo $filtre_statut === 'en_cours' ? 'btn-gold' : 'btn-outline-gold'; ?>">
                    <i class="fas fa-play"></i> En cours
                    <?php if (isset($stats['en_cours'])): ?>
                        <span class="badge bg-primary"><?php echo $stats['en_cours']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?statut=termine"
                    class="btn <?php echo $filtre_statut === 'termine' ? 'btn-gold' : 'btn-outline-gold'; ?>">
                    <i class="fas fa-check-circle"></i> Terminé
                    <?php if (isset($stats['termine'])): ?>
                        <span class="badge bg-secondary"><?php echo $stats['termine']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (empty($reservations)): ?>
        <!-- Aucune réservation -->
        <div class="card card-gold">
            <div class="card-body text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h3 class="text-muted">
                    <?php if ($filtre_statut === 'tous'): ?>
                        Aucune réservation pour le moment
                    <?php else: ?>
                        Aucune réservation avec le statut "<?php echo ucfirst(str_replace('_', ' ', $filtre_statut)); ?>"
                    <?php endif; ?>
                </h3>
                <p class="text-muted mb-4">Commencez à louer du matériel pour vos événements!</p>
                <a href="catalogue.php" class="btn btn-gold btn-lg">
                    <i class="fas fa-shopping-bag"></i> Voir le catalogue
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Liste réservations -->
        <?php foreach ($reservations as $reservation): ?>
            <?php
            // Récupérer lignes réservation
            $stmt = $pdo->prepare("
                SELECT lr.*, m.nom_materiel, m.photo
                FROM ligne_reservation lr
                JOIN materiel m ON lr.id_materiel = m.id_materiel
                WHERE lr.id_reservation = ?
            ");
            $stmt->execute([$reservation['id_reservation']]);
            $lignes = $stmt->fetchAll();

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

            <div class="card card-gold mb-3">
                <div class="card-header bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h5 class="mb-0">
                                <i class="fas fa-hashtag icon-gold"></i>
                                <?php echo str_pad($reservation['id_reservation'], 6, '0', STR_PAD_LEFT); ?>
                            </h5>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i>
                                <?php
                                $date = new DateTime($reservation['date_reservation']);
                                echo $date->format('d/m/Y à H:i');
                                ?>
                            </small>
                        </div>
                        <div class="col-md-3 text-center">
                            <?php
                            // Couleurs inline
                            $badge_style = [
                                'en_attente' => 'background-color: #ffc107; color: #000;',
                                'confirme' => 'background-color: #198754; color: white;',
                                'en_cours' => 'background-color: #0d6efd; color: white;',
                                'termine' => 'background-color: #6c757d; color: white;',
                                'annule' => 'background-color: #dc3545; color: white;'
                            ];
                            ?>

                            <span class="badge px-3 py-2" style="<?php echo $badge_style[$reservation['statut']]; ?>">
                                <i class="fas <?php echo $badge_icon[$reservation['statut']]; ?>"></i>
                                <?php echo $statut_label; ?>
                            </span>
                            <i class="fas <?php echo $badge_icon[$reservation['statut']]; ?>"></i>
                            <?php echo $statut_label; ?>
                            </span>
                        </div>
                        <div class="col-md-3 text-end">
                            <strong class="text-gold">
                                <?php echo number_format($reservation['montant_total'], 0, ',', ' '); ?> Ar
                            </strong>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Matériels -->
                        <div class="col-md-8">
                            <h6 class="mb-3">
                                <i class="fas fa-box-open icon-gold"></i> Matériels loués
                                <span class="badge bg-gold"><?php echo count($lignes); ?></span>
                            </h6>
                            <div class="row">
                                <?php foreach (array_slice($lignes, 0, 3) as $ligne): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $image_path = 'images/' . $ligne['photo'];
                                            if (file_exists($image_path)): ?>
                                                <img src="<?php echo htmlspecialchars($image_path); ?>"
                                                    class="rounded me-2"
                                                    style="width: 40px; height: 40px; object-fit: cover;"
                                                    alt="<?php echo htmlspecialchars($ligne['nom_materiel']); ?>">
                                            <?php else: ?>
                                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center"
                                                    style="width: 40px; height: 40px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="small">
                                                <div class="fw-bold text-truncate" style="max-width: 120px;">
                                                    <?php echo htmlspecialchars($ligne['nom_materiel']); ?>
                                                </div>
                                                <small class="text-muted">×<?php echo $ligne['quantite']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($lignes) > 3): ?>
                                    <div class="col-md-12">
                                        <small class="text-muted">
                                            + <?php echo count($lignes) - 3; ?> autre(s) matériel(s)...
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Infos paiement -->
                        <div class="col-md-4">
                            <h6 class="mb-3">
                                <i class="fas fa-calculator icon-gold"></i> Détails paiement
                            </h6>
                            <div class="small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Acompte versé:</span>
                                    <span class="text-primary fw-bold">
                                        <?php echo number_format($reservation['acompte_verse'], 0, ',', ' '); ?> Ar
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Caution:</span>
                                    <span class="text-warning fw-bold">
                                        <?php echo number_format($reservation['caution'], 0, ',', ' '); ?> Ar
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Reste à payer:</span>
                                    <span class="text-danger fw-bold">
                                        <?php echo number_format($reservation['montant_restant'], 0, ',', ' '); ?> Ar
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <?php if ($reservation['statut'] === 'en_attente'): ?>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    En attente de validation par notre équipe
                                </small>
                            <?php elseif ($reservation['statut'] === 'confirme'): ?>
                                <small class="text-success">
                                    <i class="fas fa-check-circle"></i>
                                    Réservation confirmée - Préparez le paiement
                                </small>
                            <?php elseif ($reservation['statut'] === 'en_cours'): ?>
                                <small class="text-primary">
                                    <i class="fas fa-hourglass-half"></i>
                                    Location en cours
                                </small>
                            <?php elseif ($reservation['statut'] === 'termine'): ?>
                                <small class="text-secondary">
                                    <i class="fas fa-flag-checkered"></i>
                                    Terminé
                                </small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <a href="detail-reservation.php?id=<?php echo $reservation['id_reservation']; ?>"
                                class="btn btn-sm btn-outline-gold">
                                <i class="fas fa-eye"></i> Voir détails
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>