<?php
// Page inscription
require_once 'includes/config.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    header('Location: mon-compte.php');
    exit;
}

// Variables pour messages
$error = '';
$success = '';

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer données
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif ($password !== $password_confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si email existe déjà
        $stmt = $pdo->prepare("SELECT id_client FROM client WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            $error = "Cet email est déjà utilisé.";
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Insérer nouveau client
            $stmt = $pdo->prepare("
                INSERT INTO client (nom, prenom, email, telephone, password, role, date_inscription) 
                VALUES (?, ?, ?, ?, ?, 'client', NOW())
            ");

            if ($stmt->execute([$nom, $prenom, $email, $telephone, $password_hash])) {
                $success = "Inscription réussie! Vous pouvez maintenant vous connecter.";
            } else {
                $error = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
        }
    }
}

$page_title = "Inscription - PLUTINA EVENT";
include 'includes/header.php';
?>


<!-- Formulaire Inscription -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card card-gold">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">
                        <i class="fas fa-user-plus icon-gold"></i> Créer un compte
                    </h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            <br><a href="connexion.php" class="alert-link">Se connecter maintenant</a>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="inscriptionForm">
                        <!-- Nom -->
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control"
                                id="nom"
                                name="nom"
                                value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                                required>
                            <div class="invalid-feedback">Le nom est obligatoire.</div>
                        </div>

                        <!-- Prénom -->
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control"
                                id="prenom"
                                name="prenom"
                                value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>"
                                required>
                            <div class="invalid-feedback">Le prénom est obligatoire.</div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required>
                            <div class="invalid-feedback">Veuillez entrer un email valide.</div>
                            <small class="text-muted">Utilisé pour la connexion</small>
                        </div>

                        <!-- Téléphone -->
                        <div class="mb-3">
                            <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                            <input type="tel"
                                class="form-control"
                                id="telephone"
                                name="telephone"
                                value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>"
                                placeholder="034 00 000 00"
                                pattern="[0-9\s]+"
                                required>
                            <div class="invalid-feedback">Le téléphone est obligatoire (chiffres uniquement).</div>
                        </div>

                        <!-- Mot de passe -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    minlength="6"
                                    required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Minimum 6 caractères.</div>
                            <!-- Password strength indicator -->
                            <div class="progress mt-2" style="height: 5px;">
                                <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="passwordHelp" class="text-muted">Minimum 6 caractères</small>
                        </div>

                        <!-- Confirmer mot de passe -->
                        <div class="mb-3">
                            <label for="password_confirm" class="form-label">Confirmer mot de passe <span class="text-danger">*</span></label>
                            <input type="password"
                                class="form-control"
                                id="password_confirm"
                                name="password_confirm"
                                minlength="6"
                                required>
                            <div class="invalid-feedback" id="passwordMatchFeedback">Les mots de passe ne correspondent pas.</div>
                        </div>

                        <!-- Bouton Submit -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-gold btn-lg" id="submitBtn">
                                <i class="fas fa-user-plus"></i> S'inscrire
                            </button>
                        </div>

                        <!-- Lien connexion -->
                        <div class="text-center">
                            <p class="mb-0">Déjà un compte? <a href="connexion.php" class="text-gold">Se connecter</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Validation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('inscriptionForm');
        const password = document.getElementById('password');
        const passwordConfirm = document.getElementById('password_confirm');
        const passwordStrength = document.getElementById('passwordStrength');
        const passwordHelp = document.getElementById('passwordHelp');
        const togglePassword = document.getElementById('togglePassword');
        const submitBtn = document.getElementById('submitBtn');

        // Toggle password visibility
        togglePassword.addEventListener('click', function() {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Password strength checker
        password.addEventListener('input', function() {
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

        // Password match checker
        function checkPasswordMatch() {
            if (passwordConfirm.value === '') return;

            if (password.value === passwordConfirm.value) {
                passwordConfirm.classList.remove('is-invalid');
                passwordConfirm.classList.add('is-valid');
            } else {
                passwordConfirm.classList.remove('is-valid');
                passwordConfirm.classList.add('is-invalid');
            }
        }

        password.addEventListener('input', checkPasswordMatch);
        passwordConfirm.addEventListener('input', checkPasswordMatch);

        // Form validation
        form.addEventListener('submit', function(e) {
            // Check if passwords match
            if (password.value !== passwordConfirm.value) {
                e.preventDefault();
                passwordConfirm.classList.add('is-invalid');

                // Show alert
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Erreur!</strong> Les mots de passe ne correspondent pas.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
                form.insertBefore(alertDiv, form.firstChild);

                // Scroll to alert
                alertDiv.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                return false;
            }

            // Check password length
            if (password.value.length < 6) {
                e.preventDefault();
                password.classList.add('is-invalid');

                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Erreur!</strong> Le mot de passe doit contenir au moins 6 caractères.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
                form.insertBefore(alertDiv, form.firstChild);

                alertDiv.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                return false;
            }

            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inscription en cours...';
        });

        // Bootstrap validation
        form.classList.add('needs-validation');
    });
</script>

<?php include 'includes/footer.php'; ?>