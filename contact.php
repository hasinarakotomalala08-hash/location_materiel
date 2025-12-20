<?php
session_start();
require_once 'includes/config.php';

$page_title = "Contact - PLUTINA EVENT";

// Variables pour formulaire
$message_sent = false;
$error = '';

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validations basiques
    if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
        $error = "Tous les champs marqués * sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email invalide.";
    } else {
        // Enregistrer message database
        try {
            $stmt = $pdo->prepare("
            INSERT INTO contact_messages 
            (nom, email, telephone, sujet, message, date_envoi)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

            $stmt->execute([
                $nom,
                $email,
                $telephone,
                $sujet,
                $message
            ]);

            $message_sent = true;
        } catch (Exception $e) {
            $error = "Erreur lors de l'envoi: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<!-- Hero Section Contact -->
<div class="hero-gold py-5">
    <div class="container text-center">
        <h1 class="display-4 text-gold">
            <i class="fas fa-envelope"></i> Contactez-nous
        </h1>
        <p class="lead text-muted">
            Une question ? Un projet d'événement ? Nous sommes à votre écoute !
        </p>
    </div>
</div>

<!-- Section Contact -->
<div class="container my-5">
    <div class="row">
        <!-- Informations Contact -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h3 class="card-title text-gold mb-4">
                        <i class="fas fa-info-circle"></i> Nos Informations
                    </h3>

                    <!-- Adresse -->
                    <div class="mb-4">
                        <h5 class="text-dark mb-2">
                            <i class="fas fa-map-marker-alt text-gold"></i> Adresse
                        </h5>
                        <p class="text-muted mb-0">
                            Ambatobe<br>
                            Antananarivo, Madagascar
                        </p>
                    </div>

                    <!-- Horaires -->
                    <div class="mb-4">
                        <h5 class="text-dark mb-2">
                            <i class="fas fa-clock text-gold"></i> Horaires d'ouverture
                        </h5>
                        <p class="text-muted mb-1">
                            <strong>Lundi - Vendredi:</strong><br>
                            09h00 - 17h00
                        </p>
                        <p class="text-muted mb-1">
                            <strong>Samedi:</strong><br>
                            09h00 - 12h00
                        </p>
                        <p class="text-muted mb-0">
                            <strong>Dimanche:</strong> Fermé
                        </p>
                    </div>

                    <!-- Réseaux sociaux -->
                    <div>
                        <h5 class="text-dark mb-3">
                            <i class="fas fa-share-alt text-gold"></i> Suivez-nous
                        </h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="https://www.facebook.com/plutinaevents" target="_blank" class="btn btn-outline-gold btn-sm" title="Facebook Plutina Events">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                            <a href="https://www.instagram.com/plutinaevents" target="_blank" class="btn btn-outline-gold btn-sm" title="Instagram Plutina Events">
                                <i class="fab fa-instagram"></i> Instagram
                            </a>
                            <a href="https://www.tiktok.com/@plutinaevents" target="_blank" class="btn btn-outline-gold btn-sm" title="TikTok Plutina Events">
                                <i class="fab fa-tiktok"></i> TikTok
                            </a>
                            <a href="https://wa.me/261343466149" target="_blank" class="btn btn-outline-gold btn-sm" title="WhatsApp">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire Contact -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="card-title text-gold mb-4">
                        <i class="fas fa-paper-plane"></i> Envoyez-nous un message
                    </h3>

                    <?php if ($message_sent): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle"></i>
                            <strong>Message envoyé avec succès!</strong><br>
                            Nous vous répondrons dans les plus brefs délais.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom complet *</label>
                                <input type="text"
                                    name="nom"
                                    class="form-control"
                                    placeholder="Votre nom"
                                    value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                                    required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email"
                                    name="email"
                                    class="form-control"
                                    placeholder="votre@email.com"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                    required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="text"
                                    name="telephone"
                                    class="form-control"
                                    placeholder="034 XX XXX XX"
                                    value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Sujet *</label>
                                <select name="sujet" class="form-select" required>
                                    <option value="">Choisir...</option>
                                    <option value="Demande de devis">Demande de devis</option>
                                    <option value="Réservation">Réservation</option>
                                    <option value="Information produit">Information produit</option>
                                    <option value="Réclamation">Réclamation</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message *</label>
                            <textarea name="message"
                                class="form-control"
                                rows="6"
                                placeholder="Décrivez votre demande en détail..."
                                required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Les champs marqués d'un astérisque (*) sont obligatoires.
                            </small>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-gold btn-lg px-5">
                                <i class="fas fa-paper-plane"></i> Envoyer le message
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info WhatsApp -->
            <div class="alert alert-success mt-4 d-flex align-items-center">
                <i class="fab fa-whatsapp fa-2x me-3"></i>
                <div>
                    <strong>Besoin d'une réponse rapide ?</strong><br>
                    Contactez-nous via WhatsApp au
                    <a href="https://wa.me/261343466149" target="_blank" class="text-decoration-none fw-bold">
                        +261 34 34 661 49
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Map Google -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gold text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-map-marked-alt"></i> Notre Localisation
                    </h4>
                </div>
                <div class="card-body p-0">
                    <div style="width: 100%; height: 450px; overflow: hidden;">
                        <!-- Google Maps PLUTINA EVENT Ambatobe - Embed direct -->
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d979.3514933208047!2d47.54152456952263!3d-18.922221640748773!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x21f07fa4be7b53bf%3A0xd3dd85acf6285b9f!2sPlutina%20Events!5e0!3m2!1sfr!2smg!4v1733515234567!5m2!1sfr!2smg"
                            width="100%"
                            height="450"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="row text-center">
                        <div class="col-md-4 mb-2 mb-md-0">
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt text-gold"></i>
                                <strong>Adresse:</strong> Ambatobe, Antananarivo
                            </small>
                        </div>
                        <div class="col-md-4 mb-2 mb-md-0">
                            <small class="text-muted">
                                <i class="fas fa-route text-gold"></i>
                                <a href="https://maps.app.goo.gl/rnFZ6gZoS2nPqpJx8"
                                    target="_blank"
                                    class="text-decoration-none">
                                    Obtenir l'itinéraire
                                </a>
                            </small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">
                                <i class="fas fa-car text-gold"></i>
                                Parking disponible
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section CTA -->
<div class="bg-light py-5 mt-5">
    <div class="container text-center">
        <h2 class="text-gold mb-3">Prêt à réserver ?</h2>
        <p class="lead text-muted mb-4">
            Découvrez notre catalogue et réservez vos matériels en quelques clics !
        </p>
        <a href="catalogue.php" class="btn btn-gold btn-lg px-5 me-2">
            <i class="fas fa-boxes"></i> Voir le catalogue
        </a>
        <a href="inscription.php" class="btn btn-outline-gold btn-lg px-5">
            <i class="fas fa-user-plus"></i> Créer un compte
        </a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>