<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";
$message_type = "";

// Activation d'un trimestre
if (isset($_GET['activate'])) {
    $id = (int)$_GET['activate'];

    // Récupérer l'année de ce trimestre
    $stmt_year = $pdo->prepare("SELECT annee_id FROM trimestres WHERE id = ?");
    $stmt_year->execute([$id]);
    $annee_id = $stmt_year->fetchColumn();

    // Désactiver tous les trimestres de cette année
    $stmt = $pdo->prepare("UPDATE trimestres SET actif = 0 WHERE annee_id = ?");
    $stmt->execute([$annee_id]);

    // Activer le trimestre sélectionné
    $stmt = $pdo->prepare("UPDATE trimestres SET actif = 1 WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "✅ Trimestre activé avec succès !";
        $message_type = "success";
    } else {
        $message = "❌ Erreur lors de l'activation.";
        $message_type = "error";
    }
}

// Suppression d'un trimestre
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Vérifier s'il y a des notes associées
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE trimestre_id = ?");
    $stmt_check->execute([$id]);
    $has_notes = $stmt_check->fetchColumn();
    
    if ($has_notes > 0) {
        $message = "⚠️ Impossible de supprimer ce trimestre : des notes y sont associées.";
        $message_type = "warning";
    } else {
        $stmt = $pdo->prepare("DELETE FROM trimestres WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = "✅ Trimestre supprimé avec succès !";
            $message_type = "success";
        } else {
            $message = "❌ Erreur lors de la suppression.";
            $message_type = "error";
        }
    }
}

// Récupérer la liste des trimestres avec statistiques
$stmt = $pdo->query("
    SELECT t.id, t.nom, t.ordre, t.actif, a.libelle AS annee, a.id as annee_id,
           (SELECT COUNT(*) FROM notes n WHERE n.trimestre_id = t.id) as nb_notes
    FROM trimestres t
    JOIN annees_scolaires a ON t.annee_id = a.id
    ORDER BY a.libelle DESC, t.ordre ASC
");
$trimestres = $stmt->fetchAll();

// Compter par année
$stats_annee = [];
foreach ($trimestres as $t) {
    if (!isset($stats_annee[$t['annee']])) {
        $stats_annee[$t['annee']] = ['total' => 0, 'actif' => 0];
    }
    $stats_annee[$t['annee']]['total']++;
    if ($t['actif']) {
        $stats_annee[$t['annee']]['actif']++;
    }
}

// Récupérer l'année en cours (avec trimestre actif)
$current_year = null;
foreach ($trimestres as $t) {
    if ($t['actif']) {
        $current_year = $t['annee'];
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des trimestres | Gestion Scolaire</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        min-height: 100vh;
        padding: 30px 20px;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 24px;
        box-shadow: 0 25px 70px rgba(245, 158, 11, 0.15);
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
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
        text-align: center;
    }

    .header-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        font-size: 36px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .header h1 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }

    .header p {
        font-size: 16px;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .current-period {
        margin-top: 25px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 16px;
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.2);
        display: inline-block;
    }

    .current-period strong {
        display: block;
        font-size: 18px;
        margin-bottom: 8px;
    }

    .stats-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        padding: 30px 40px;
        background: linear-gradient(to right, #fef3c7, #fde68a);
        border-bottom: 2px solid #f59e0b;
    }

    @media (max-width: 1024px) {
        .stats-bar {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .stats-bar {
            grid-template-columns: 1fr;
        }
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
        color: white;
    }

    .stat-icon.total {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .stat-icon.active {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .stat-icon.years {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    }

    .stat-icon.notes {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    }

    .stat-content h3 {
        font-size: 28px;
        color: #1e293b;
        margin-bottom: 5px;
    }

    .stat-content p {
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
    }

    .message {
        margin: 20px 40px;
        padding: 20px;
        border-radius: 16px;
        text-align: center;
        font-size: 16px;
        font-weight: 500;
        animation: messageIn 0.4s ease-out;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    @keyframes messageIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.success {
        background: linear-gradient(to right, #d1fae5, #a7f3d0);
        color: #065f46;
        border-left: 5px solid #34d399;
    }

    .message.error {
        background: linear-gradient(to right, #fee2e2, #fecaca);
        color: #7f1d1d;
        border-left: 5px solid #f87171;
    }

    .message.warning {
        background: linear-gradient(to right, #fef3c7, #fde68a);
        color: #92400e;
        border-left: 5px solid #f59e0b;
    }

    .actions-bar {
        padding: 30px 40px;
        background: white;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .btn-add {
        padding: 16px 32px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
    }

    .btn-add:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    }

    .year-filter {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .year-btn {
        padding: 12px 24px;
        background: #f8fafc;
        color: #475569;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .year-btn:hover {
        background: #f59e0b;
        color: white;
        border-color: #f59e0b;
    }

    .year-btn.active {
        background: #f59e0b;
        color: white;
        border-color: #f59e0b;
        font-weight: 600;
    }

    .content {
        padding: 0 40px 40px;
    }

    .trimesters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    @media (max-width: 768px) {
        .trimesters-grid {
            grid-template-columns: 1fr;
        }
    }

    .trimester-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 3px solid transparent;
    }

    .trimester-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }

    .trimester-card.active {
        border-color: #10b981;
        box-shadow: 0 15px 35px rgba(16, 185, 129, 0.2);
    }

    .trimester-header {
        padding: 25px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        position: relative;
    }

    .trimester-header.active {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .trimester-order {
        position: absolute;
        top: -15px;
        left: 25px;
        width: 60px;
        height: 60px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: 800;
        color: #f59e0b;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .trimester-header.active .trimester-order {
        color: #10b981;
    }

    .trimester-title {
        font-size: 22px;
        font-weight: 700;
        margin-left: 70px;
        margin-bottom: 10px;
    }

    .trimester-year {
        font-size: 14px;
        opacity: 0.9;
        margin-left: 70px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .trimester-body {
        padding: 25px;
    }

    .trimester-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-item {
        text-align: center;
        padding: 15px;
        background: #f8fafc;
        border-radius: 12px;
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
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .trimester-status {
        padding: 12px;
        border-radius: 10px;
        text-align: center;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .status-active {
        background: #d1fae5;
        color: #065f46;
        border: 2px solid #a7f3d0;
    }

    .status-inactive {
        background: #f1f5f9;
        color: #64748b;
        border: 2px solid #e2e8f0;
    }

    .trimester-actions {
        display: flex;
        gap: 10px;
    }

    .btn-action {
        flex: 1;
        padding: 14px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-activate {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
    }

    .btn-activate:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
    }

    .btn-delete {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .btn-delete:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }

    .btn-current {
        background: #10b981;
        color: white;
        cursor: default;
    }

    .btn-edit {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }

    .btn-edit:hover {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(139, 92, 246, 0.3);
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        color: #64748b;
        grid-column: 1 / -1;
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

    .empty-state p {
        max-width: 500px;
        margin: 0 auto 30px;
        line-height: 1.6;
    }

    .nav-links {
        display: flex;
        justify-content: center;
        gap: 25px;
        padding: 35px 40px;
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
        flex-wrap: wrap;
    }

    .nav-links a {
        padding: 18px 32px;
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
        background: #f59e0b;
        color: white;
        border-color: #f59e0b;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(245, 158, 11, 0.2);
    }

    .nav-links a:nth-child(2):hover {
        background: #10b981;
        border-color: #10b981;
    }

    @media (max-width: 1024px) {

        .header,
        .stats-bar,
        .actions-bar,
        .content,
        .nav-links {
            padding: 30px;
        }

        .header h1 {
            font-size: 28px;
        }
    }

    @media (max-width: 768px) {
        .nav-links {
            flex-direction: column;
        }

        .actions-bar {
            flex-direction: column;
            text-align: center;
        }

        .year-filter {
            justify-content: center;
        }
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h1><i class="fas fa-calendar-week"></i> Gestion des Trimestres</h1>
                <p>Organisez et gérez les périodes d'évaluation de l'année scolaire</p>

                <?php if ($current_year): ?>
                <div class="current-period">
                    <strong><i class="fas fa-calendar-check"></i> Trimestre en cours</strong>
                    Année : <?php echo htmlspecialchars($current_year); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($trimestres); ?></h3>
                    <p>Trimestres créés</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count(array_filter($trimestres, fn($t) => $t['actif'])); ?></h3>
                    <p>Trimestre(s) actif(s)</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon years">
                    <i class="fas fa-school"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($stats_annee); ?></h3>
                    <p>Années scolaires</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon notes">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo array_sum(array_column($trimestres, 'nb_notes')); ?></h3>
                    <p>Notes associées</p>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <i class="fas fa-<?php 
                    echo $message_type === 'success' ? 'check-circle' : 
                          ($message_type === 'error' ? 'exclamation-circle' : 'exclamation-triangle');
                ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="actions-bar">
            <div>
                <h3 style="color: #475569; font-size: 18px; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-filter"></i> Filtre par année
                </h3>
                <p style="color: #64748b; font-size: 14px; margin-top: 5px;">
                    Cliquez sur une année pour filtrer les trimestres
                </p>
            </div>

            <div class="year-filter" id="yearFilter">
                <button class="year-btn active" data-year="all">
                    <i class="fas fa-layer-group"></i> Toutes
                </button>
                <?php foreach ($stats_annee as $annee => $stat): ?>
                <button class="year-btn" data-year="<?php echo htmlspecialchars($annee); ?>">
                    <?php echo htmlspecialchars($annee); ?>
                    <span
                        style="margin-left: 8px; padding: 2px 8px; background: #f59e0b; color: white; border-radius: 10px; font-size: 12px;">
                        <?php echo $stat['total']; ?>
                    </span>
                </button>
                <?php endforeach; ?>
            </div>

            <a href="ajouter.php" class="btn-add">
                <i class="fas fa-plus-circle"></i> Nouveau trimestre
            </a>
        </div>

        <div class="content">
            <div class="trimesters-grid" id="trimestersGrid">
                <?php if (empty($trimestres)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Aucun trimestre créé</h3>
                    <p>Commencez par créer votre premier trimestre pour organiser l'année scolaire</p>
                    <br>
                    <a href="ajouter.php" class="btn-add" style="display: inline-flex; width: auto;">
                        <i class="fas fa-plus-circle"></i> Créer le premier trimestre
                    </a>
                </div>
                <?php else: ?>
                <?php foreach ($trimestres as $t): ?>
                <div class="trimester-card <?php echo $t['actif'] ? 'active' : ''; ?>"
                    data-year="<?php echo htmlspecialchars($t['annee']); ?>">
                    <div class="trimester-header <?php echo $t['actif'] ? 'active' : ''; ?>">
                        <div class="trimester-order">
                            <?php echo $t['ordre']; ?>
                        </div>
                        <div class="trimester-title">
                            <?php echo htmlspecialchars($t['nom']); ?>
                        </div>
                        <div class="trimester-year">
                            <i class="fas fa-calendar-star"></i>
                            <?php echo htmlspecialchars($t['annee']); ?>
                        </div>
                    </div>

                    <div class="trimester-body">
                        <div class="trimester-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $t['id']; ?></div>
                                <div class="stat-label">ID</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $t['nb_notes']; ?></div>
                                <div class="stat-label">Notes</div>
                            </div>
                        </div>

                        <div class="trimester-status <?php echo $t['actif'] ? 'status-active' : 'status-inactive'; ?>">
                            <i class="fas fa-<?php echo $t['actif'] ? 'check-circle' : 'pause-circle'; ?>"></i>
                            <?php echo $t['actif'] ? 'Trimestre en cours' : 'Trimestre inactif'; ?>
                        </div>

                        <div class="trimester-actions">
                            <?php if ($t['actif']): ?>
                            <span class="btn-action btn-current">
                                <i class="fas fa-check"></i> Actuel
                            </span>
                            <?php else: ?>
                            <a href="?activate=<?php echo $t['id']; ?>" class="btn-action btn-activate"
                                onclick="return confirm('Activer le trimestre <?php echo addslashes($t['nom']); ?> ?\n\nTous les autres trimestres de l\'année <?php echo addslashes($t['annee']); ?> seront désactivés.')">
                                <i class="fas fa-play"></i> Activer
                            </a>
                            <?php endif; ?>

                            <?php if ($t['nb_notes'] == 0): ?>
                            <a href="?delete=<?php echo $t['id']; ?>" class="btn-action btn-delete"
                                onclick="return confirmDelete('<?php echo addslashes($t['nom']); ?>')">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php else: ?>
                            <button class="btn-action btn-delete" disabled
                                title="Impossible de supprimer : des notes sont associées">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="nav-links">
            <a href="ajouter.php">
                <i class="fas fa-plus-circle"></i> Ajouter un trimestre
            </a>
            <a href="../notes/liste.php">
                <i class="fas fa-star"></i> Gérer les notes
            </a>
            <a href="../accueil.php">
                <i class="fas fa-home"></i> Tableau de bord
            </a>
        </div>
    </div>

    <script>
    // Filtrage par année
    const yearBtns = document.querySelectorAll('.year-btn');
    const trimesterCards = document.querySelectorAll('.trimester-card');

    yearBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const selectedYear = this.dataset.year;

            // Mettre à jour les boutons actifs
            yearBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Filtrer les cartes
            let visibleCount = 0;
            trimesterCards.forEach(card => {
                if (selectedYear === 'all' || card.dataset.year === selectedYear) {
                    card.style.display = 'block';
                    visibleCount++;
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300);
                }
            });

            // Mettre à jour le compteur
            const countElement = document.querySelector('#yearFilter .year-btn.active span') ||
                document.querySelector('#yearFilter .year-btn.active');
            if (countElement) {
                const originalText = countElement.textContent.replace(/\d+$/, '').trim();
                countElement.textContent = selectedYear === 'all' ? 'Toutes' : originalText + ' ' +
                    visibleCount;
            }
        });
    });

    // Confirmation de suppression
    function confirmDelete(trimesterName) {
        return confirm(
            `⚠️ Êtes-vous sûr de vouloir supprimer le trimestre "${trimesterName}" ?\n\nCette action est irréversible.`
            );
    }

    // Animation des cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.trimester-card');
        cards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
            card.style.animation = 'fadeIn 0.6s ease-out forwards';
            card.style.opacity = '0';
        });

        // Animation des statistiques
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
            card.style.animation = 'fadeIn 0.5s ease-out forwards';
            card.style.opacity = '0';
        });
    });

    // Auto-hide message
    const messageDiv = document.querySelector('.message');
    if (messageDiv) {
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 5000);
    }

    // Hover effects améliorés
    trimesterCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            const header = this.querySelector('.trimester-header');
            if (header) {
                header.style.transform = 'scale(1.02)';
                header.style.transition = 'transform 0.3s ease';
            }
        });

        card.addEventListener('mouseleave', function() {
            const header = this.querySelector('.trimester-header');
            if (header) {
                header.style.transform = 'scale(1)';
            }
        });
    });
    </script>
</body>

</html>