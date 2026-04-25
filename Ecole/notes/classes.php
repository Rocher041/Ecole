<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

// Récupérer toutes les classes
$stmt = $pdo->query("SELECT * FROM classes ORDER BY nom_classe");
$classes = $stmt->fetchAll();

// Récupérer le nombre d'élèves par classe
$stmt = $pdo->query("
    SELECT classe_id, COUNT(*) as nb_eleves 
    FROM eleves 
    GROUP BY classe_id
");
$eleves_par_classe = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Récupérer le nombre de notes par classe
$stmt = $pdo->query("
    SELECT e.classe_id, COUNT(*) as nb_notes 
    FROM notes n
    JOIN eleves e ON n.eleve_id = e.id
    GROUP BY e.classe_id
");
$notes_par_classe = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classes | Gestion Scolaire</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 24px;
        box-shadow: 0 25px 70px rgba(2, 132, 199, 0.15);
        overflow: hidden;
        animation: fadeIn 0.6s ease-out;
    }

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

    .header {
        background: linear-gradient(135deg, #0369a1 0%, #0c4a6e 100%);
        color: white;
        padding: 40px;
        position: relative;
        overflow: hidden;
    }

    .header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 200" opacity="0.1"><path d="M0,100 C150,200 350,0 500,100 C650,200 850,0 1000,100 L1000,200 L0,200 Z" fill="white"/></svg>');
        background-size: cover;
    }

    .header-content {
        position: relative;
        z-index: 2;
    }

    .header h1 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header p {
        font-size: 16px;
        opacity: 0.9;
        max-width: 600px;
    }

    .content {
        padding: 40px;
    }

    .page-title {
        font-size: 28px;
        color: #1e293b;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .page-title i {
        color: #0369a1;
        font-size: 32px;
    }

    .classes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }

    .class-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
        position: relative;
    }

    .class-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 50px rgba(3, 105, 161, 0.2);
        border-color: #0369a1;
    }

    .class-header {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        padding: 30px;
        position: relative;
        overflow: hidden;
    }

    .class-header::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 200%;
        background: rgba(255, 255, 255, 0.1);
        transform: rotate(45deg);
    }

    .class-icon {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 20px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .class-name {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .class-desc {
        font-size: 14px;
        opacity: 0.9;
    }

    .class-body {
        padding: 25px;
    }

    .stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }

    .view-btn {
        display: block;
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #0369a1, #0c4a6e);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .view-btn:hover {
        background: linear-gradient(135deg, #0284c7, #0369a1);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(3, 105, 161, 0.3);
    }

    .nav-links {
        display: flex;
        justify-content: center;
        gap: 20px;
        padding: 40px;
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
        flex-wrap: wrap;
    }

    .nav-links a {
        padding: 16px 30px;
        background: white;
        color: #475569;
        text-decoration: none;
        border-radius: 16px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
        border: 2px solid #e2e8f0;
    }

    .nav-links a:hover {
        background: #0369a1;
        color: white;
        border-color: #0369a1;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(3, 105, 161, 0.2);
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #64748b;
    }

    .empty-state i {
        font-size: 80px;
        color: #cbd5e0;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        font-size: 24px;
        margin-bottom: 10px;
        color: #475569;
    }

    .stats-summary {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .summary-card {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        padding: 25px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
    }

    .summary-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: white;
    }

    .summary-icon.classes {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    }

    .summary-icon.students {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .summary-icon.notes {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    }

    .summary-content h3 {
        font-size: 28px;
        color: #1e293b;
        margin-bottom: 5px;
    }

    .summary-content p {
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .stats-summary {
            grid-template-columns: 1fr;
        }

        .classes-grid {
            grid-template-columns: 1fr;
        }

        .header {
            padding: 30px 20px;
        }

        .content {
            padding: 20px;
        }
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1><i class="fas fa-school"></i> Classes de l'Établissement</h1>
                <p>Sélectionnez une classe pour accéder à ses matières et notes</p>
            </div>
        </div>

        <div class="content">
            <div class="page-title">
                <i class="fas fa-graduation-cap"></i>
                <span>Liste des Classes</span>
            </div>

            <div class="stats-summary">
                <div class="summary-card">
                    <div class="summary-icon classes">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="summary-content">
                        <h3><?php echo count($classes); ?></h3>
                        <p>Classes</p>
                    </div>
                </div>

                <div class="summary-card">
                    <div class="summary-icon students">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="summary-content">
                        <h3><?php echo array_sum($eleves_par_classe); ?></h3>
                        <p>Élèves au total</p>
                    </div>
                </div>


            </div>

            <?php if (empty($classes)): ?>
            <div class="empty-state">
                <i class="fas fa-chalkboard"></i>
                <h3>Aucune classe créée</h3>
                <p>Commencez par créer des classes pour organiser votre établissement</p>
            </div>
            <?php else: ?>
            <div class="classes-grid">
                <?php foreach ($classes as $classe):
                        $nb_eleves = $eleves_par_classe[$classe['id']] ?? 0;
                        $nb_notes = $notes_par_classe[$classe['id']] ?? 0;
                        $class_icon = $classe['nom_classe'][0]; // Première lettre pour l'icône
                    ?>
                <div class="class-card" onclick="window.location.href='matieres.php?classe_id=<?= $classe['id'] ?>'">
                    <div class="class-header">
                        <div class="class-icon">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <h3 class="class-name"><?php echo htmlspecialchars($classe['nom_classe']); ?></h3>
                        <p class="class-desc">Classe du collège</p>
                    </div>
                    <div class="class-body">
                        <div class="stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $nb_eleves; ?></div>
                                <div class="stat-label">Élèves</div>
                            </div>

                        </div>
                        <a href="matieres.php?classe_id=<?= $classe['id'] ?>" class="view-btn">
                            <i class="fas fa-arrow-right"></i> Voir les matières
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="nav-links">
            <a href="../accueil.php">
                <i class="fas fa-home"></i> Tableau de bord
            </a>
            <a href="../eleves/liste.php">
                <i class="fas fa-users"></i> Gérer les élèves
            </a>
        </div>
    </div>

    <script>
    // Animation des cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.class-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
            card.style.animation = 'fadeIn 0.5s ease-out forwards';
            card.style.opacity = '0';
        });
    });
    </script>
</body>

</html>