<?php
require_once 'check_admin.php';

// Statistiques
// Total réservations
$stmt = $pdo->query("SELECT COUNT(*) FROM reservation");
$total_reservations = $stmt->fetchColumn();

// Réservations par statut
$stmt = $pdo->query("
    SELECT statut, COUNT(*) as nb 
    FROM reservation 
    GROUP BY statut
");
$stats_statut = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Total clients
$stmt = $pdo->query("SELECT COUNT(*) FROM client WHERE role = 'client'");
$total_clients = $stmt->fetchColumn();

// Revenus total (montant_total de toutes réservations confirmées/terminées)
$stmt = $pdo->query("
    SELECT COALESCE(SUM(montant_total), 0) 
    FROM reservation 
    WHERE statut IN ('confirme', 'en_cours', 'termine')
");
$revenus_total = $stmt->fetchColumn();

// Revenus ce mois
$stmt = $pdo->query("
    SELECT COALESCE(SUM(montant_total), 0) 
    FROM reservation 
    WHERE statut IN ('confirme', 'en_cours', 'termine')
    AND MONTH(date_reservation) = MONTH(CURRENT_DATE())
    AND YEAR(date_reservation) = YEAR(CURRENT_DATE())
");
$revenus_mois = $stmt->fetchColumn();

// Matériels total
$stmt = $pdo->query("SELECT COUNT(*) FROM materiel");
$total_materiels = $stmt->fetchColumn();

// Matériels les plus loués (top 5)
$stmt = $pdo->query("
    SELECT m.nom_materiel, SUM(lr.quantite) as total_loue
    FROM ligne_reservation lr
    JOIN materiel m ON lr.id_materiel = m.id_materiel
    GROUP BY m.id_materiel
    ORDER BY total_loue DESC
    LIMIT 5
");
$top_materiels = $stmt->fetchAll();

// Dernières réservations (5)
$stmt = $pdo->query("
    SELECT r.*, c.nom, c.prenom
    FROM reservation r
    JOIN client c ON r.id_client = c.id_client
    ORDER BY r.date_reservation DESC
    LIMIT 5
");
$dernieres_reservations = $stmt->fetchAll();

$page_title = "Dashboard Admin - PLUTINA EVENT";
include 'header.php';
?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-chart-line text-warning"></i> Dashboard Administrateur
            </h1>
            <p class="text-muted">Vue d'ensemble de votre activité</p>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="gestion-reservations.php" class="btn btn-warning">
                    <i class="fas fa-calendar-check"></i> Réservations
                </a>
                <a href="gestion-materiels.php" class="btn btn-outline-warning">
                    <i class="fas fa-boxes"></i> Matériels
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row 1 -->
    <div class="row mb-4">
        <!-- Total Réservations -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Réservations</p>
                            <h2 class="mb-0"><?php echo $total_reservations; ?></h2>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- En attente -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">En attente</p>
                            <h2 class="mb-0 text-warning">
                                <?php echo $stats_statut['en_attente'] ?? 0; ?>
                            </h2>
                        </div>
                        <div class="text-warning" style="font-size: 2.5rem;">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <a href="gestion-reservations.php?statut=en_attente" class="btn btn-sm btn-warning mt-2 w-100">
                        <i class="fas fa-arrow-right"></i> Traiter
                    </a>
                </div>
            </div>
        </div>

        <!-- Total Clients -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Clients</p>
                            <h2 class="mb-0"><?php echo $total_clients; ?></h2>
                        </div>
                        <div class="text-success" style="font-size: 2.5rem;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matériels -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Matériels</p>
                            <h2 class="mb-0"><?php echo $total_materiels; ?></h2>
                        </div>
                        <div class="text-info" style="font-size: 2.5rem;">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards Row 2 - Revenus -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-3">
            <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #D4AF37 0%, #B8941E 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 small opacity-75">Revenus Total</p>
                            <h2 class="mb-0"><?php echo number_format($revenus_total, 0, ',', ' '); ?> Ar</h2>
                        </div>
                        <div style="font-size: 2.5rem; opacity: 0.8;">
                            <i class="fas fa-coins"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card border-0 shadow-sm bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 small opacity-75">Revenus ce mois</p>
                            <h2 class="mb-0"><?php echo number_format($revenus_mois, 0, ',', ' '); ?> Ar</h2>
                        </div>
                        <div style="font-size: 2.5rem; opacity: 0.8;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Dernières réservations -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history text-warning"></i> Dernières réservations
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Numéro</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dernieres_reservations)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox"></i> Aucune réservation
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dernieres_reservations as $resa): ?>
                                        <?php
                                        $badge_class = [
                                            'en_attente' => 'bg-warning text-dark',
                                            'confirme' => 'bg-success',
                                            'en_cours' => 'bg-primary',
                                            'termine' => 'bg-secondary',
                                            'annule' => 'bg-danger'
                                        ];
                                        ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo str_pad($resa['id_reservation'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($resa['prenom'] . ' ' . $resa['nom']); ?></td>
                                            <td>
                                                <small>
                                                    <?php
                                                    $date = new DateTime($resa['date_reservation']);
                                                    echo $date->format('d/m/Y');
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($resa['montant_total'], 0, ',', ' '); ?> Ar</strong>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $badge_class[$resa['statut']]; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $resa['statut'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="gestion-reservations.php?id=<?php echo $resa['id_reservation']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light text-center">
                    <a href="gestion-reservations.php" class="btn btn-sm btn-warning">
                        <i class="fas fa-list"></i> Voir toutes les réservations
                    </a>
                </div>
            </div>
        </div>

        <!-- Top matériels loués -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-star text-warning"></i> Top Matériels
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_materiels)): ?>
                        <p class="text-center text-muted">
                            <i class="fas fa-inbox"></i><br>Aucune location
                        </p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($top_materiels as $index => $mat): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <span class="badge bg-warning text-dark me-2"><?php echo $index + 1; ?></span>
                                        <?php echo htmlspecialchars($mat['nom_materiel']); ?>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo $mat['total_loue']; ?>× loué
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light text-center">
                    <a href="gestion-materiels.php" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-boxes"></i> Gérer matériels
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Messages Contact -->
<div class="col-lg-3 col-md-6 mb-4">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1 small">Messages non lus</p>
                    <h2 class="mb-0">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE lu = FALSE");
                        echo $stmt->fetchColumn();
                        ?>
                    </h2>
                </div>
                <div class="text-danger" style="font-size: 2.5rem;">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            <a href="messages-contact.php" class="btn btn-sm btn-warning mt-3">
                <i class="fas fa-inbox"></i> Voir messages
            </a>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>