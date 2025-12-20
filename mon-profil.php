<?php
// Page mon profil - Modification
require_once 'includes/config.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Variables
$message = '';
$error = '';

// Récupérer infos utilisateur
$stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: connexion.php');
    exit;
}

// Traitement formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // MODIFIER INFORMATIONS
        if ($action === 'modifier_infos') {
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

            // Vérifier si email existe déjà (sauf si c'est le même)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM client WHERE email = ? AND id_client != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Cet email est déjà utilisé par un autre compte.");
            }

            // Mettre à jour
            $stmt = $pdo->prepare("
                UPDATE client 
                SET nom = ?, prenom = ?, email = ?, telephone = ?
                WHERE id_client = ?
            ");
            $stmt->execute([$nom, $prenom, $email, $telephone, $_SESSION['user_id']]);

            // Mettre à jour session
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_email'] = $email;

            // Récupérer nouvelles données
            $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            $message = "Informations mises à jour avec succès!";
        }

        // CHANGER MOT DE PASSE
        elseif ($action === 'changer_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Validations
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("Tous les champs sont obligatoires.");
            }

            // Vérifier mot de passe actuel
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Mot de passe actuel incorrect.");
            }

            if (strlen($new_password) < 6) {
                throw new Exception("Le nouveau mot de passe doit contenir au moins 6 caractères.");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("Les nouveaux mots de passe ne correspondent pas.");
            }

            // Mettre à jour mot de passe
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("UPDATE client SET password = ? WHERE id_client = ?");
            $stmt->execute([$password_hash, $_SESSION['user_id']]);

            $message = "Mot de passe changé avec succès!";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = "Mon Profil - PLUTINA EVENT";
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="bg-light py-2">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                <li class="breadcrumb-item"><a href="mon-compte.php">Mon Compte</a></li>
                <li class="breadcrumb-item active">Mon Profil</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Mon Profil -->
<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-user-edit icon-gold"></i> Mon Profil
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="mon-compte.php" class="btn btn-outline-gold">
                <i class="fas fa-arrow-left"></i> Retour
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

    <div class="row">
        <!-- Formulaire Modifier Infos -->
        <div class="col-lg-6 mb-4">
            <div class="card card-gold">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user icon-gold"></i> Mes Informations
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formInfos">
                        <input type="hidden" name="action" value="modifier_infos">

                        <div class="mb-3">
                            <label for="nom" class="form-label">
                                Nom <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                id="nom"
                                name="nom"
                                value="<?php echo htmlspecialchars($user['nom']); ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="prenom" class="form-label">
                                Prénom <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                class="form-control"
                                id="prenom"
                                name="prenom"
                                value="<?php echo htmlspecialchars($user['prenom']); ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                required>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> Utilisé pour la connexion
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="telephone" class="form-label">
                                Téléphone <span class="text-danger">*</span>
                            </label>
                            <input type="tel"
                                class="form-control"
                                id="telephone"
                                name="telephone"
                                value="<?php echo htmlspecialchars($user['telephone']); ?>"
                                placeholder="034 XX XXX XX"
                                required>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <small>
                                Membre depuis le
                                <?php
                                $date = new DateTime($user['date_inscription']);
                                echo $date->format('d/m/Y');
                                ?>
                            </small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-gold btn-lg">
                                <i class="fas fa-save"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Formulaire Changer Mot de Passe -->
        <div class="col-lg-6 mb-4">
            <div class="card card-gold">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-lock icon-gold"></i> Changer mon mot de passe
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="formPassword">
                        <input type="hidden" name="action" value="changer_password">

                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                Mot de passe actuel <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control"
                                    id="current_password"
                                    name="current_password"
                                    required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleCurrent">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                Nouveau mot de passe <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control"
                                    id="new_password"
                                    name="new_password"
                                    minlength="6"
                                    required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleNew">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="passwordHelp" class="text-muted">Minimum 6 caractères</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                Confirmer nouveau mot de passe <span class="text-danger">*</span>
                            </label>
                            <input type="password"
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="6"
                                required>
                            <div class="invalid-feedback" id="passwordMatchFeedback">
                                Les mots de passe ne correspondent pas.
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <small>
                                Après changement, vous devrez vous reconnecter avec votre nouveau mot de passe.
                            </small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-key"></i> Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Danger Zone -->
    <div class="row">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Zone Dangereuse
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Si vous souhaitez supprimer votre compte, veuillez nous contacter.
                    </p>
                    <a href="contact.php" class="btn btn-outline-danger">
                        <i class="fas fa-envelope"></i> Contacter le support
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const toggleButtons = [{
                btn: 'toggleCurrent',
                input: 'current_password'
            },
            {
                btn: 'toggleNew',
                input: 'new_password'
            }
        ];

        toggleButtons.forEach(item => {
            const btn = document.getElementById(item.btn);
            const input = document.getElementById(item.input);

            if (btn && input) {
                btn.addEventListener('click', function() {
                    const type = input.type === 'password' ? 'text' : 'password';
                    input.type = type;
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            }
        });

        // Password strength checker
        const newPassword = document.getElementById('new_password');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordHelp = document.getElementById('passwordHelp');

        if (newPassword && passwordStrength) {
            newPassword.addEventListener('input', function() {
                const value = this.value;
                let strength = 0;

                if (value.length >= 6) strength += 25;
                if (value.length >= 8) strength += 25;
                if (/[A-Z]/.test(value)) strength += 25;
                if (/[0-9]/.test(value)) strength += 25;

                passwordStrength.style.width = strength + '%';

                if (strength <= 25) {
                    passwordStrength.className = 'progress-bar bg-danger';
                    passwordHelp.textContent = 'Mot de passe faible';
                    passwordHelp.className = 'text-danger';
                } else if (strength <= 50) {
                    passwordStrength.className = 'progress-bar bg-warning';
                    passwordHelp.textContent = 'Mot de passe moyen';
                    passwordHelp.className = 'text-warning';
                } else {
                    passwordStrength.className = 'progress-bar bg-success';
                    passwordHelp.textContent = 'Mot de passe fort';
                    passwordHelp.className = 'text-success';
                }
            });
        }

        // Password match checker
        const confirmPassword = document.getElementById('confirm_password');

        function checkPasswordMatch() {
            if (confirmPassword.value === '') return;

            if (newPassword.value === confirmPassword.value) {
                confirmPassword.classList.remove('is-invalid');
                confirmPassword.classList.add('is-valid');
            } else {
                confirmPassword.classList.remove('is-valid');
                confirmPassword.classList.add('is-invalid');
            }
        }

        if (newPassword && confirmPassword) {
            newPassword.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        }

        // Form validation
        const formPassword = document.getElementById('formPassword');

        if (formPassword) {
            formPassword.addEventListener('submit', function(e) {
                if (newPassword.value !== confirmPassword.value) {
                    e.preventDefault();
                    confirmPassword.classList.add('is-invalid');

                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Erreur!</strong> Les mots de passe ne correspondent pas.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    formPassword.insertBefore(alertDiv, formPassword.firstChild);

                    alertDiv.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    return false;
                }

                if (newPassword.value.length < 6) {
                    e.preventDefault();
                    newPassword.classList.add('is-invalid');

                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                    alertDiv.innerHTML = `
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Erreur!</strong> Le mot de passe doit contenir au moins 6 caractères.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    formPassword.insertBefore(alertDiv, formPassword.firstChild);

                    alertDiv.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });

                    return false;
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>