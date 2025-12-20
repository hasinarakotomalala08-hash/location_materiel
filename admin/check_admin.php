<?php
// Protection admin - include ao début fichiers admin rehetra
session_start();

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../connexion.php');
    exit;
}

// Vérifier si admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Pas admin - rediriger vers page client
    header('Location: ../mon-compte.php');
    exit;
}

// Include config
require_once '../includes/config.php';
