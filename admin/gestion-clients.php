<?php
require_once 'check_admin.php';

// Variables
$message = '';
$error = '';
$search = $_GET['search'] ?? '';

// Traitement suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id_client = (int)($_POST['id_client'] ?? 0);

    if ($action === 'supprimer' && $id_client > 0) {
        try {
            // Vérifier réservations actives
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM reservation 
                WHERE id_client = ? 
                AND statut IN ('en_attente', 'confirme', 'en_cours')
            ");
            $stmt->execute([$id_client]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Impossible de supprimer: client a $count réservation(s) active(s).";
            } else {
                // Supprimer client
                $stmt = $pdo->prepare("DELETE FROM client WHERE id_client = ? AND role = 'client'");
                $stmt->execute([$id_client]);
                $message = "Client supprimé avec succès!";
            }
        } catch (Exception $e) {
            $error = "Erreur: " . $e->getMessage();
        }
    }
}

// Récupérer clients avec stats
if ($search) {
    $stmt = $pdo->prepare("
        SELECT c.*,
            COUNT(DISTINCT r.id_reservation) as nb_reservations,
            COALESCE(SUM(CASE WHEN r.statut IN ('confirme', 'en_cours', 'termine') THEN r.montant_total ELSE 0 END), 0) as total_depense,
            MAX(r.date_reservation) as derniere_reservation
        FROM client c
        LEFT JOIN reservation r ON c.id_client = r.id_client
        WHERE c.role = 'client'
        AND (c.nom LIKE ? OR c.prenom LIKE ? OR c.email LIKE ? OR c.telephone LIKE ?)
        GROUP BY c.id_client
        ORDER BY c.date_inscription DESC
    ");
    $search_term = '%' . $search . '%';
    $stmt->execute([$search_term, $search_term, $search_term, $search_term]);
} else {
    $stmt = $pdo->query("
        SELECT c.*,
            COUNT(DISTINCT r.id_reservation) as nb_reservations,
            COALESCE(SUM(CASE WHEN r.statut IN ('confirme', 'en_cours', 'termine') THEN r.montant_total ELSE 0 END), 0) as total_depense,
            MAX(r.date_reservation) as derniere_reservation
        FROM client c
        LEFT JOIN reservation r ON c.id_client = r.id_client
        WHERE c.role = 'client'
        GROUP BY c.id_client
        ORDER BY c.date_inscription DESC
    ");
}
$clients = $stmt->fetchAll();

// Stats globales
$stmt = $pdo->query("SELECT COUNT(*) FROM client WHERE role = 'client'");
$total_clients = $stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT COUNT(*) FROM client c
    WHERE role = 'client'
    AND EXISTS (
        SELECT 1 FROM reservation r 
        WHERE r.id_client = c.id_client 
        AND r.date_reservation >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    )
");
$clients_actifs = $stmt->fetchColumn();

$page_title = "Gestion Clients - Admin PLUTINA EVENT";
include 'header.php';
?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-users text-warning"></i> Gestion des Clients
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Retour Dashboard
            </a>
        </div>
    </div>

    <!-- Messages -->
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

    <!-- Stats cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Clients</p>
                            <h2 class="mb-0"><?php echo $total_clients; ?></h2>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem;">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Clients Actifs (30j)</p>
                            <h2 class="mb-0 text-success"><?php echo $clients_actifs; ?></h2>
                        </div>
                        <div class="text-success" style="font-size: 2.5rem;">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="Rechercher par nom, email ou téléphone..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </form>
            <?php if ($search): ?>
                <div class="mt-2">
                    <a href="gestion-clients.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i> Effacer recherche
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Table clients -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Inscription</th>
                            <th>Réservations</th>
                            <th>Total Dépensé</th>
                            <th>Activité</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    <?php if ($search): ?>
                                        Aucun client trouvé pour "<?php echo htmlspecialchars($search); ?>"
                                    <?php else: ?>
                                        Aucun client enregistré
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clients as $client): ?>
                                <?php
                                // Badge activité
                                $days_since = null;
                                $badge_class = 'secondary';
                                $badge_text = 'Nouveau';

                                if ($client['derniere_reservation']) {
                                    $last = new DateTime($client['derniere_reservation']);
                                    $now = new DateTime();
                                    $days_since = $now->diff($last)->days;

                                    if ($days_since <= 7) {
                                        $badge_class = 'success';
                                        $badge_text = 'Très actif';
                                    } elseif ($days_since <= 30) {
                                        $badge_class = 'primary';
                                        $badge_text = 'Actif';
                                    } elseif ($days_since <= 90) {
                                        $badge_class = 'warning';
                                        $badge_text = 'Modéré';
                                    } else {
                                        $badge_class = 'secondary';
                                        $badge_text = 'Inactif';
                                    }
                                } elseif ($client['nb_reservations'] == 0) {
                                    $badge_class = 'info';
                                    $badge_text = 'Nouveau';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></strong>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-hashtag"></i><?php echo $client['id_client']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <i class="fas fa-envelope text-muted"></i>
                                            <?php echo htmlspecialchars($client['email']); ?>
                                        </div>
                                        <div class="small">
                                            <i class="fas fa-phone text-muted"></i>
                                            <?php echo htmlspecialchars($client['telephone']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small>
                                            <?php
                                            $date = new DateTime($client['date_inscription']);
                                            echo $date->format('d/m/Y');
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($client['nb_reservations'] > 0): ?>
                                            <span class="badge bg-primary">
                                                <?php echo $client['nb_reservations']; ?> résa
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Aucune</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($client['total_depense'] > 0): ?>
                                            <strong class="text-warning">
                                                <?php echo number_format($client['total_depense'], 0, ',', ' '); ?> Ar
                                            </strong>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $badge_class; ?>">
                                            <?php echo $badge_text; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Voir réservations -->
                                            <?php if ($client['nb_reservations'] > 0): ?>
                                                <button type="button"
                                                    class="btn btn-outline-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#reservationsModal<?php echo $client['id_client']; ?>"
                                                    title="Voir réservations">
                                                    <i class="fas fa-calendar"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Supprimer -->
                                            <button type="button"
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#supprimerModal<?php echo $client['id_client']; ?>"
                                                title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal Réservations -->
                                <?php if ($client['nb_reservations'] > 0): ?>
                                    <?php
                                    $stmt_resa = $pdo->prepare("
                                        SELECT * FROM reservation 
                                        WHERE id_client = ? 
                                        ORDER BY date_reservation DESC
                                    ");
                                    $stmt_resa->execute([$client['id_client']]);
                                    $reservations = $stmt_resa->fetchAll();
                                    ?>

                                    <div class="modal fade" id="reservationsModal<?php echo $client['id_client']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-calendar-alt text-primary"></i>
                                                        Réservations - <?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Numéro</th>
                                                                    <th>Date</th>
                                                                    <th>Montant</th>
                                                                    <th>Statut</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($reservations as $resa): ?>
                                                                    <?php
                                                                    $badge_class_resa = [
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
                                                                        <td>
                                                                            <small>
                                                                                <?php
                                                                                $date_resa = new DateTime($resa['date_reservation']);
                                                                                echo $date_resa->format('d/m/Y');
                                                                                ?>
                                                                            </small>
                                                                        </td>
                                                                        <td>
                                                                            <strong><?php echo number_format($resa['montant_total'], 0, ',', ' '); ?> Ar</strong>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge <?php echo $badge_class_resa[$resa['statut']]; ?>">
                                                                                <?php echo ucfirst(str_replace('_', ' ', $resa['statut'])); ?>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                            <tfoot>
                                                                <tr class="table-light">
                                                                    <td colspan="2"><strong>Total</strong></td>
                                                                    <td colspan="2">
                                                                        <strong class="text-warning">
                                                                            <?php echo number_format($client['total_depense'], 0, ',', ' '); ?> Ar
                                                                        </strong>
                                                                    </td>
                                                                </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <a href="gestion-reservations.php" class="btn btn-primary">
                                                        <i class="fas fa-calendar-check"></i> Voir toutes réservations
                                                    </a>
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Modal Supprimer -->
                                <div class="modal fade" id="supprimerModal<?php echo $client['id_client']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="supprimer">
                                                <input type="hidden" name="id_client" value="<?php echo $client['id_client']; ?>">

                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-exclamation-triangle"></i> Confirmer suppression
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <p>Êtes-vous sûr de vouloir supprimer ce client?</p>
                                                    <div class="alert alert-warning">
                                                        <strong><?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?></strong><br>
                                                        <small><?php echo htmlspecialchars($client['email']); ?></small>
                                                    </div>

                                                    <?php if ($client['nb_reservations'] > 0): ?>
                                                        <div class="alert alert-info">
                                                            <i class="fas fa-info-circle"></i>
                                                            Ce client a <strong><?php echo $client['nb_reservations']; ?> réservation(s)</strong>.
                                                            <br>Suppression possible uniquement si aucune réservation active.
                                                        </div>
                                                    <?php endif; ?>

                                                    <p class="text-danger small mb-0">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        Cette action est irréversible.
                                                    </p>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-danger">
                                                        <i class="fas fa-trash"></i> Supprimer
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>