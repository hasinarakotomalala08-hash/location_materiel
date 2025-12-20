<?php
require_once 'check_admin.php';

// Variables
$message = '';
$error = '';

// Traitement actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        // AJOUTER
        if ($action === 'ajouter') {
            $nom = trim($_POST['nom_materiel']);
            $description = trim($_POST['description']);
            $prix_journalier = (float)$_POST['prix_journalier'];
            $prix_remboursement = (float)$_POST['prix_remboursement'];
            $quantite_totale = (int)$_POST['quantite_totale'];
            $id_categorie = (int)$_POST['id_categorie'];

            // Upload photo
            $photo = 'default.jpg';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
                $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo = 'materiel_' . time() . '.' . $extension;
                move_uploaded_file($_FILES['photo']['tmp_name'], '../images/' . $photo);
            }

            $stmt = $pdo->prepare("
                INSERT INTO materiel (
                    nom_materiel, description, prix_journalier, prix_remboursement,
                    quantite_totale, quantite_disponible, photo, id_categorie
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $nom,
                $description,
                $prix_journalier,
                $prix_remboursement,
                $quantite_totale,
                $quantite_totale,
                $photo,
                $id_categorie
            ]);

            $message = "Matériel ajouté avec succès!";
        }

        // MODIFIER
        elseif ($action === 'modifier') {
            $id = (int)$_POST['id_materiel'];
            $nom = trim($_POST['nom_materiel']);
            $description = trim($_POST['description']);
            $prix_journalier = (float)$_POST['prix_journalier'];
            $prix_remboursement = (float)$_POST['prix_remboursement'];
            $quantite_totale = (int)$_POST['quantite_totale'];
            $quantite_disponible = (int)$_POST['quantite_disponible'];
            $id_categorie = (int)$_POST['id_categorie'];

            // Upload nouvelle photo si fournie
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
                $extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $photo = 'materiel_' . time() . '.' . $extension;
                move_uploaded_file($_FILES['photo']['tmp_name'], '../images/' . $photo);

                $stmt = $pdo->prepare("
                    UPDATE materiel SET 
                        nom_materiel = ?, description = ?, prix_journalier = ?,
                        prix_remboursement = ?, quantite_totale = ?, quantite_disponible = ?,
                        photo = ?, id_categorie = ?
                    WHERE id_materiel = ?
                ");
                $stmt->execute([
                    $nom,
                    $description,
                    $prix_journalier,
                    $prix_remboursement,
                    $quantite_totale,
                    $quantite_disponible,
                    $photo,
                    $id_categorie,
                    $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE materiel SET 
                        nom_materiel = ?, description = ?, prix_journalier = ?,
                        prix_remboursement = ?, quantite_totale = ?, quantite_disponible = ?,
                        id_categorie = ?
                    WHERE id_materiel = ?
                ");
                $stmt->execute([
                    $nom,
                    $description,
                    $prix_journalier,
                    $prix_remboursement,
                    $quantite_totale,
                    $quantite_disponible,
                    $id_categorie,
                    $id
                ]);
            }

            $message = "Matériel modifié avec succès!";
        }

        // SUPPRIMER
        elseif ($action === 'supprimer') {
            $id = (int)$_POST['id_materiel'];

            // Vérifier si utilisé dans réservations actives
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM ligne_reservation lr
                JOIN reservation r ON lr.id_reservation = r.id_reservation
                WHERE lr.id_materiel = ? AND r.statut IN ('en_attente', 'confirme', 'en_cours')
            ");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = "Impossible de supprimer: matériel utilisé dans $count réservation(s) active(s).";
            } else {
                $stmt = $pdo->prepare("DELETE FROM materiel WHERE id_materiel = ?");
                $stmt->execute([$id]);
                $message = "Matériel supprimé avec succès!";
            }
        }
    } catch (Exception $e) {
        $error = "Erreur: " . $e->getMessage();
    }
}

// Filtre catégorie
$filtre_cat = $_GET['cat'] ?? 'tous';

// Récupérer matériels
if ($filtre_cat === 'tous') {
    $stmt = $pdo->query("
        SELECT m.*, c.nom_categorie
        FROM materiel m
        JOIN categorie c ON m.id_categorie = c.id_categorie
        ORDER BY c.nom_categorie, m.nom_materiel
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT m.*, c.nom_categorie
        FROM materiel m
        JOIN categorie c ON m.id_categorie = c.id_categorie
        WHERE m.id_categorie = ?
        ORDER BY m.nom_materiel
    ");
    $stmt->execute([$filtre_cat]);
}
$materiels = $stmt->fetchAll();

// Récupérer catégories
$categories = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie")->fetchAll();

$page_title = "Gestion Matériels - Admin PLUTINA EVENT";
include 'header.php';
?>

<div class="container-fluid my-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>
                <i class="fas fa-boxes text-warning"></i> Gestion des Matériels
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#ajouterModal">
                <i class="fas fa-plus"></i> Ajouter matériel
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

    <!-- Filtres -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="?cat=tous" class="btn <?php echo $filtre_cat === 'tous' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                    <i class="fas fa-list"></i> Tous (<?php echo count($materiels); ?>)
                </a>
                <?php foreach ($categories as $cat): ?>
                    <a href="?cat=<?php echo $cat['id_categorie']; ?>"
                        class="btn <?php echo $filtre_cat == $cat['id_categorie'] ? 'btn-warning' : 'btn-outline-warning'; ?>">
                        <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Table matériels -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Photo</th>
                            <th>Nom</th>
                            <th>Catégorie</th>
                            <th>Prix/jour</th>
                            <th>Prix rembours.</th>
                            <th>Stock Total</th>
                            <th>Disponible</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materiels)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    Aucun matériel
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($materiels as $mat): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $image_path = '../images/' . $mat['photo'];
                                        if (file_exists($image_path)): ?>
                                            <img src="<?php echo htmlspecialchars($image_path); ?>"
                                                class="rounded"
                                                style="width: 60px; height: 60px; object-fit: cover;"
                                                alt="<?php echo htmlspecialchars($mat['nom_materiel']); ?>">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                                style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($mat['nom_materiel']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($mat['description'], 0, 50)); ?>...</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($mat['nom_categorie']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-warning">
                                            <?php echo number_format($mat['prix_journalier'], 0, ',', ' '); ?> Ar
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="text-info">
                                            <?php echo number_format($mat['prix_remboursement'], 0, ',', ' '); ?> Ar
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $mat['quantite_totale']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $dispo_percent = ($mat['quantite_totale'] > 0)
                                            ? ($mat['quantite_disponible'] / $mat['quantite_totale'] * 100)
                                            : 0;

                                        $badge_color = 'success';
                                        if ($dispo_percent < 30) $badge_color = 'danger';
                                        elseif ($dispo_percent < 60) $badge_color = 'warning';
                                        ?>
                                        <span class="badge bg-<?php echo $badge_color; ?>">
                                            <?php echo $mat['quantite_disponible']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button"
                                                class="btn btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modifierModal<?php echo $mat['id_materiel']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button"
                                                class="btn btn-outline-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#supprimerModal<?php echo $mat['id_materiel']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal Modifier -->
                                <div class="modal fade" id="modifierModal<?php echo $mat['id_materiel']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" enctype="multipart/form-data">
                                                <input type="hidden" name="action" value="modifier">
                                                <input type="hidden" name="id_materiel" value="<?php echo $mat['id_materiel']; ?>">

                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-edit text-primary"></i> Modifier matériel
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Nom *</label>
                                                        <input type="text" name="nom_materiel" class="form-control"
                                                            value="<?php echo htmlspecialchars($mat['nom_materiel']); ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Description *</label>
                                                        <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($mat['description']); ?></textarea>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Prix/jour (Ar) *</label>
                                                            <input type="number" name="prix_journalier" class="form-control"
                                                                value="<?php echo $mat['prix_journalier']; ?>" step="0.01" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Prix remboursement (Ar) *</label>
                                                            <input type="number" name="prix_remboursement" class="form-control"
                                                                value="<?php echo $mat['prix_remboursement']; ?>" step="0.01" required>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Quantité totale *</label>
                                                            <input type="number" name="quantite_totale" class="form-control"
                                                                value="<?php echo $mat['quantite_totale']; ?>" required>
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">Disponible *</label>
                                                            <input type="number" name="quantite_disponible" class="form-control"
                                                                value="<?php echo $mat['quantite_disponible']; ?>" required>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Catégorie *</label>
                                                        <select name="id_categorie" class="form-select" required>
                                                            <?php foreach ($categories as $cat): ?>
                                                                <option value="<?php echo $cat['id_categorie']; ?>"
                                                                    <?php echo $mat['id_categorie'] == $cat['id_categorie'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Photo (laisser vide pour garder l'actuelle)</label>
                                                        <input type="file" name="photo" class="form-control" accept="image/*">
                                                        <small class="text-muted">Actuelle: <?php echo $mat['photo']; ?></small>
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

                                <!-- Modal Supprimer -->
                                <div class="modal fade" id="supprimerModal<?php echo $mat['id_materiel']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="supprimer">
                                                <input type="hidden" name="id_materiel" value="<?php echo $mat['id_materiel']; ?>">

                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-exclamation-triangle"></i> Confirmer suppression
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <p>Êtes-vous sûr de vouloir supprimer ce matériel?</p>
                                                    <div class="alert alert-warning">
                                                        <strong><?php echo htmlspecialchars($mat['nom_materiel']); ?></strong>
                                                    </div>
                                                    <p class="text-danger small">
                                                        <i class="fas fa-info-circle"></i>
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

<!-- Modal Ajouter -->
<div class="modal fade" id="ajouterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="ajouter">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus text-success"></i> Ajouter matériel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom *</label>
                        <input type="text" name="nom_materiel" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prix/jour (Ar) *</label>
                            <input type="number" name="prix_journalier" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prix remboursement (Ar) *</label>
                            <input type="number" name="prix_remboursement" class="form-control" step="0.01" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Quantité totale *</label>
                        <input type="number" name="quantite_totale" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catégorie *</label>
                        <select name="id_categorie" class="form-select" required>
                            <option value="">Choisir...</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id_categorie']; ?>">
                                    <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
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