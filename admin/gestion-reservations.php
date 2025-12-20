<?php
require_once 'check_admin.php';
// Variables
$message = '';
$error = '';

// Traitement actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_reservation = (int)($_POST['id_reservation'] ?? 0);

    if ($id_reservation > 0) {
        try {
            if ($action === 'confirmer') {
                $stmt = $pdo->prepare("UPDATE reservation SET statut = 'confirme' WHERE id_reservation = ?");
                $stmt->execute([$id_reservation]);
                $message = "Réservation confirmée avec succès!";
            } elseif ($action === 'en_cours') {
                $stmt = $pdo->prepare("UPDATE reservation SET statut = 'en_cours' WHERE id_reservation = ?");
                $stmt->execute([$id_reservation]);
                $message = "Réservation marquée en cours!";
            } elseif ($action === 'terminer') {
                // Marquer terminé + remettre stock disponible
                $pdo->beginTransaction();

                // Update statut
                $stmt = $pdo->prepare("UPDATE reservation SET statut = 'termine' WHERE id_reservation = ?");
                $stmt->execute([$id_reservation]);

                // Remettre stock
                $stmt = $pdo->prepare("
                    SELECT id_materiel, quantite 
                    FROM ligne_reservation 
                    WHERE id_reservation = ?
                ");
                $stmt->execute([$id_reservation]);
                $lignes = $stmt->fetchAll();

                $stmt_update = $pdo->prepare("
                    UPDATE materiel 
                    SET quantite_disponible = quantite_disponible + ? 
                    WHERE id_materiel = ?
                ");

                foreach ($lignes as $ligne) {
                    $stmt_update->execute([$ligne['quantite'], $ligne['id_materiel']]);
                }

                $pdo->commit();
                $message = "Réservation terminée! Stock remis à jour.";
            } elseif ($action === 'annuler') {
                // Annuler + remettre stock
                $pdo->beginTransaction();

                // Update statut
                $stmt = $pdo->prepare("UPDATE reservation SET statut = 'annule' WHERE id_reservation = ?");
                $stmt->execute([$id_reservation]);

                // Remettre stock
                $stmt = $pdo->prepare("
                    SELECT id_materiel, quantite 
                    FROM ligne_reservation 
                    WHERE id_reservation = ?
                ");
                $stmt->execute([$id_reservation]);
                $lignes = $stmt->fetchAll();

                $stmt_update = $pdo->prepare("
                    UPDATE materiel 
                    SET quantite_disponible = quantite_disponible + ? 
                    WHERE id_materiel = ?
                ");

                foreach ($lignes as $ligne) {
                    $stmt_update->execute([$ligne['quantite'], $ligne['id_materiel']]);
                }

                $pdo->commit();
                $message = "Réservation annulée! Stock remis à jour.";
            }
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Erreur: " . $e->getMessage();
        }
    }
}

// Filtre
$filtre = $_GET['statut'] ?? 'tous';

// Récupérer réservations
if ($filtre === 'tous') {
    $stmt = $pdo->query("
        SELECT r.*, c.nom, c.prenom, c.email, c.telephone
        FROM reservation r
        JOIN client c ON r.id_client = c.id_client
        ORDER BY r.date_reservation DESC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom, c.prenom, c.email, c.telephone
        FROM reservation r
        JOIN client c ON r.id_client = c.id_client
        WHERE r.statut = ?
        ORDER BY r.date_reservation DESC
    ");
    $stmt->execute([$filtre]);
}
$reservations = $stmt->fetchAll();

// Stats par statut
$stmt = $pdo->query("
    SELECT statut, COUNT(*) as nb 
    FROM reservation 
    GROUP BY statut
");
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$page_title = "Gestion Réservations - Admin PLUTINA EVENT";
include 'header.php';
?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-calendar-check text-warning"></i> Gestion des Réservations
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

    <!-- Filtres -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="btn-group w-100" role="group">
                <a href="?statut=tous" class="btn <?php echo $filtre === 'tous' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-list"></i> Toutes
                    <span class="badge bg-dark"><?php echo count($reservations); ?></span>
                </a>
                <a href="?statut=en_attente" class="btn <?php echo $filtre === 'en_attente' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-clock"></i> En attente
                    <?php if (isset($stats['en_attente'])): ?>
                        <span class="badge bg-danger"><?php echo $stats['en_attente']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?statut=confirme" class="btn <?php echo $filtre === 'confirme' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-check"></i> Confirmé
                    <?php if (isset($stats['confirme'])): ?>
                        <span class="badge bg-success"><?php echo $stats['confirme']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?statut=en_cours" class="btn <?php echo $filtre === 'en_cours' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-play"></i> En cours
                    <?php if (isset($stats['en_cours'])): ?>
                        <span class="badge bg-primary"><?php echo $stats['en_cours']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?statut=termine" class="btn <?php echo $filtre === 'termine' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-check-circle"></i> Terminé
                    <?php if (isset($stats['termine'])): ?>
                        <span class="badge bg-secondary"><?php echo $stats['termine']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="?statut=annule" class="btn <?php echo $filtre === 'annule' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-times"></i> Annulé
                    <?php if (isset($stats['annule'])): ?>
                        <span class="badge bg-danger"><?php echo $stats['annule']; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Table réservations -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Numéro</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Acompte</th>
                            <th>Reste</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Aucune réservation avec ce statut
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $resa): ?>
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
                                        <strong class="text-primary">
                                            #<?php echo str_pad($resa['id_reservation'], 6, '0', STR_PAD_LEFT); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($resa['prenom'] . ' ' . $resa['nom']); ?></strong>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($resa['email']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <?php
                                            $date = new DateTime($resa['date_reservation']);
                                            echo $date->format('d/m/Y H:i');
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong class="text-warning">
                                            <?php echo number_format($resa['montant_total'], 0, ',', ' '); ?> Ar
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="text-success">
                                            <?php echo number_format($resa['acompte_verse'], 0, ',', ' '); ?> Ar
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-danger">
                                            <?php echo number_format($resa['montant_restant'], 0, ',', ' '); ?> Ar
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badge_class[$resa['statut']]; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $resa['statut'])); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Bouton détails -->
                                            <button type="button"
                                                class="btn btn-outline-info"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailModal<?php echo $resa['id_reservation']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <!-- Actions selon statut -->
                                            <?php if ($resa['statut'] === 'en_attente'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Confirmer cette réservation?')">
                                                    <input type="hidden" name="action" value="confirmer">
                                                    <input type="hidden" name="id_reservation" value="<?php echo $resa['id_reservation']; ?>">
                                                    <button type="submit" class="btn btn-outline-success" title="Confirmer">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($resa['statut'] === 'confirme'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Marquer en cours?')">
                                                    <input type="hidden" name="action" value="en_cours">
                                                    <input type="hidden" name="id_reservation" value="<?php echo $resa['id_reservation']; ?>">
                                                    <button type="submit" class="btn btn-outline-primary" title="En cours">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($resa['statut'] === 'en_cours'): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Marquer terminé? Le stock sera remis à jour.')">
                                                    <input type="hidden" name="action" value="terminer">
                                                    <input type="hidden" name="id_reservation" value="<?php echo $resa['id_reservation']; ?>">
                                                    <button type="submit" class="btn btn-outline-secondary" title="Terminer">
                                                        <i class="fas fa-flag-checkered"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- Annuler (sauf si déjà terminé/annulé) -->
                                            <?php if (!in_array($resa['statut'], ['termine', 'annule'])): ?>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('Annuler cette réservation? Le stock sera remis à jour.')">
                                                    <input type="hidden" name="action" value="annuler">
                                                    <input type="hidden" name="id_reservation" value="<?php echo $resa['id_reservation']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger" title="Annuler">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal Détails -->
                                <?php
                                // Récupérer lignes réservation
                                $stmt_lignes = $pdo->prepare("
                                    SELECT lr.*, m.nom_materiel, m.photo
                                    FROM ligne_reservation lr
                                    JOIN materiel m ON lr.id_materiel = m.id_materiel
                                    WHERE lr.id_reservation = ?
                                ");
                                $stmt_lignes->execute([$resa['id_reservation']]);
                                $lignes = $stmt_lignes->fetchAll();
                                ?>

                                <div class="modal fade" id="detailModal<?php echo $resa['id_reservation']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-file-invoice text-warning"></i>
                                                    Réservation #<?php echo str_pad($resa['id_reservation'], 6, '0', STR_PAD_LEFT); ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- Info client -->
                                                <h6 class="border-bottom pb-2 mb-3">
                                                    <i class="fas fa-user text-primary"></i> Informations client
                                                </h6>
                                                <div class="row mb-4">
                                                    <div class="col-md-6">
                                                        <strong>Nom:</strong> <?php echo htmlspecialchars($resa['prenom'] . ' ' . $resa['nom']); ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Email:</strong> <?php echo htmlspecialchars($resa['email']); ?>
                                                    </div>
                                                    <div class="col-md-6 mt-2">
                                                        <strong>Téléphone:</strong> <?php echo htmlspecialchars($resa['telephone']); ?>
                                                    </div>
                                                    <div class="col-md-6 mt-2">
                                                        <strong>Date réservation:</strong>
                                                        <?php
                                                        $date_resa = new DateTime($resa['date_reservation']);
                                                        echo $date_resa->format('d/m/Y à H:i');
                                                        ?>
                                                    </div>
                                                </div>

                                                <!-- Matériels -->
                                                <h6 class="border-bottom pb-2 mb-3">
                                                    <i class="fas fa-boxes text-info"></i> Matériels loués
                                                </h6>
                                                <div class="table-responsive mb-4">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Matériel</th>
                                                                <th>Dates</th>
                                                                <th>Qté</th>
                                                                <th>Prix/j</th>
                                                                <th>Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($lignes as $ligne): ?>
                                                                <?php
                                                                $debut = new DateTime($ligne['date_debut']);
                                                                $fin = new DateTime($ligne['date_fin']);
                                                                $interval = $debut->diff($fin);
                                                                $nb_jours = $interval->days + 1;
                                                                $total_ligne = $ligne['prix_unitaire'] * $ligne['quantite'] * $nb_jours;
                                                                ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($ligne['nom_materiel']); ?></td>
                                                                    <td>
                                                                        <small>
                                                                            <?php echo $debut->format('d/m'); ?> → <?php echo $fin->format('d/m'); ?>
                                                                            (<?php echo $nb_jours; ?>j)
                                                                        </small>
                                                                    </td>
                                                                    <td><?php echo $ligne['quantite']; ?></td>
                                                                    <td><?php echo number_format($ligne['prix_unitaire'], 0, ',', ' '); ?> Ar</td>
                                                                    <td><strong><?php echo number_format($total_ligne, 0, ',', ' '); ?> Ar</strong></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <!-- Montants -->
                                                <h6 class="border-bottom pb-2 mb-3">
                                                    <i class="fas fa-calculator text-success"></i> Détails paiement
                                                </h6>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span>Total location:</span>
                                                            <strong class="text-warning">
                                                                <?php echo number_format($resa['montant_total'], 0, ',', ' '); ?> Ar
                                                            </strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span>Acompte (50%):</span>
                                                            <strong class="text-success">
                                                                <?php echo number_format($resa['acompte_verse'], 0, ',', ' '); ?> Ar
                                                            </strong>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span>Caution (25%):</span>
                                                            <strong class="text-info">
                                                                <?php echo number_format($resa['caution'], 0, ',', ' '); ?> Ar
                                                            </strong>
                                                        </div>
                                                        <div class="d-flex justify-content-between mb-2">
                                                            <span>Reste à payer:</span>
                                                            <strong class="text-danger">
                                                                <?php echo number_format($resa['montant_restant'], 0, ',', ' '); ?> Ar
                                                            </strong>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="../generer-pdf.php?id=<?php echo $resa['id_reservation']; ?>"
                                                    class="btn btn-warning"
                                                    target="_blank">
                                                    <i class="fas fa-file-pdf"></i> Télécharger Facture PDF
                                                </a>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Fermer
                                                </button>
                                            </div>
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