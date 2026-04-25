<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

// Vérifier si une classe est spécifiée
if (!isset($_GET['classe_id'])) {
    header("Location: classes.php");
    exit;
}

$classe_id = (int)$_GET['classe_id'];

// Récupérer les informations de la classe
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$classe_id]);
$classe = $stmt->fetch();

if (!$classe) {
    header("Location: classes.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        m.id,
        m.nom_matiere,
        cm.coefficient
    FROM matieres m
    INNER JOIN classe_matiere cm ON cm.matiere_id = m.id
    WHERE cm.classe_id = ?
    ORDER BY m.nom_matiere
");
$stmt->execute([$classe_id]);
$matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Récupérer les statistiques par matière pour cette classe
$stmt = $pdo->prepare("
    SELECT 
        m.id,
        COUNT(n.id) AS nb_notes,
        AVG(n.note) AS moyenne,
        COUNT(DISTINCT n.eleve_id) AS nb_eleves_notes
    FROM matieres m
    INNER JOIN classe_matiere cm ON cm.matiere_id = m.id
    LEFT JOIN notes n 
        ON n.matiere_id = m.id
    LEFT JOIN eleves e 
        ON e.id = n.eleve_id AND e.classe_id = ?
    WHERE cm.classe_id = ?
    GROUP BY m.id
");
$stmt->execute([$classe_id, $classe_id]);
$stats_bruts = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stats_matieres = [];
foreach ($stats_bruts as $row) {
    $stats_matieres[$row['id']] = [
        'nb_notes' => (int)$row['nb_notes'],
        'moyenne' => (float)$row['moyenne'],
        'nb_eleves_notes' => (int)$row['nb_eleves_notes']
    ];
}


// Récupérer les élèves de la classe
$stmt = $pdo->prepare("SELECT COUNT(*) FROM eleves WHERE classe_id = ?");
$stmt->execute([$classe_id]);
$nb_eleves = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matières | <?php echo htmlspecialchars($classe['nom_classe']); ?></title>
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
        background: linear-gradient(135deg, #10b981 0%, #047857 100%);
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

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 24px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        margin-bottom: 20px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateX(-5px);
    }

    .header h1 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header-info {
        display: flex;
        align-items: center;
        gap: 30px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.15);
        padding: 10px 20px;
        border-radius: 12px;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
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

    .matieres-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 20px;
    }

    .matiere-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid transparent;
    }

    .matiere-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 50px rgba(16, 185, 129, 0.2);
        border-color: #10b981;
    }

    .matiere-header {
        padding: 30px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-bottom: 2px solid #e2e8f0;
    }

    .matiere-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #10b981, #047857);
        color: white;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 20px;
    }

    .matiere-name {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 5px;
    }

    .matiere-coeff {
        font-size: 14px;
        color: #64748b;
        background: #e2e8f0;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-block;
    }

    .matiere-body {
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
        background: linear-gradient(135deg, #10b981, #047857);
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
        background: linear-gradient(135deg, #34d399, #10b981);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
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
        background: #10b981;
        color: white;
        border-color: #10b981;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);
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

    .class-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .class-stat-card {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        padding: 25px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
    }

    .class-stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: white;
    }

    .class-stat-icon.subjects {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    }

    .class-stat-icon.students {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    }

    .class-stat-icon.avg {
        background: linear-gradient(135deg, #10b981, #047857);
    }

    .class-stat-content h3 {
        font-size: 28px;
        color: #1e293b;
        margin-bottom: 5px;
    }

    .class-stat-content p {
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .class-stats {
            grid-template-columns: 1fr;
        }

        .matieres-grid {
            grid-template-columns: 1fr;
        }

        .header {
            padding: 30px 20px;
        }

        .content {
            padding: 20px;
        }

        .header-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <a href="classes.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Retour aux classes
                </a>
                <h1><i class="fas fa-book"></i> Matières - <?php echo htmlspecialchars($classe['nom_classe']); ?></h1>
                <p>Sélectionnez une matière pour voir et gérer les notes</p>

                <div class="header-info">
                    <div class="info-item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Classe: <?php echo htmlspecialchars($classe['nom_classe']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $nb_eleves; ?> élèves</span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-book"></i>
                        <span><?php echo count($matieres); ?> matières</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="class-stats">
                <div class="class-stat-card">
                    <div class="class-stat-icon subjects">
                        <i class="fas fa-books"></i>
                    </div>
                    <div class="class-stat-content">
                        <h3><?php echo count($matieres); ?></h3>
                        <p>Matières enseignées</p>
                    </div>
                </div>

                <div class="class-stat-card">
                    <div class="class-stat-icon students">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="class-stat-content">
                        <h3><?php echo $nb_eleves; ?></h3>
                        <p>Élèves dans la classe</p>
                    </div>
                </div>

                <div class="class-stat-card">
                    <div class="class-stat-icon avg">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="class-stat-content">
                        <h3>
                            <?php
                            $total_notes = 0;
                            $somme_notes = 0;
                            foreach ($stats_matieres as $stat) {
                                $total_notes += $stat['nb_notes'];
                                $somme_notes += $stat['moyenne'] * $stat['nb_notes'];
                            }
                            echo $total_notes > 0 ? number_format($somme_notes / $total_notes, 2) : '0.00';
                            ?>/20
                        </h3>
                        <p>Moyenne générale</p>
                    </div>
                </div>
            </div>

            <div class="page-title">
                <i class="fas fa-book-open"></i>
                <span>Toutes les matières</span>
            </div>

            <?php if (empty($matieres)): ?>
            <div class="empty-state">
                <i class="fas fa-books"></i>
                <h3>Aucune matière créée</h3>
                <p>Créez des matières pour commencer à saisir des notes</p>
            </div>
            <?php else: ?>
            <div class="matieres-grid">
                <?php foreach ($matieres as $matiere):
                        $stats = $stats_matieres[$matiere['id']] ?? ['nb_notes' => 0, 'moyenne' => 0, 'nb_eleves_notes' => 0];
                        $moyenne = $stats['moyenne'] > 0 ? number_format($stats['moyenne'], 2) : '0.00';
                        $pourcentage = $nb_eleves > 0 ? round(($stats['nb_eleves_notes'] / $nb_eleves) * 100) : 0;
                    ?>
                <div class="matiere-card"
                    onclick="window.location.href='notes_matiere.php?classe_id=<?= $classe_id ?>&matiere_id=<?= $matiere['id'] ?>'">
                    <div class="matiere-header">
                        <div class="matiere-icon">
                            <?php echo strtoupper(substr($matiere['nom_matiere'], 0, 1)); ?>
                        </div>
                        <h3 class="matiere-name"><?php echo htmlspecialchars($matiere['nom_matiere']); ?></h3>
                        <span class="matiere-coeff">Coefficient: <?php echo $matiere['coefficient']; ?></span>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="nav-links">
            <a href="classes.php">
                <i class="fas fa-arrow-left"></i> Retour aux classes
            </a>
            <a href="../accueil.php">
                <i class="fas fa-home"></i> Tableau de bord
            </a>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.matiere-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
            card.style.animation = 'fadeIn 0.5s ease-out forwards';
            card.style.opacity = '0';
        });
    });
    </script>
</body>

</html>