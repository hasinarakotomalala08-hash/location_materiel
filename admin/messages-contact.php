<?php
require_once 'check_admin.php';

// Marquer comme lu
if (isset($_GET['marquer_lu'])) {
    $id = (int)$_GET['marquer_lu'];
    $stmt = $pdo->prepare("
        UPDATE contact_messages 
        SET lu = TRUE, date_lecture = NOW() 
        WHERE id_message = ?
    ");
    $stmt->execute([$id]);
    header('Location: messages-contact.php');
    exit;
}

// Supprimer message
if (isset($_POST['supprimer'])) {
    $id = (int)$_POST['id_message'];
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id_message = ?");
    $stmt->execute([$id]);
}

// Récupérer messages
$filtre = $_GET['filtre'] ?? 'tous';

if ($filtre === 'non_lus') {
    $stmt = $pdo->query("
        SELECT * FROM contact_messages 
        WHERE lu = FALSE 
        ORDER BY date_envoi DESC
    ");
} else {
    $stmt = $pdo->query("
        SELECT * FROM contact_messages 
        ORDER BY date_envoi DESC
    ");
}
$messages = $stmt->fetchAll();

// Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE lu = FALSE");
$nb_non_lus = $stmt->fetchColumn();

$page_title = "Messages Contact - Admin PLUTINA EVENT";
include 'header.php';
?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-inbox text-warning"></i> Messages Contact
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Retour Dashboard
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Messages non lus</p>
                            <h2 class="mb-0 text-danger"><?php echo $nb_non_lus; ?></h2>
                        </div>
                        <div class="text-danger" style="font-size: 2.5rem;">
                            <i class="fas fa-envelope"></i>
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
                            <p class="text-muted mb-1 small">Total Messages</p>
                            <h2 class="mb-0 text-primary"><?php echo count($messages); ?></h2>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem;">
                            <i class="fas fa-inbox"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="btn-group">
                <a href="?filtre=tous" class="btn <?php echo $filtre === 'tous' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-inbox"></i> Tous (<?php echo count($messages); ?>)
                </a>
                <a href="?filtre=non_lus" class="btn <?php echo $filtre === 'non_lus' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-envelope"></i> Non lus
                    <?php if ($nb_non_lus > 0): ?>
                        <span class="badge bg-danger"><?php echo $nb_non_lus; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Table messages -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>De</th>
                            <th>Sujet</th>
                            <th>Message</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Aucun message
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): ?>
                                <tr class="<?php echo !$msg['lu'] ? 'table-warning' : ''; ?>">
                                    <td>
                                        <?php if ($msg['lu']): ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-envelope-open"></i> Lu
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="fas fa-envelope"></i> Nouveau
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php
                                            $date = new DateTime($msg['date_envoi']);
                                            echo $date->format('d/m/Y H:i');
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($msg['nom']); ?></strong>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-envelope"></i>
                                            <?php echo htmlspecialchars($msg['email']); ?>
                                        </small>
                                        <?php if ($msg['telephone']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-phone"></i>
                                                <?php echo htmlspecialchars($msg['telephone']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($msg['sujet']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php
                                            $preview = substr($msg['message'], 0, 80);
                                            echo htmlspecialchars($preview);
                                            echo strlen($msg['message']) > 80 ? '...' : '';
                                            ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Voir détails -->
                                            <button type="button"
                                                class="btn btn-outline-info"
                                                data-bs-toggle="modal"
                                                data-bs-target="#detailModal<?php echo $msg['id_message']; ?>"
                                                title="Voir message">
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <!-- Marquer lu -->
                                            <?php if (!$msg['lu']): ?>
                                                <a href="?marquer_lu=<?php echo $msg['id_message']; ?>"
                                                    class="btn btn-outline-success"
                                                    title="Marquer comme lu">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Supprimer -->
                                            <button type="button"
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteModal<?php echo $msg['id_message']; ?>"
                                                title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal Détails -->
                                <div class="modal fade" id="detailModal<?php echo $msg['id_message']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-envelope-open text-primary"></i>
                                                    Message de <?php echo htmlspecialchars($msg['nom']); ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <strong>De:</strong> <?php echo htmlspecialchars($msg['nom']); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Email:</strong>
                                                    <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>">
                                                        <?php echo htmlspecialchars($msg['email']); ?>
                                                    </a>
                                                </div>
                                                <?php if ($msg['telephone']): ?>
                                                    <div class="mb-3">
                                                        <strong>Téléphone:</strong>
                                                        <a href="tel:<?php echo htmlspecialchars($msg['telephone']); ?>">
                                                            <?php echo htmlspecialchars($msg['telephone']); ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mb-3">
                                                    <strong>Sujet:</strong>
                                                    <span class="badge bg-primary">
                                                        <?php echo htmlspecialchars($msg['sujet']); ?>
                                                    </span>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>Date:</strong>
                                                    <?php
                                                    $date = new DateTime($msg['date_envoi']);
                                                    echo $date->format('d/m/Y à H:i');
                                                    ?>
                                                </div>
                                                <hr>
                                                <div>
                                                    <strong>Message:</strong>
                                                    <div class="mt-2 p-3 bg-light rounded">
                                                        <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=Re: <?php echo urlencode($msg['sujet']); ?>"
                                                    class="btn btn-primary">
                                                    <i class="fas fa-reply"></i> Répondre par email
                                                </a>
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Supprimer -->
                                <div class="modal fade" id="deleteModal<?php echo $msg['id_message']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="supprimer" value="1">
                                                <input type="hidden" name="id_message" value="<?php echo $msg['id_message']; ?>">

                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-exclamation-triangle"></i> Confirmer suppression
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <p>Supprimer ce message?</p>
                                                    <div class="alert alert-warning">
                                                        <strong>De:</strong> <?php echo htmlspecialchars($msg['nom']); ?><br>
                                                        <strong>Sujet:</strong> <?php echo htmlspecialchars($msg['sujet']); ?>
                                                    </div>
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