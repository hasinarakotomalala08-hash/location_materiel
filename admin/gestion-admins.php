<?php
require_once 'check_admin.php';

// Variables
$message = '';
$error = '';

// Traitement actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // AJOUTER ADMIN
        if ($action === 'ajouter') {
            $nom = trim($_POST['nom']);
            $prenom = trim($_POST['prenom']);
            $email = trim($_POST['email']);
            $telephone = trim($_POST['telephone']);
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];

            // Validations
            if (empty($nom) || empty($prenom) || empty($email) || empty($telephone) || empty($password)) {
                throw new Exception("Tous les champs sont obligatoires.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email invalide.");
            }

            if (strlen($password) < 6) {
                throw new Exception("Le mot de passe doit contenir au moins 6 caractères.");
            }

            if ($password !== $password_confirm) {
                throw new Exception("Les mots de passe ne correspondent pas.");
            }

            // Vérifier email unique
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM client WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cet email est déjà utilisé.");
            }

            // Créer admin
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO client (nom, prenom, email, telephone, password, role, date_inscription)
                VALUES (?, ?, ?, ?, ?, 'admin', NOW())
            ");
            $stmt->execute([$nom, $prenom, $email, $telephone, $password_hash]);

            $message = "Administrateur ajouté avec succès!";
        }

        // MODIFIER ADMIN
        elseif ($action === 'modifier') {
            $id = (int)$_POST['id_client'];
            $nom = trim($_POST['nom']);
            $prenom = trim($_POST['prenom']);
            $email = trim($_POST['email']);
            $telephone = trim($_POST['telephone']);

            // Validations
            if (empty($nom) || empty($prenom) || empty($email) || empty($telephone)) {
                throw new Exception("Tous les champs sont obligatoires.");
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Format email invalide.");
            }

            // Vérifier email unique (sauf si même admin)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM client WHERE email = ? AND id_client != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cet email est déjà utilisé par un autre compte.");
            }

            // Modifier
            $stmt = $pdo->prepare("
                UPDATE client 
                SET nom = ?, prenom = ?, email = ?, telephone = ?
                WHERE id_client = ? AND role = 'admin'
            ");
            $stmt->execute([$nom, $prenom, $email, $telephone, $id]);

            $message = "Administrateur modifié avec succès!";
        }

        // RÉINITIALISER PASSWORD
        elseif ($action === 'reset_password') {
            $id = (int)$_POST['id_client'];
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];

            if (strlen($password) < 6) {
                throw new Exception("Le mot de passe doit contenir au moins 6 caractères.");
            }

            if ($password !== $password_confirm) {
                throw new Exception("Les mots de passe ne correspondent pas.");
            }

            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                UPDATE client 
                SET password = ?
                WHERE id_client = ? AND role = 'admin'
            ");
            $stmt->execute([$password_hash, $id]);

            $message = "Mot de passe réinitialisé avec succès!";
        }

        // SUPPRIMER ADMIN
        elseif ($action === 'supprimer') {
            $id = (int)$_POST['id_client'];

            // Empêcher suppression de soi-même
            if ($id == $_SESSION['user_id']) {
                throw new Exception("Vous ne pouvez pas supprimer votre propre compte!");
            }

            // Compter admins restants
            $stmt = $pdo->query("SELECT COUNT(*) FROM client WHERE role = 'admin'");
            $count = $stmt->fetchColumn();

            if ($count <= 1) {
                throw new Exception("Impossible de supprimer le dernier administrateur!");
            }

            // Supprimer
            $stmt = $pdo->prepare("DELETE FROM client WHERE id_client = ? AND role = 'admin'");
            $stmt->execute([$id]);

            $message = "Administrateur supprimé avec succès!";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer admins
$stmt = $pdo->query("
    SELECT * FROM client 
    WHERE role = 'admin'
    ORDER BY date_inscription DESC
");
$admins = $stmt->fetchAll();

$page_title = "Gestion Administrateurs - Admin PLUTINA EVENT";
include 'header.php';
?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-user-shield text-warning"></i> Gestion des Administrateurs
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                <i class="fas fa-plus"></i> Ajouter administrateur
            </button>
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

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Total Administrateurs</p>
                            <h2 class="mb-0 text-warning"><?php echo count($admins); ?></h2>
                        </div>
                        <div class="text-warning" style="font-size: 2.5rem;">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle"></i>
                <strong>Important:</strong> Les administrateurs ont accès total au système.
                Assurez-vous de créer uniquement des comptes de confiance.
            </div>
        </div>
    </div>

    <!-- Table admins -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Administrateur</th>
                            <th>Contact</th>
                            <th>Date création</th>
                            <th>Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Aucun administrateur
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></strong>
                                            <?php if ($admin['id_client'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-primary ms-2">Vous</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-hashtag"></i><?php echo $admin['id_client']; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <i class="fas fa-envelope text-muted"></i>
                                            <?php echo htmlspecialchars($admin['email']); ?>
                                        </div>
                                        <div class="small">
                                            <i class="fas fa-phone text-muted"></i>
                                            <?php echo htmlspecialchars($admin['telephone']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <small>
                                            <?php
                                            $date = new DateTime($admin['date_inscription']);
                                            echo $date->format('d/m/Y');
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Actif
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Modifier -->
                                            <button type="button"
                                                class="btn btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modifierModal<?php echo $admin['id_client']; ?>"
                                                title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <!-- Reset password -->
                                            <button type="button"
                                                class="btn btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#resetModal<?php echo $admin['id_client']; ?>"
                                                title="Réinitialiser mot de passe">
                                                <i class="fas fa-key"></i>
                                            </button>

                                            <!-- Supprimer -->
                                            <?php if ($admin['id_client'] != $_SESSION['user_id']): ?>
                                                <button type="button"
                                                    class="btn btn-outline-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#supprimerModal<?php echo $admin['id_client']; ?>"
                                                    title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal Modifier -->
                                <div class="modal fade" id="modifierModal<?php echo $admin['id_client']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="modifier">
                                                <input type="hidden" name="id_client" value="<?php echo $admin['id_client']; ?>">

                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-edit text-primary"></i> Modifier administrateur
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Nom *</label>
                                                            <input type="text" name="nom" class="form-control"
                                                                value="<?php echo htmlspecialchars($admin['nom']); ?>" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Prénom *</label>
                                                            <input type="text" name="prenom" class="form-control"
                                                                value="<?php echo htmlspecialchars($admin['prenom']); ?>" required>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Email *</label>
                                                        <input type="email" name="email" class="form-control"
                                                            value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Téléphone *</label>
                                                        <input type="text" name="telephone" class="form-control"
                                                            value="<?php echo htmlspecialchars($admin['telephone']); ?>" required>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-save"></i> Enregistrer
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Reset Password -->
                                <div class="modal fade" id="resetModal<?php echo $admin['id_client']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="id_client" value="<?php echo $admin['id_client']; ?>">

                                                <div class="modal-header bg-warning">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-key"></i> Réinitialiser mot de passe
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="alert alert-warning">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        Nouveau mot de passe pour:
                                                        <strong><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></strong>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Nouveau mot de passe *</label>
                                                        <input type="password" name="password" class="form-control"
                                                            placeholder="Minimum 6 caractères" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Confirmer mot de passe *</label>
                                                        <input type="password" name="password_confirm" class="form-control" required>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="fas fa-key"></i> Réinitialiser
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Supprimer -->
                                <?php if ($admin['id_client'] != $_SESSION['user_id']): ?>
                                    <div class="modal fade" id="supprimerModal<?php echo $admin['id_client']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="supprimer">
                                                    <input type="hidden" name="id_client" value="<?php echo $admin['id_client']; ?>">

                                                    <div class="modal-header bg-danger text-white">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-exclamation-triangle"></i> Confirmer suppression
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <p>Êtes-vous sûr de vouloir supprimer cet administrateur?</p>
                                                        <div class="alert alert-warning">
                                                            <strong><?php echo htmlspecialchars($admin['prenom'] . ' ' . $admin['nom']); ?></strong><br>
                                                            <small><?php echo htmlspecialchars($admin['email']); ?></small>
                                                        </div>
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
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter -->
<div class="modal fade" id="ajouterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="ajouter">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus text-success"></i> Ajouter administrateur
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom *</label>
                            <input type="text" name="prenom" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control"
                            placeholder="admin@plutinaevent.mg" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Téléphone *</label>
                        <input type="text" name="telephone" class="form-control"
                            placeholder="034 XX XXX XX" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mot de passe *</label>
                        <input type="password" name="password" class="form-control"
                            placeholder="Minimum 6 caractères" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirmer mot de passe *</label>
                        <input type="password" name="password_confirm" class="form-control" required>
                    </div>

                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle"></i>
                        Le nouvel administrateur aura accès total au système.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>