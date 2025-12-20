<?php
// Test connexion database
require_once 'includes/config.php';

echo "<h1>🧪 TEST CONNEXION DATABASE</h1>";

// Test simple query
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM materiel");
    $result = $stmt->fetch();

    echo "<p>✅ Connexion réussie!</p>";
    echo "<p>📦 Nombre matériels dans BDD: <strong>" . $result['total'] . "</strong></p>";

    // Liste catégories
    $stmt = $pdo->query("SELECT * FROM categorie");
    $categories = $stmt->fetchAll();

    echo "<h3>📂 Catégories:</h3>";
    echo "<ul>";
    foreach ($categories as $cat) {
        echo "<li>" . $cat['nom_categorie'] . "</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p>❌ ERREUR: " . $e->getMessage() . "</p>";
}
