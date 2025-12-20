<?php
// Page mon compte
require_once 'includes/config.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Récupérer infos utilisateur
$stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    // Si utilisateur n'existe pas, déconnecter
    session_destroy();
    header('Location: connexion.php');
    exit;
}

// Récupérer nombre de réservations
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reservation WHERE id_client = ?");
$stmt->execute([$_SESSION['user_id']]);
$nb_reservations = $stmt->fetchColumn();

$page_title = "Mon Compte - PLUTINA EVENT";
include 'includes/header.php';
?>
<!-- Mon Compte -->
<div class="container my-5">
    <!-- Message Bienvenue -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-gold">
                <div class="card-body text-center py-4">
                    <h1 class="display-5 mb-3">
                        <i class="fas fa-user-circle icon-gold"></i>
                        Bienvenue, <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>!
                    </h1>
                    <p class="lead mb-0 text-muted">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 mb-4">
            <div class="card card-gold">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bars icon-gold"></i> Menu</h5>
                </div>
                <div class="list-group list-group-flush">
                    <a href="mon-compte.php" class="list-group-item list-group-item-action active">
                        <i class="fas fa-home"></i> Tableau de bord
                    </a>
                    <a href="mes-reservations.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-alt"></i> Mes réservations
                        <?php if ($nb_reservations > 0): ?>
                            <span class="badge bg-gold float-end"><?php echo $nb_reservations; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="mon-profil.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-edit"></i> Mon profil
                    </a>
                    <a href="catalogue.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-shopping-bag"></i> Louer du matériel
                    </a>
                    <hr class="my-0">
                    <a href="deconnexion.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </div>
            </div>
        </div>

        <!-- Contenu Principal -->
        <div class="col-md-9">
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card card-gold h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-3x text-gold mb-3"></i>
                            <h3 class="mb-0"><?php echo $nb_reservations; ?></h3>
                            <p class="text-muted mb-0">Réservation(s)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-gold h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                            <h3 class="mb-0">0</h3>
                            <p class="text-muted mb-0">En cours</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-gold h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h3 class="mb-0">0</h3>
                            <p class="text-muted mb-0">Terminée(s)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations Compte -->
            <div class="card card-gold mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle icon-gold"></i> Informations du compte</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Nom complet</label>
                            <p class="fw-bold mb-0">
                                <?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Email</label>
                            <p class="fw-bold mb-0">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Téléphone</label>
                            <p class="fw-bold mb-0">
                                <?php echo htmlspecialchars($user['telephone']); ?>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Membre depuis</label>
                            <p class="fw-bold mb-0">
                                <?php
                                $date = new DateTime($user['date_inscription']);
                                echo $date->format('d/m/Y');
                                ?>
                            </p>
                        </div>
                    </div>
                    <hr>
                    <a href="mon-profil.php" class="btn btn-outline-gold">
                        <i class="fas fa-edit"></i> Modifier mon profil
                    </a>
                </div>
            </div>

            <!-- Réservations récentes -->
            <div class="card card-gold">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history icon-gold"></i> Réservations récentes</h5>
                </div>
                <div class="card-body">
                    <?php if ($nb_reservations == 0): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Aucune réservation pour le moment</h5>
                            <p class="text-muted mb-4">Commencez à louer du matériel pour vos événements!</p>
                            <a href="catalogue.php" class="btn btn-gold">
                                <i class="fas fa-shopping-bag"></i> Voir le catalogue
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Vous avez <?php echo $nb_reservations; ?> réservation(s).
                            <a href="mes-reservations.php" class="alert-link">Voir toutes mes réservations</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS custom pour badge -->
<style>
    .bg-gold {
        background-color: #D4AF37 !important;
        color: white !important;
    }

    .list-group-item.active {
        background-color: #FFF9E6;
        border-color: #D4AF37;
        color: #333;
    }
</style>

<?php include 'includes/footer.php'; ?>