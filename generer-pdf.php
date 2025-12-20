<?php
// Génération PDF Facture
require_once 'includes/config.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    die('Non autorisé');
}

// Récupérer ID réservation
$id_reservation = (int)($_GET['id'] ?? 0);

if ($id_reservation === 0) {
    die('Réservation invalide');
}

// Récupérer réservation
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    // Admin peut accéder à n'importe quelle réservation
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom, c.prenom, c.email, c.telephone
        FROM reservation r
        JOIN client c ON r.id_client = c.id_client
        WHERE r.id_reservation = ?
    ");
    $stmt->execute([$id_reservation]);
} else {
    // Client normal: s'assure que la réservation appartient à l'utilisateur connecté
    $stmt = $pdo->prepare("
        SELECT r.*, c.nom, c.prenom, c.email, c.telephone
        FROM reservation r
        JOIN client c ON r.id_client = c.id_client
        WHERE r.id_reservation = ? AND r.id_client = ?
    ");
    $stmt->execute([$id_reservation, $_SESSION['user_id']]);
}
$reservation = $stmt->fetch();

if (!$reservation) {
    die('Réservation non trouvée');
}

// Récupérer lignes réservation
$stmt = $pdo->prepare("
    SELECT lr.*, m.nom_materiel, m.prix_remboursement
    FROM ligne_reservation lr
    JOIN materiel m ON lr.id_materiel = m.id_materiel
    WHERE lr.id_reservation = ?
");
$stmt->execute([$id_reservation]);
$lignes = $stmt->fetchAll();

// Calculer valeur totale matériel
$valeur_materiel = 0;
foreach ($lignes as $ligne) {
    $valeur_materiel += $ligne['prix_remboursement'] * $ligne['quantite'];
}

// ============================================
// TCPDF - Vérifier installation
// ============================================

// Option 1: Composer
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}
// Option 2: Manual
elseif (file_exists('includes/tcpdf/tcpdf.php')) {
    require_once 'includes/tcpdf/tcpdf.php';
} else {
    die('TCPDF non installé. Veuillez installer TCPDF via Composer ou télécharger manuellement.');
}

// ============================================
// Créer PDF
// ============================================

// Créer instance TCPDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Informations document
$pdf->SetCreator('PLUTINA EVENT');
$pdf->SetAuthor('PLUTINA EVENT');
$pdf->SetTitle('Facture #' . str_pad($reservation['id_reservation'], 6, '0', STR_PAD_LEFT));
$pdf->SetSubject('Facture Location Matériel');

// Supprimer header/footer par défaut
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Marges
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);

// Ajouter page
$pdf->AddPage();

// Police par défaut
$pdf->SetFont('helvetica', '', 10);

// ============================================
// CONTENU PDF
// ============================================

// Logo (si existe)
$logo_path = 'images/logo.png';
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 15, 15, 40, 0, 'PNG');
}

// Titre PLUTINA EVENT
$pdf->SetFont('helvetica', 'B', 20);
$pdf->SetTextColor(212, 175, 55); // Couleur gold
$pdf->Cell(0, 10, 'PLUTINA EVENT', 0, 1, 'R');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, 'Location Matériel Événementiel', 0, 1, 'R');
$pdf->Cell(0, 5, 'Ambatobe, Antananarivo, Madagascar', 0, 1, 'R');
$pdf->Cell(0, 5, 'Tél: 034 34 661 49 / 032 52 500 60', 0, 1, 'R');
$pdf->Cell(0, 5, 'Email: hello@plutina-events.com', 0, 1, 'R');

$pdf->Ln(10);

// Ligne séparation
$pdf->SetDrawColor(212, 175, 55);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

$pdf->Ln(10);

// Titre FACTURE
$pdf->SetFont('helvetica', 'B', 18);
$pdf->SetTextColor(212, 175, 55);
$pdf->Cell(0, 10, 'FACTURE / REÇU', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

$pdf->Ln(5);

// Informations réservation & client (2 colonnes)
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 6, 'INFORMATIONS RÉSERVATION', 0, 0, 'L');
$pdf->Cell(95, 6, 'INFORMATIONS CLIENT', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 9);

// Colonne Gauche - Réservation
$y_start = $pdf->GetY();

$pdf->Cell(95, 5, 'Numéro: #' . str_pad($reservation['id_reservation'], 6, '0', STR_PAD_LEFT), 0, 1, 'L');

$date = new DateTime($reservation['date_reservation']);
$pdf->Cell(95, 5, 'Date: ' . $date->format('d/m/Y à H:i'), 0, 1, 'L');

// Badge statut
$statut_label = ucfirst(str_replace('_', ' ', $reservation['statut']));
$pdf->Cell(95, 5, 'Statut: ' . $statut_label, 0, 1, 'L');

// Colonne Droite - Client
$pdf->SetY($y_start);
$pdf->SetX(110);

$pdf->Cell(95, 5, 'Nom: ' . $reservation['prenom'] . ' ' . $reservation['nom'], 0, 1, 'L');
$pdf->SetX(110);
$pdf->Cell(95, 5, 'Email: ' . $reservation['email'], 0, 1, 'L');
$pdf->SetX(110);
$pdf->Cell(95, 5, 'Tél: ' . $reservation['telephone'], 0, 1, 'L');

$pdf->Ln(10);

// ============================================
// TABLEAU MATÉRIELS
// ============================================

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(212, 175, 55);
$pdf->SetTextColor(255, 255, 255);

// En-tête tableau
$pdf->Cell(70, 7, 'MATÉRIEL', 1, 0, 'L', true);
$pdf->Cell(30, 7, 'DATES', 1, 0, 'C', true);
$pdf->Cell(15, 7, 'QTÉ', 1, 0, 'C', true);
$pdf->Cell(30, 7, 'PRIX/JOUR', 1, 0, 'R', true);
$pdf->Cell(35, 7, 'TOTAL', 1, 1, 'R', true);

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);

// Lignes matériels
foreach ($lignes as $ligne) {
    $debut = new DateTime($ligne['date_debut']);
    $fin = new DateTime($ligne['date_fin']);
    $interval = $debut->diff($fin);
    $nb_jours = $interval->days + 1;
    $subtotal = $ligne['prix_unitaire'] * $ligne['quantite'] * $nb_jours;

    // Nom matériel
    $pdf->Cell(70, 6, $ligne['nom_materiel'], 1, 0, 'L');

    // Dates
    $dates_str = $debut->format('d/m') . '-' . $fin->format('d/m') . ' (' . $nb_jours . 'j)';
    $pdf->Cell(30, 6, $dates_str, 1, 0, 'C');

    // Quantité
    $pdf->Cell(15, 6, $ligne['quantite'], 1, 0, 'C');

    // Prix unitaire
    $pdf->Cell(30, 6, number_format($ligne['prix_unitaire'], 0, ',', ' ') . ' Ar', 1, 0, 'R');

    // Total
    $pdf->Cell(35, 6, number_format($subtotal, 0, ',', ' ') . ' Ar', 1, 1, 'R');
}

$pdf->Ln(5);

// ============================================
// TOTAUX
// ============================================

$pdf->SetFont('helvetica', 'B', 10);

// Total Location
$pdf->Cell(145, 7, 'TOTAL LOCATION', 1, 0, 'R');
$pdf->SetTextColor(212, 175, 55);
$pdf->Cell(35, 7, number_format($reservation['montant_total'], 0, ',', ' ') . ' Ar', 1, 1, 'R');

$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);

// Acompte
$pdf->Cell(145, 6, 'Acompte versé (50%)', 1, 0, 'R');
$pdf->SetTextColor(0, 128, 0);
$pdf->Cell(35, 6, number_format($reservation['acompte_verse'], 0, ',', ' ') . ' Ar', 1, 1, 'R');

// Caution
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(145, 6, 'Caution (25%)', 1, 0, 'R');
$pdf->SetTextColor(255, 140, 0);
$pdf->Cell(35, 6, number_format($reservation['caution'], 0, ',', ' ') . ' Ar', 1, 1, 'R');

// Reste à payer
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(145, 7, 'RESTE À PAYER', 1, 0, 'R');
$pdf->SetTextColor(255, 0, 0);
$pdf->Cell(35, 7, number_format($reservation['montant_restant'], 0, ',', ' ') . ' Ar', 1, 1, 'R');

$pdf->Ln(10);

// ============================================
// VALEUR MATÉRIEL (CAUTION)
// ============================================

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(255, 140, 0);
$pdf->Cell(0, 6, 'VALEUR DU MATÉRIEL LOUÉ (CAUTION)', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$pdf->SetTextColor(0, 0, 0);

foreach ($lignes as $ligne) {
    $pdf->Cell(150, 5, '• ' . $ligne['nom_materiel'] . ' (' . $ligne['quantite'] . '×)', 0, 0, 'L');
    $pdf->Cell(30, 5, number_format($ligne['prix_remboursement'], 0, ',', ' ') . ' Ar/unité', 0, 1, 'R');
}

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(150, 6, 'Total valeur matériel:', 0, 0, 'L');
$pdf->SetTextColor(255, 140, 0);
$pdf->Cell(30, 6, number_format($valeur_materiel, 0, ',', ' ') . ' Ar', 0, 1, 'R');

$pdf->SetFont('helvetica', 'I', 7);
$pdf->SetTextColor(100, 100, 100);
$pdf->MultiCell(0, 4, 'En cas de perte ou casse, le prix de remplacement sera déduit de la caution (25%). Si insuffisant, un complément vous sera demandé.', 0, 'L');

$pdf->Ln(10);

// ============================================
// CONDITIONS & FOOTER
// ============================================

$pdf->SetDrawColor(212, 175, 55);
$pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(0, 5, 'CONDITIONS GÉNÉRALES', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 7);
$pdf->MultiCell(
    0,
    4,
    "• Le matériel doit être retourné propre et en bon état.\n" .
        "• La caution sera remboursée après vérification du matériel retourné.\n" .
        "• Toute perte ou casse entraînera une retenue sur la caution.\n" .
        "• Le solde restant doit être réglé lors de la récupération du matériel.\n" .
        "• PLUTINA EVENT se réserve le droit de modifier les conditions sans préavis.",
    0,
    'L'
);

$pdf->Ln(10);

// Footer
$pdf->SetFont('helvetica', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, 'Merci pour votre confiance! - PLUTINA EVENT', 0, 1, 'C');
$pdf->Cell(0, 5, 'Document généré le ' . date('d/m/Y à H:i'), 0, 1, 'C');

// ============================================
// OUTPUT PDF
// ============================================

$filename = 'Facture_PLUTINA_' . str_pad($reservation['id_reservation'], 6, '0', STR_PAD_LEFT) . '.pdf';

// Force téléchargement
$pdf->Output($filename, 'D');
exit;
