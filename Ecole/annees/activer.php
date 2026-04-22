<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";

// Activation d'une année
if (isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];

    // Désactiver toutes les années
    $pdo->query("UPDATE annees_scolaires SET active = 0");

    // Activer celle sélectionnée
    $stmt = $pdo->prepare("UPDATE annees_scolaires SET active = 1 WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Année scolaire activée avec succès !";
    } else {
        $message = "Erreur lors de l'activation.";
    }
}

// Récupération des années
$stmt = $pdo->query("SELECT * FROM annees_scolaires ORDER BY id ASC");
$annees = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Années Scolaires | Scolarité</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --secondary: #f0f9ff;
        --success: #10b981;
        --error: #ef4444;
        --warning: #f59e0b;
        --light: #f8fafc;
        --dark: #1e293b;
        --gray: #64748b;
        --border: #e2e8f0;
        --shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        --radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #dbeafe 100%);
        min-height: 100vh;
        padding: 30px 20px;
        color: var(--dark);
        line-height: 1.6;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .header-content h1 {
        font-size: 36px;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 8px;
    }

    .header-content p {
        color: var(--gray);
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-content p i {
        color: var(--primary);
    }

    .action-buttons {
        display: flex;
        gap: 15px;
    }

    .btn {
        padding: 15px 28px;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        font-size: 15px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
    }

    .btn-secondary {
        background: white;
        color: var(--dark);
        border: 2px solid var(--border);
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 40px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        margin-bottom: 30px;
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert {
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        font-weight: 500;
        animation: fadeIn 0.5s ease-out;
        border-left: 5px solid;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .alert-success {
        background: linear-gradient(135deg, #d1fae5, #ecfdf5);
        color: var(--success);
        border-left-color: var(--success);
    }

    .alert-error {
        background: linear-gradient(135deg, #fee2e2, #fef2f2);
        color: var(--error);
        border-left-color: var(--error);
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        transition: var(--transition);
        border: 1px solid var(--border);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        font-size: 24px;
    }

    .stat-active {
        background: linear-gradient(135deg, #d1fae5, #10b981);
        color: white;
    }

    .stat-total {
        background: linear-gradient(135deg, #e0f2fe, #0ea5e9);
        color: white;
    }

    .stat-number {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 8px;
        color: var(--dark);
    }

    .stat-label {
        color: var(--gray);
        font-size: 15px;
        font-weight: 500;
    }

    .table-container {
        overflow-x: auto;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: white;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    .table thead {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    }

    .table th {
        padding: 22px 20px;
        text-align: left;
        color: white;
        font-weight: 600;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
    }

    .table th:first-child {
        border-top-left-radius: 16px;
    }

    .table th:last-child {
        border-top-right-radius: 16px;
    }

    .table td {
        padding: 20px;
        border-bottom: 1px solid var(--border);
        color: var(--dark);
        font-weight: 500;
    }

    .table tbody tr {
        transition: var(--transition);
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 14px;
    }

    .status-active {
        background: linear-gradient(135deg, #d1fae5, #10b981);
        color: white;
    }

    .status-inactive {
        background: linear-gradient(135deg, #f1f5f9, #94a3b8);
        color: white;
    }

    .action-buttons-cell {
        display: flex;
        gap: 12px;
    }

    .btn-action {
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        font-size: 14px;
    }

    .btn-activate {
        background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        color: white;
    }

    .btn-disabled {
        background: linear-gradient(135deg, #f1f5f9, #cbd5e1);
        color: var(--gray);
        cursor: default;
    }

    .btn-activate:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
    }

    .footer {
        text-align: center;
        margin-top: 50px;
        padding: 25px;
        color: var(--gray);
        font-size: 14px;
        border-top: 1px solid var(--border);
        background: white;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .footer i {
        color: var(--primary);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray);
    }

    .empty-state i {
        font-size: 64px;
        color: var(--border);
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            text-align: center;
        }

        .header-content {
            text-align: center;
        }

        .action-buttons {
            flex-direction: column;
            width: 100%;
        }

        .btn {
            justify-content: center;
        }

        .stats-cards {
            grid-template-columns: 1fr;
        }

        .glass-card {
            padding: 25px;
        }
    }

    .pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>Gestion des Années Scolaires</h1>
                <p>
                    <i class="fas fa-calendar-alt"></i>
                    Gérez et activez les années académiques de votre établissement
                </p>
            </div>
            <div class="action-buttons">
                <a href="ajouter.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    Nouvelle Année
                </a>
                <a href="../accueil.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Tableau de Bord
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert <?php echo strpos($message, 'succès') !== false ? 'alert-success' : 'alert-error'; ?>">
            <i
                class="fas <?php echo strpos($message, 'succès') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>

        <?php 
            $activeCount = 0;
            $totalCount = count($annees);
            foreach ($annees as $annee) {
                if ($annee['active']) $activeCount++;
            }
        ?>

        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon stat-total">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-number"><?php echo $totalCount; ?></div>
                <div class="stat-label">Années Scolaires</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-active">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number"><?php echo $activeCount; ?></div>
                <div class="stat-label">Année Active</div>
            </div>
        </div>

        <div class="glass-card">
            <?php if (empty($annees)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>Aucune année scolaire enregistrée</h3>
                <p>Commencez par ajouter une nouvelle année scolaire</p>
                <a href="ajouter.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i>
                    Ajouter la première année
                </a>
            </div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-graduation-cap"></i> Libellé</th>
                            <th><i class="fas fa-chart-line"></i> Statut</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($annees as $annee): ?>
                        <tr>
                            <td><strong>#<?php echo $annee['id']; ?></strong></td>
                            <td>
                                <div style="font-size: 18px; font-weight: 700; color: var(--dark);">
                                    <?php echo htmlspecialchars($annee['libelle']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($annee['active']): ?>
                                <span class="status-badge status-active pulse">
                                    <i class="fas fa-check-circle"></i>
                                    Active
                                </span>
                                <?php else: ?>
                                <span class="status-badge status-inactive">
                                    <i class="fas fa-times-circle"></i>
                                    Inactive
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons-cell">
                                    <?php if (!$annee['active']): ?>
                                    <a href="?activate=<?php echo $annee['id']; ?>" class="btn-action btn-activate"
                                        onclick="return confirm('Voulez-vous vraiment activer l\'année scolaire <?php echo htmlspecialchars($annee['libelle']); ?> ?')">
                                        <i class="fas fa-toggle-on"></i>
                                        Activer
                                    </a>
                                    <?php else: ?>
                                    <span class="btn-action btn-disabled">
                                        <i class="fas fa-check"></i>
                                        Actuelle
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <i class="fas fa-shield-alt"></i>
            <span>Système de Gestion Scolaire • © <?php echo date('Y'); ?></span>
        </div>
    </div>

    <script>
    // Confirmation avant activation
    document.addEventListener('DOMContentLoaded', function() {
        const activateButtons = document.querySelectorAll('.btn-activate');
        activateButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm(
                        'Cette action désactivera l\'année scolaire actuelle et activera la nouvelle. Continuer ?'
                    )) {
                    e.preventDefault();
                }
            });
        });

        // Animation pour les statistiques
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            const target = parseInt(stat.textContent);
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    stat.textContent = target;
                    clearInterval(timer);
                } else {
                    stat.textContent = Math.floor(current);
                }
            }, 30);
        });
    });
    </script>
</body>

</html>