<?php
// ============================================
// CONFIGURATION BASE DE DONNÉES
// ============================================

// Informations de connexion
define('DB_HOST', 'localhost');
define('DB_NAME', 'location_materiel');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion à la base de données avec PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("❌ ERREUR Connexion Database: " . $e->getMessage());
}

// Démarrer session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone Madagascar
date_default_timezone_set('Indian/Antananarivo');
