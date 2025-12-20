<?php
// Page déconnexion
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire le cookie de session si existe
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers page connexion avec message
header('Location: connexion.php?deconnexion=1');
exit;
