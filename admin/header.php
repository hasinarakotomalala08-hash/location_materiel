<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin - PLUTINA EVENT'; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <!-- Navbar Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt text-warning"></i>
                <strong>ADMIN</strong> PLUTINA EVENT
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gestion-reservations.php">
                            <i class="fas fa-calendar-check"></i> Réservations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gestion-materiels.php">
                            <i class="fas fa-boxes"></i> Matériels
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gestion-clients.php">
                            <i class="fas fa-users"></i> Clients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gestion-admins.php">
                            <i class="fas fa-user-shield"></i> Admins
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="messages-contact.php">
                            <i class="fas fa-inbox"></i> Messages
                            <?php
                            // Badge non lus
                            $stmt_notif = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE lu = FALSE");
                            $nb_notif = $stmt_notif->fetchColumn();
                            if ($nb_notif > 0):
                            ?>
                                <span class="badge bg-danger"><?php echo $nb_notif; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt"></i> Voir site
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i>
                            <?php echo htmlspecialchars($_SESSION['user_nom']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="../mon-compte.php">
                                    <i class="fas fa-user"></i> Mon compte
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="../deconnexion.php">
                                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>