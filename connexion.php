<?php
// Page connexion
require_once 'includes/config.php';

// Si déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    header('Location: mon-compte.php');
    exit;
}

// Variables pour messages
$error = '';

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        // Récupérer utilisateur
        $stmt = $pdo->prepare("SELECT * FROM client WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Vérifier password
        if ($user && password_verify($password, $user['password'])) {
            // Login réussi - créer session
            $_SESSION['user_id'] = $user['id_client'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role'];

            // Rediriger selon rôle
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: mon-compte.php');
            }
            exit;
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}

$page_title = "Connexion - PLUTINA EVENT";
include 'includes/header.php';
?>


<!-- Formulaire Connexion -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card card-gold">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">
                        <i class="fas fa-sign-in-alt icon-gold"></i> Connexion
                    </h2>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="connexionForm">
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope icon-gold"></i> Email
                            </label>
                            <input type="email"
                                class="form-control form-control-lg"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                placeholder="votre@email.com"
                                required>
                            <div class="invalid-feedback">Veuillez entrer votre email.</div>
                        </div>

                        <!-- Mot de passe -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock icon-gold"></i> Mot de passe
                            </label>
                            <div class="input-group">
                                <input type="password"
                                    class="form-control form-control-lg"
                                    id="password"
                                    name="password"
                                    placeholder="••••••••"
                                    required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Veuillez entrer votre mot de passe.</div>
                        </div>

                        <!-- Remember me (optionnel) -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Se souvenir de moi
                            </label>
                        </div>

                        <!-- Bouton Submit -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-gold btn-lg" id="submitBtn">
                                <i class="fas fa-sign-in-alt"></i> Se connecter
                            </button>
                        </div>

                        <!-- Liens -->
                        <div class="text-center">
                            <p class="mb-2">
                                <a href="#" class="text-muted" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                                    <i class="fas fa-question-circle"></i> Mot de passe oublié?
                                </a>
                            </p>
                            <hr>
                            <p class="mb-0">
                                Pas encore de compte? <a href="inscription.php" class="text-gold fw-bold">S'inscrire</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info card -->
            <div class="card mt-3 border-0 bg-light">
                <div class="card-body text-center">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt text-success"></i>
                        Connexion sécurisée • Vos données sont protégées
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Mot de passe oublié -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key icon-gold"></i> Mot de passe oublié
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Pour réinitialiser votre mot de passe, veuillez contacter notre service client:</p>
                <div class="alert alert-info">
                    <i class="fas fa-phone"></i> <strong>034 00 00 00</strong><br>
                    <i class="fas fa-envelope"></i> <strong>contact@plutinaevent.mg</strong>
                </div>
                <p class="text-muted small mb-0">
                    <i class="fas fa-info-circle"></i>
                    Nous vous aiderons à récupérer votre compte rapidement.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Validation -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('connexionForm');
        const password = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const submitBtn = document.getElementById('submitBtn');

        // Toggle password visibility
        togglePassword.addEventListener('click', function() {
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // Form validation
        form.addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');

            // Reset validation
            email.classList.remove('is-invalid');
            password.classList.remove('is-invalid');

            let hasError = false;

            // Check email
            if (email.value.trim() === '') {
                email.classList.add('is-invalid');
                hasError = true;
            }

            // Check password
            if (password.value === '') {
                password.classList.add('is-invalid');
                hasError = true;
            }

            // If errors, prevent submit
            if (hasError) {
                e.preventDefault();

                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Erreur!</strong> Veuillez remplir tous les champs.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
                form.insertBefore(alertDiv, form.firstChild);

                return false;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion en cours...';
        });

        // Bootstrap validation
        form.classList.add('needs-validation');
    });
</script>

<?php include 'includes/footer.php'; ?>