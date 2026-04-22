<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'config/database.php'; // adapte le chemin si nécessaire

// Récupérer le nombre d'élèves
$stmt = $pdo->query("SELECT COUNT(*) FROM eleves");
$total_eleves = $stmt->fetchColumn();

// Récupérer le nombre de classes
$stmt = $pdo->query("SELECT COUNT(*) FROM classes");
$total_classes = $stmt->fetchColumn();

// Récupérer le nombre de matières
$stmt = $pdo->query("SELECT COUNT(*) FROM matieres");
$total_matieres = $stmt->fetchColumn();

// Récupérer le nombre d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) FROM utilisateurs");
$total_utilisateurs = $stmt->fetchColumn();

// Nombre total d'années scolaires
$stmt = $pdo->query("SELECT COUNT(*) FROM annees_scolaires");
$total_annees = $stmt->fetchColumn();

// Nombre total de trimestres
$stmt = $pdo->query("SELECT COUNT(*) FROM trimestres");
$total_trimestres = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Système de Gestion Scolaire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    :root {
        --primary: #2c3e50;
        --secondary: #3498db;
        --accent: #9b59b6;
        --success: #2ecc71;
        --warning: #f39c12;
        --danger: #e74c3c;
        --light: #ecf0f1;
        --dark: #2c3e50;
        --gray: #95a5a6;
        --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    body {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        color: #333;
    }

    /* Header */
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary) 0%, #34495e 100%);
        color: white;
        padding: 20px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .header-left h1 {
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header-left h1 i {
        color: var(--secondary);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-badge {
        background: rgba(255, 255, 255, 0.15);
        padding: 8px 16px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-badge i {
        color: var(--success);
    }

    .logout-btn {
        background: var(--danger);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        transition: all 0.3s;
    }

    .logout-btn:hover {
        background: #c0392b;
        transform: translateY(-2px);
    }

    /* Navigation */
    .main-nav {
        background: white;
        margin: 20px 40px;
        border-radius: 12px;
        padding: 20px;
        box-shadow: var(--shadow);
    }

    .nav-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
    }

    .nav-item {
        background: var(--light);
        padding: 15px;
        border-radius: 8px;
        text-decoration: none;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s;
        border-left: 4px solid var(--secondary);
    }

    .nav-item:hover {
        background: var(--secondary);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    }

    .nav-item i {
        font-size: 1.2rem;
        width: 24px;
    }

    /* Dashboard Cards */
    .dashboard-section {
        padding: 0 40px 40px;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 25px;
        color: var(--primary);
    }

    .section-title i {
        background: var(--secondary);
        color: white;
        padding: 10px;
        border-radius: 8px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 25px;
    }

    .dashboard-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        box-shadow: var(--shadow);
        transition: all 0.3s;
        border-top: 5px solid var(--secondary);
        position: relative;
        overflow: hidden;
    }

    .dashboard-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }

    .card-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--secondary) 0%, #2980b9 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        color: white;
        font-size: 1.5rem;
    }

    .card-content h3 {
        color: var(--primary);
        margin-bottom: 10px;
        font-size: 1.3rem;
    }

    .card-content p {
        color: var(--gray);
        line-height: 1.5;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }

    .card-action {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 15px;
    }

    .card-btn {
        background: var(--primary);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        transition: all 0.3s;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .card-btn:hover {
        background: var(--secondary);
        transform: translateY(-2px);
    }

    .card-stats {
        color: var(--secondary);
        font-weight: bold;
        font-size: 0.9rem;
    }

    /* Quick Stats */
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: var(--shadow);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.3rem;
    }

    .stat-content h4 {
        color: var(--gray);
        font-size: 0.9rem;
        margin-bottom: 5px;
    }

    .stat-number {
        color: var(--primary);
        font-size: 1.8rem;
        font-weight: bold;
    }

    /* Footer */
    .dashboard-footer {
        background: var(--primary);
        color: white;
        padding: 25px 40px;
        margin-top: 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .footer-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .footer-left i {
        color: var(--secondary);
        font-size: 1.5rem;
    }

    .footer-right {
        display: flex;
        gap: 20px;
    }

    .footer-link {
        color: var(--light);
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-link:hover {
        color: var(--secondary);
    }

    /* Responsive */
    @media (max-width: 768px) {

        .dashboard-header,
        .main-nav,
        .dashboard-section {
            padding: 20px;
        }

        .dashboard-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .nav-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .dashboard-grid {
            grid-template-columns: 1fr;
        }

        .dashboard-footer {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dashboard-card,
    .nav-item,
    .stat-card {
        animation: fadeIn 0.5s ease-out;
    }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-school"></i> Tableau de Bord Scolaire</h1>
        </div>
        <div class="user-info">
            <div class="user-badge">
                <i class="fas fa-user-circle"></i>
                <span>Connecté en tant que : <strong><?= htmlspecialchars($_SESSION['user_role']) ?></strong></span>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </div>
    </header>



    <div class="quick-stats">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h4>Total Élèves</h4>
                <div class="stat-number"><?= $total_eleves ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h4>Classes</h4>
                <div class="stat-number"><?= $total_classes ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                <i class="fas fa-book-open"></i>
            </div>
            <div class="stat-content">
                <h4>Matières</h4>
                <div class="stat-number"><?= $total_matieres ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i class="fas fa-users-cog"></i>
            </div>
            <div class="stat-content">
                <h4>Utilisateurs</h4>
                <div class="stat-number"><?= $total_utilisateurs ?></div>
            </div>
        </div>
    </div>


    <!-- Tableau de Bord Principal -->
    <div class="dashboard-section">
        <h2 class="section-title"><i class="fas fa-tachometer-alt"></i> Modules Principaux</h2>
        <div class="dashboard-grid">
            <!-- Carte Classes -->
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="card-content">
                    <h3>Gestion des Classes</h3>
                    <p>Créer, modifier et consulter les classes. Affecter des professeurs et suivre les effectifs.</p>
                    <div class="card-action">
                        <a href="classes/liste.php" class="card-btn">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                        <span class="card-stats"><?= $total_classes ?> classes</span>
                    </div>
                </div>
            </div>

            <!-- Carte Élèves -->
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="card-content">
                    <h3>Gestion des Élèves</h3>
                    <p>Inscrire, archiver et suivre le parcours des élèves. Consulter les informations détaillées.</p>
                    <div class="card-action">
                        <a href="eleves/liste.php" class="card-btn">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                        <span class="card-stats"><?= $total_eleves ?> élèves</span>
                    </div>
                </div>
            </div>

            <!-- Carte Matières -->
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-book-open"></i>
                </div>
                <div class="card-content">
                    <h3>Gestion des Matières</h3>
                    <p>Configurer les matières enseignées, les coefficients et les professeurs responsables.</p>
                    <div class="card-action">
                        <a href="matieres/liste.php" class="card-btn">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                        <span class="card-stats"><?= $total_matieres ?> matières</span>
                    </div>
                </div>
            </div>

            <!-- Carte Notes -->
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="card-content">
                    <h3>Saisie des Notes</h3>
                    <p>Saisir et modifier les notes des élèves. Calculer les moyennes et statistiques.</p>
                    <div class="card-action">
                        <a href="notes/classes.php" class="card-btn">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                        <span class="card-stats"></span>
                    </div>
                </div>
            </div>

            <!-- Carte Trimestres -->
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="card-content">
                    <h3>Gestion des Périodes</h3>
                    <p>Configurer et activer les trimestres. Définir les dates importantes et échéances.</p>
                    <div class="card-action">
                        <a href="trimestres/liste.php" class="card-btn">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                        <span class="card-stats"><?= $total_trimestres ?> trimestres</span>
                    </div>
                </div>
            </div>

            <!-- Carte Années Scolaires -->
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="card-content">
                    <h3>Années Scolaires</h3>
                    <p>Gérer les années scolaires. Clôturer une année et préparer la suivante.</p>
                    <div class="card-action">
                        <a href="annees/activer.php" class="card-btn">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                        <span class="card-stats"><?=  $total_annees?> années inscrites</span>
                    </div>
                </div>
            </div>

            <!-- Carte Paramètres
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="card-content">
                    <h3>Paramètres Système</h3>
                    <p>Configurer les préférences, les seuils de notes, et les paramètres d'impression.</p>
                    <div class="card-action">
                        <a href="parametres/index.php" class="card-btn">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                        <span class="card-stats">Configuration</span>
                    </div>
                </div>
            </div> -->

            <!-- Carte Bulletins -->
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <div class="card-content">
                    <h3>Génération de Bulletins</h3>
                    <p>Générer, prévisualiser et imprimer les bulletins scolaires avec options avancées.</p>
                    <div class="card-action">
                        <a href="bulletins/bulletins.php" class="card-btn">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                        <span class="card-stats">Prêt à générer</span>
                    </div>
                </div>
            </div>

            <!-- Carte Utilisateurs -->
            <div class="dashboard-card">
                <div class="card-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="card-content">
                    <h3>Gestion des Utilisateurs</h3>
                    <p>Créer et gérer les comptes utilisateurs, permissions et rôles d'accès.</p>
                    <div class="card-action">
                        <a href="utilisateurs/liste.php" class="card-btn">
                            <i class="fas fa-arrow-right"></i> Accéder
                        </a>
                        <span class="card-stats"><?= $total_utilisateurs ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="dashboard-footer">
        <div class="footer-left">
            <i class="fas fa-graduation-cap"></i>
            <div>
                <h3>Système de Gestion Scolaire Pro</h3>
                <p>Version 0.0.1 • <?= date('d/m/Y') ?></p>
            </div>
        </div>
        <!-- <div class="footer-right">
            <a href="#" class="footer-link">Aide & Support</a>
            <a href="#" class="footer-link">Documentation</a>
            <a href="#" class="footer-link">Mentions Légales</a>
        </div> -->
    </footer>

    <script>
    // Animation simple au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.dashboard-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });
    });
    </script>
</body>

</html>