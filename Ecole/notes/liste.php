<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

// Récupérer toutes les notes avec info élève, matière, trimestre et année
$stmt = $pdo->query("
    SELECT n.id, e.nom, e.prenom, e.matricule, e.classe_id, m.nom_matiere, m.coefficient, 
           n.type_note, n.note, n.date_note, n.eleve_id, n.matiere_id,
           t.nom AS trimestre, t.ordre, a.libelle,
           c.nom_classe
    FROM notes n
    JOIN eleves e ON n.eleve_id = e.id
    JOIN matieres m ON n.matiere_id = m.id
    JOIN trimestres t ON n.trimestre_id = t.id
    JOIN annees_scolaires a ON n.annee_id = a.id
    JOIN classes c ON e.classe_id = c.id
    ORDER BY a.libelle DESC, t.ordre, e.nom, e.prenom, m.nom_matiere, n.date_note
");

$notes = $stmt->fetchAll();

// Calculer les statistiques
$total_notes = count($notes);
$moyenne_generale = 0;
$notes_par_classe = [];
$notes_par_matiere = [];

if ($total_notes > 0) {
    $somme_notes = 0;
    
    foreach ($notes as $note) {
        $somme_notes += $note['note'];
        
        // Statistiques par classe
        if (!isset($notes_par_classe[$note['nom_classe']])) {
            $notes_par_classe[$note['nom_classe']] = ['total' => 0, 'somme' => 0, 'count' => 0];
        }
        $notes_par_classe[$note['nom_classe']]['total']++;
        $notes_par_classe[$note['nom_classe']]['somme'] += $note['note'];
        $notes_par_classe[$note['nom_classe']]['count']++;
        
        // Statistiques par matière
        if (!isset($notes_par_matiere[$note['nom_matiere']])) {
            $notes_par_matiere[$note['nom_matiere']] = ['total' => 0, 'somme' => 0, 'count' => 0];
        }
        $notes_par_matiere[$note['nom_matiere']]['total']++;
        $notes_par_matiere[$note['nom_matiere']]['somme'] += $note['note'];
        $notes_par_matiere[$note['nom_matiere']]['count']++;
    }
    
    $moyenne_generale = $somme_notes / $total_notes;
}

// Récupérer les années et trimestres pour les filtres
$annees = $pdo->query("SELECT DISTINCT a.id, a.libelle FROM notes n JOIN annees_scolaires a ON n.annee_id = a.id ORDER BY a.libelle DESC")->fetchAll();
$trimestres = $pdo->query("SELECT DISTINCT t.id, t.nom, t.ordre FROM notes n JOIN trimestres t ON n.trimestre_id = t.id ORDER BY t.ordre")->fetchAll();
$classes = $pdo->query("SELECT DISTINCT c.id, c.nom_classe FROM notes n JOIN eleves e ON n.eleve_id = e.id JOIN classes c ON e.classe_id = c.id ORDER BY c.nom_classe")->fetchAll();
$matieres = $pdo->query("SELECT DISTINCT m.id, m.nom_matiere FROM notes n JOIN matieres m ON n.matiere_id = m.id ORDER BY m.nom_matiere")->fetchAll();

// Traitement des filtres
$filters = [];
if (isset($_GET['annee_id']) && $_GET['annee_id'] != '') {
    $filters['annee_id'] = (int)$_GET['annee_id'];
}
if (isset($_GET['trimestre_id']) && $_GET['trimestre_id'] != '') {
    $filters['trimestre_id'] = (int)$_GET['trimestre_id'];
}
if (isset($_GET['classe_id']) && $_GET['classe_id'] != '') {
    $filters['classe_id'] = (int)$_GET['classe_id'];
}
if (isset($_GET['matiere_id']) && $_GET['matiere_id'] != '') {
    $filters['matiere_id'] = (int)$_GET['matiere_id'];
}
if (isset($_GET['search']) && $_GET['search'] != '') {
    $filters['search'] = $_GET['search'];
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des notes | Gestion Scolaire</title>
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
        padding: 30px 20px;
    }

    .container {
        max-width: 1400px;
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

    .header-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 25px;
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
        gap: 15px;
    }

    .header p {
        font-size: 16px;
        opacity: 0.9;
        max-width: 600px;
    }

    .stats-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        padding: 30px 40px;
        background: linear-gradient(to right, #f8fafc, #f1f5f9);
        border-bottom: 2px solid #e2e8f0;
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
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    }

    .stat-icon.average {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .stat-icon.students {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    }

    .stat-icon.subjects {
        background: linear-gradient(135deg, #f59e0b, #d97706);
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

    .filters-bar {
        padding: 30px 40px;
        background: white;
        border-bottom: 2px solid #e2e8f0;
    }

    .filters-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr) auto;
        gap: 15px;
        align-items: end;
    }

    @media (max-width: 1200px) {
        .filters-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 768px) {
        .filters-grid {
            grid-template-columns: 1fr;
        }
    }

    .filter-group {
        margin-bottom: 0;
    }

    .filter-group label {
        display: block;
        margin-bottom: 8px;
        color: #475569;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-group label i {
        color: #0369a1;
        font-size: 14px;
    }

    select,
    input[type="text"] {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 14px;
        background: white;
        color: #1e293b;
        transition: all 0.3s ease;
    }

    select:focus,
    input[type="text"]:focus {
        outline: none;
        border-color: #0369a1;
        box-shadow: 0 0 0 3px rgba(3, 105, 161, 0.1);
    }

    .btn-filter {
        padding: 14px 28px;
        background: linear-gradient(135deg, #0369a1, #0c4a6e);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        white-space: nowrap;
    }

    .btn-filter:hover {
        background: linear-gradient(135deg, #0284c7, #0369a1);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(3, 105, 161, 0.3);
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
        margin-left: auto;
    }

    .btn-add:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    }

    .content {
        padding: 0 40px 40px;
    }

    .table-container {
        background: white;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        margin-top: 30px;
    }

    .grades-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
    }

    .grades-table thead {
        background: linear-gradient(135deg, #1e293b, #334155);
    }

    .grades-table th {
        padding: 22px 20px;
        text-align: left;
        color: white;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
    }

    .grades-table th:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 25%;
        height: 50%;
        width: 1px;
        background: rgba(255, 255, 255, 0.2);
    }

    .grades-table tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .grades-table tbody tr:hover {
        background: #f8fafc;
        transform: translateX(5px);
    }

    .grades-table td {
        padding: 20px;
        color: #475569;
        font-size: 14px;
        vertical-align: middle;
    }

    .student-cell {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 15px;
        flex-shrink: 0;
    }

    .student-info {
        flex: 1;
    }

    .student-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 15px;
    }

    .student-details {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
    }

    .subject-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .subject-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }

    .subject-info {
        flex: 1;
    }

    .subject-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 15px;
    }

    .subject-coeff {
        font-size: 12px;
        color: #64748b;
        margin-top: 3px;
    }

    .type-badge {
        display: inline-block;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .type-interro {
        background: #dbeafe;
        color: #1e40af;
    }

    .type-mini_dev {
        background: #fef3c7;
        color: #92400e;
    }

    .type-dev_hebdo {
        background: #fce7f3;
        color: #be185d;
    }

    .type-compo {
        background: #dcfce7;
        color: #166534;
    }

    .note-cell {
        text-align: center;
    }

    .note-value {
        font-size: 24px;
        font-weight: 700;
        background: linear-gradient(135deg, #10b981, #059669);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .note-grade {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-top: 5px;
    }

    .grade-excellent {
        background: #d1fae5;
        color: #065f46;
    }

    .grade-good {
        background: #fef3c7;
        color: #92400e;
    }

    .grade-average {
        background: #fde68a;
        color: #92400e;
    }

    .grade-poor {
        background: #fee2e2;
        color: #7f1d1d;
    }

    .date-cell {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: #475569;
    }

    .period-badge {
        display: inline-block;
        padding: 8px 16px;
        background: #f1f5f9;
        color: #475569;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #e2e8f0;
    }

    .year-badge {
        display: inline-block;
        padding: 8px 16px;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        color: #0c4a6e;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #bae6fd;
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
        background: #0369a1;
        color: white;
        border-color: #0369a1;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(3, 105, 161, 0.2);
    }

    .nav-links a:nth-child(2):hover {
        background: #10b981;
        border-color: #10b981;
    }

    .export-bar {
        padding: 20px 40px;
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
        display: flex;
        justify-content: flex-end;
        gap: 15px;
    }

    .btn-export {
        padding: 12px 24px;
        background: #f1f5f9;
        color: #475569;
        border: 2px solid #cbd5e1;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-export:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }

    @media (max-width: 1024px) {

        .header,
        .stats-bar,
        .filters-bar,
        .content,
        .nav-links {
            padding: 30px;
        }

        .header h1 {
            font-size: 28px;
        }

        .grades-table {
            display: block;
            overflow-x: auto;
        }
    }

    @media (max-width: 768px) {
        .nav-links {
            flex-direction: column;
        }

        .export-bar {
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
                    <i class="fas fa-chart-line"></i>
                </div>
                <h1><i class="fas fa-list-check"></i> Bulletin des Notes</h1>
                <p>Consultez et analysez l'ensemble des notes des élèves avec filtres avancés</p>
            </div>
        </div>

        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($total_notes); ?></h3>
                    <p>Notes enregistrées</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon average">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_notes > 0 ? number_format($moyenne_generale, 2) : '0.00'; ?>/20</h3>
                    <p>Moyenne générale</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon students">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count(array_unique(array_column($notes, 'eleve_id'))); ?></h3>
                    <p>Élèves notés</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon subjects">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count(array_unique(array_column($notes, 'matiere_id'))); ?></h3>
                    <p>Matières évaluées</p>
                </div>
            </div>
        </div>

        <div class="filters-bar">
            <form method="GET" action="" id="filterForm">
                <div class="filters-grid">
                    <div class="filter-group">
                        <label for="annee_id"><i class="fas fa-calendar-star"></i> Année</label>
                        <select id="annee_id" name="annee_id">
                            <option value="">Toutes les années</option>
                            <?php foreach ($annees as $a): ?>
                            <option value="<?= $a['id'] ?>"
                                <?php echo isset($_GET['annee_id']) && $_GET['annee_id'] == $a['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($a['libelle']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="trimestre_id"><i class="fas fa-calendar-check"></i> Trimestre</label>
                        <select id="trimestre_id" name="trimestre_id">
                            <option value="">Tous les trimestres</option>
                            <?php foreach ($trimestres as $t): ?>
                            <option value="<?= $t['id'] ?>"
                                <?php echo isset($_GET['trimestre_id']) && $_GET['trimestre_id'] == $t['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($t['nom'] . ' (T' . $t['ordre'] . ')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="classe_id"><i class="fas fa-school"></i> Classe</label>
                        <select id="classe_id" name="classe_id">
                            <option value="">Toutes les classes</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?php echo isset($_GET['classe_id']) && $_GET['classe_id'] == $c['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($c['nom_classe']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="matiere_id"><i class="fas fa-book-open"></i> Matière</label>
                        <select id="matiere_id" name="matiere_id">
                            <option value="">Toutes les matières</option>
                            <?php foreach ($matieres as $m): ?>
                            <option value="<?= $m['id'] ?>"
                                <?php echo isset($_GET['matiere_id']) && $_GET['matiere_id'] == $m['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($m['nom_matiere']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="search"><i class="fas fa-search"></i> Recherche</label>
                        <input type="text" id="search" name="search" placeholder="Nom élève ou matière..."
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>

                    <div>
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                    </div>
                </div>
            </form>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; padding-top: 25px; border-top: 2px solid #e2e8f0;">
                <div>
                    <h3 style="color: #475569; font-size: 16px; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-bar"></i>
                        <span id="filterCount"><?php echo count($notes); ?> notes affichées</span>
                    </h3>
                </div>
                <a href="ajouter.php" class="btn-add">
                    <i class="fas fa-plus-circle"></i> Nouvelle note
                </a>
            </div>
        </div>

        <div class="content">
            <div class="table-container">
                <?php if (empty($notes)): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Aucune note enregistrée</h3>
                    <p>Commencez par ajouter les premières notes pour les élèves</p>
                    <br>
                    <a href="ajouter.php" class="btn-add" style="display: inline-flex; width: auto;">
                        <i class="fas fa-plus-circle"></i> Ajouter la première note
                    </a>
                </div>
                <?php else: ?>
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th>Élève</th>
                            <th>Matière</th>
                            <th>Type</th>
                            <th>Note</th>
                            <th>Date</th>
                            <th>Période</th>
                            <th>Année</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notes as $n): 
                                // Déterminer l'appréciation de la note
                                $grade_class = '';
                                $grade_text = '';
                                if ($n['note'] >= 16) {
                                    $grade_class = 'grade-excellent';
                                    $grade_text = 'Excellent';
                                } elseif ($n['note'] >= 14) {
                                    $grade_class = 'grade-good';
                                    $grade_text = 'Très bien';
                                } elseif ($n['note'] >= 10) {
                                    $grade_class = 'grade-average';
                                    $grade_text = 'Moyen';
                                } else {
                                    $grade_class = 'grade-poor';
                                    $grade_text = 'À améliorer';
                                }
                                
                                // Icône pour le type de note
                                $type_icons = [
                                    'interro' => 'fas fa-bolt',
                                    'mini_dev' => 'fas fa-file-alt',
                                    'dev_hebdo' => 'fas fa-file-signature',
                                    'compo' => 'fas fa-file-contract'
                                ];
                                $type_icon = $type_icons[$n['type_note']] ?? 'fas fa-file';
                                
                                // Couleur du type
                                $type_class = 'type-' . $n['type_note'];
                            ?>
                        <tr>
                            <td>
                                <div class="student-cell">
                                    <div class="student-avatar">
                                        <?php echo strtoupper(substr($n['prenom'], 0, 1) . substr($n['nom'], 0, 1)); ?>
                                    </div>
                                    <div class="student-info">
                                        <div class="student-name">
                                            <?php echo htmlspecialchars($n['prenom'] . ' ' . $n['nom']); ?>
                                        </div>
                                        <div class="student-details">
                                            <?php echo htmlspecialchars($n['matricule']); ?> •
                                            <?php echo htmlspecialchars($n['nom_classe']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="subject-cell">
                                    <div class="subject-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="subject-info">
                                        <div class="subject-name">
                                            <?php echo htmlspecialchars($n['nom_matiere']); ?>
                                        </div>
                                        <div class="subject-coeff">
                                            Coefficient: <?php echo $n['coefficient']; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="type-badge <?php echo $type_class; ?>">
                                    <i class="<?php echo $type_icon; ?>"></i>
                                    <?php echo htmlspecialchars($n['type_note']); ?>
                                </span>
                            </td>
                            <td class="note-cell">
                                <div class="note-value">
                                    <?php echo number_format($n['note'], 2); ?>
                                </div>
                                <div class="note-grade <?php echo $grade_class; ?>">
                                    <?php echo $grade_text; ?>
                                </div>
                            </td>
                            <td class="date-cell">
                                <?php echo date('d/m/Y', strtotime($n['date_note'])); ?>
                            </td>
                            <td>
                                <span class="period-badge">
                                    <i class="fas fa-calendar-week"></i>
                                    <?php echo htmlspecialchars($n['trimestre']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="year-badge">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo htmlspecialchars($n['libelle']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="export-bar">
            <a href="#" class="btn-export">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            <a href="#" class="btn-export">
                <i class="fas fa-file-excel"></i> Excel
            </a>
            <a href="#" class="btn-export">
                <i class="fas fa-print"></i> Imprimer
            </a>
        </div>

        <div class="nav-links">
            <a href="ajouter.php">
                <i class="fas fa-plus-circle"></i> Ajouter une note
            </a>
            <a href="../eleves/liste.php">
                <i class="fas fa-users"></i> Gérer les élèves
            </a>
            <a href="../accueil.php">
                <i class="fas fa-home"></i> Tableau de bord
            </a>
        </div>
    </div>

    <script>
    // Mise à jour du compteur de notes filtrées
    function updateFilterCount() {
        const visibleRows = document.querySelectorAll('.grades-table tbody tr').length;
        document.getElementById('filterCount').textContent = visibleRows + ' notes affichées';
    }

    // Animation des lignes du tableau
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.grades-table tbody tr');
        rows.forEach((row, index) => {
            row.style.animationDelay = (index * 0.05) + 's';
            row.style.animation = 'fadeIn 0.5s ease-out forwards';
            row.style.opacity = '0';
        });

        updateFilterCount();
    });

    // Reset des filtres
    document.querySelector('.btn-filter[type="submit"]').addEventListener('click', function(e) {
        // Petit délai pour l'animation
        setTimeout(() => {
            updateFilterCount();
        }, 100);
    });

    // Reset du formulaire
    const resetBtn = document.createElement('button');
    resetBtn.type = 'button';
    resetBtn.innerHTML = '<i class="fas fa-redo"></i> Réinitialiser';
    resetBtn.className = 'btn-filter';
    resetBtn.style.background = 'linear-gradient(135deg, #64748b, #475569)';
    resetBtn.addEventListener('click', function() {
        document.getElementById('filterForm').reset();
        document.getElementById('filterForm').submit();
    });

    const filterGrid = document.querySelector('.filters-grid');
    const submitBtn = filterGrid.querySelector('button[type="submit"]');
    submitBtn.parentNode.appendChild(resetBtn);

    // Recherche en temps réel (optionnel)
    const searchInput = document.getElementById('search');
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('filterForm').submit();
        }
    });

    // Animation des statistiques
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
        card.style.animation = 'fadeIn 0.6s ease-out forwards';
        card.style.opacity = '0';
    });
    </script>
</body>

</html>