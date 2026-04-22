<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

/* =====================
   DONNÉES DE BASE
===================== */
$classes = $pdo->query("SELECT * FROM classes ORDER BY nom_classe")->fetchAll();
$trimestres = $pdo->query("SELECT * FROM trimestres ORDER BY ordre")->fetchAll();
$annees = $pdo->query("SELECT * FROM annees_scolaires ORDER BY libelle DESC")->fetchAll();

/* =====================
   TRAITEMENT FORMULAIRE
===================== */
if (isset($_POST['generer'])) {
    $classe_id = (int)$_POST['classe_id'];
    $trimestre_id = (int)$_POST['trimestre_id'];
    $annee_id = (int)$_POST['annee_id'];

    // Récupérer la classe
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $stmt->execute([$classe_id]);
    $classe = $stmt->fetch();

    // Récupérer les élèves de la classe
    $stmt = $pdo->prepare("SELECT * FROM eleves WHERE classe_id = ? ORDER BY nom, prenom");
    $stmt->execute([$classe_id]);
    $eleves = $stmt->fetchAll();

    // Récupérer les matières
    $matieres = $pdo->query("SELECT * FROM matieres ORDER BY nom_matiere")->fetchAll();

    // Récupérer les notes par élève
    $bulletins = [];
    $moyennes_eleves = [];

    foreach ($eleves as $eleve) {
        $notes_eleve = [];
        $total_moyennes = 0;
        $count_moyennes = 0;

        foreach ($matieres as $matiere) {
            $stmt = $pdo->prepare("
    SELECT type_note, note 
    FROM notes 
    WHERE eleve_id = ? AND matiere_id = ? AND trimestre_id = ? AND annee_id = ?
");
            $stmt->execute([$eleve['id'], $matiere['id'], $trimestre_id, $annee_id]);
            $notes_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organiser les notes par type
            $notes = [];
            foreach ($notes_raw as $n) {
                $notes[$n['type_note']][] = $n['note'];
            }


            // Calcul de la moyenne de la matière
            $interro = isset($notes['interro']) ? array_sum($notes['interro']) / count($notes['interro']) : null;
            $mini_dev = isset($notes['mini_dev']) ? array_sum($notes['mini_dev']) / count($notes['mini_dev']) : null;
            $dev_hebdo = isset($notes['dev_hebdo']) ? array_sum($notes['dev_hebdo']) / count($notes['dev_hebdo']) : null;
            $compo = isset($notes['compo']) ? array_sum($notes['compo']) / count($notes['compo']) : null;


            $dev_notes = array_filter([$mini_dev, $dev_hebdo], fn($v) => $v !== null);
            $moy_dev = count($dev_notes) ? array_sum($dev_notes) / count($dev_notes) : null;

            $int_notes = array_filter([$interro, $moy_dev], fn($v) => $v !== null);
            $moy_int = count($int_notes) ? array_sum($int_notes) / count($int_notes) : null;

            $final_notes = array_filter([$moy_int, $compo], fn($v) => $v !== null);
            $moy_matiere = count($final_notes) ? array_sum($final_notes) / count($final_notes) : null;

            if ($moy_matiere !== null) {
                $total_moyennes += $moy_matiere;
                $count_moyennes++;
            }

            $notes_eleve[] = [
                'matiere' => $matiere['nom_matiere'],
                'interro' => $interro,
                'mini_dev' => $mini_dev,
                'dev_hebdo' => $dev_hebdo,
                'compo' => $compo,
                'moyenne' => $moy_matiere
            ];
        }

        $moyenne_generale = $count_moyennes > 0 ? $total_moyennes / $count_moyennes : null;

        $bulletins[] = [
            'eleve' => $eleve,
            'notes' => $notes_eleve,
            'moyenne_generale' => $moyenne_generale,
            'count_moyennes' => $count_moyennes
        ];

        if ($moyenne_generale !== null) {
            $moyennes_eleves[$eleve['id']] = $moyenne_generale;
        }
    }

    // Calcul du classement
    arsort($moyennes_eleves);
    $classement = array_keys($moyennes_eleves);

    foreach ($bulletins as &$bulletin) {
        $position = array_search($bulletin['eleve']['id'], $classement);
        $bulletin['rang'] = $position !== false ? $position + 1 : null;
    }

    // Récupérer les infos du trimestre et année
    $stmt = $pdo->prepare("SELECT * FROM trimestres WHERE id = ?");
    $stmt->execute([$trimestre_id]);
    $trimestre = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM annees_scolaires WHERE id = ?");
    $stmt->execute([$annee_id]);
    $annee = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impression des bulletins par classe | Gestion Scolaire</title>
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
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
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

    .form-section {
        background: #f8fafc;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 40px;
        border: 2px solid #e2e8f0;
    }

    .section-title {
        font-size: 22px;
        color: #1e293b;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .section-title i {
        color: #8b5cf6;
        font-size: 24px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        color: #475569;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-group label i {
        color: #8b5cf6;
        font-size: 14px;
    }

    select {
        width: 100%;
        padding: 16px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 500;
        color: #1e293b;
        background: white;
        transition: all 0.3s ease;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 18px center;
        background-size: 20px;
    }

    select:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .btn {
        padding: 18px 40px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 12px;
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(139, 92, 246, 0.3);
    }

    .btn-print-all {
        background: linear-gradient(135deg, #10b981, #059669);
    }

    .btn-print-all:hover {
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    }

    .btn-generate {
        width: 100%;
        text-align: center;
        justify-content: center;
        margin-top: 20px;
    }

    .results-section {
        margin-top: 40px;
    }

    .class-info {
        background: linear-gradient(135deg, #f5f3ff, #ede9fe);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }

    .class-details {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .class-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        border-radius: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
    }

    .class-text h3 {
        font-size: 24px;
        color: #1e293b;
        margin-bottom: 5px;
    }

    .class-text p {
        color: #64748b;
        font-size: 14px;
    }

    .stats-cards {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .stat-card {
        background: white;
        padding: 15px 25px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #8b5cf6;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .bulletin-list {
        margin-top: 30px;
    }

    .bulletin-item {
        background: white;
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 20px;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 20px;
    }

    .bulletin-item:hover {
        border-color: #c4b5fd;
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .student-info {
        display: flex;
        align-items: center;
        gap: 20px;
        flex: 1;
    }

    .student-avatar {
        width: 60px;
        height: 60px;
        border-radius: 15px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 600;
    }

    .student-details h4 {
        font-size: 18px;
        color: #1e293b;
        margin-bottom: 5px;
    }

    .student-details p {
        color: #64748b;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .bulletin-stats {
        display: flex;
        gap: 25px;
        align-items: center;
    }

    .bulletin-stat {
        text-align: center;
    }

    .bulletin-value {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 5px;
    }

    .bulletin-value.rang {
        color: #8b5cf6;
    }

    .bulletin-value.moyenne {
        color: #10b981;
    }

    .bulletin-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 500;
    }

    .bulletin-actions {
        display: flex;
        gap: 15px;
    }

    .btn-action {
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-view {
        background: #e0f2fe;
        color: #0369a1;
        border: 2px solid #bae6fd;
    }

    .btn-view:hover {
        background: #bae6fd;
        transform: translateY(-2px);
    }

    .btn-print {
        background: #dcfce7;
        color: #059669;
        border: 2px solid #a7f3d0;
    }

    .btn-print:hover {
        background: #a7f3d0;
        transform: translateY(-2px);
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
        background: #8b5cf6;
        color: white;
        border-color: #8b5cf6;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(139, 92, 246, 0.2);
    }

    .print-all-section {
        background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        border-radius: 16px;
        padding: 30px;
        margin-top: 40px;
        text-align: center;
        border: 2px solid #86efac;
    }

    .print-all-header {
        font-size: 24px;
        color: #065f46;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
    }

    .print-all-desc {
        color: #047857;
        margin-bottom: 25px;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    .print-options {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .print-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: white;
        border-radius: 10px;
        border: 2px solid #a7f3d0;
    }

    .print-option input[type="checkbox"] {
        width: 18px;
        height: 18px;
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

    .loading {
        text-align: center;
        padding: 40px;
        color: #64748b;
    }

    .loading i {
        font-size: 48px;
        margin-bottom: 20px;
        color: #8b5cf6;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    @media (max-width: 768px) {
        .bulletin-item {
            flex-direction: column;
            text-align: center;
        }

        .student-info {
            flex-direction: column;
            text-align: center;
        }

        .bulletin-stats {
            justify-content: center;
        }

        .bulletin-actions {
            justify-content: center;
        }

        .header {
            padding: 30px 20px;
        }

        .content {
            padding: 20px;
        }

        .class-info {
            flex-direction: column;
            text-align: center;
        }

        .class-details {
            flex-direction: column;
        }
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <a href="../accueil.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Retour au tableau de bord
                </a>
                <h1><i class="fas fa-print"></i> Impression des bulletins</h1>
                <p>Générez et imprimez les bulletins pour toute une classe en un clic</p>
            </div>
        </div>

        <div class="content">
            <div class="page-title">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Sélectionnez la classe et la période</span>
            </div>

            <div class="form-section">
                <h2 class="section-title">
                    <i class="fas fa-filter"></i>
                    Critères de sélection
                </h2>

                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="classe_id"><i class="fas fa-school"></i> Classe</label>
                            <select id="classe_id" name="classe_id" required>
                                <option value="">-- Sélectionner une classe --</option>
                                <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    <?php echo isset($classe_id) && $classe_id == $c['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($c['nom_classe']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="trimestre_id"><i class="fas fa-calendar-alt"></i> Trimestre</label>
                            <select id="trimestre_id" name="trimestre_id" required>
                                <option value="">-- Sélectionner un trimestre --</option>
                                <?php foreach ($trimestres as $t): ?>
                                <option value="<?= $t['id'] ?>"
                                    <?php echo isset($trimestre_id) && $trimestre_id == $t['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($t['nom']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="annee_id"><i class="fas fa-calendar-star"></i> Année scolaire</label>
                            <select id="annee_id" name="annee_id" required>
                                <option value="">-- Sélectionner une année --</option>
                                <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>"
                                    <?php echo isset($annee_id) && $annee_id == $a['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($a['libelle']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" name="generer" class="btn btn-generate">
                        <i class="fas fa-search"></i> Voir les bulletins
                    </button>
                </form>
            </div>

            <?php if (isset($bulletins) && isset($classe)): ?>
            <div class="results-section">
                <div class="class-info">
                    <div class="class-details">
                        <div class="class-icon">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <div class="class-text">
                            <h3>Classe : <?= htmlspecialchars($classe['nom_classe']) ?></h3>
                            <p>
                                <?= count($eleves) ?> élèves •
                                Trimestre : <?= htmlspecialchars($trimestre['nom']) ?> •
                                Année : <?= htmlspecialchars($annee['libelle']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="stats-cards">
                        <div class="stat-card">
                            <div class="stat-value"><?= count($eleves) ?></div>
                            <div class="stat-label">Élèves</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">
                                <?php
                                    $avec_notes = 0;
                                    foreach ($bulletins as $b) {
                                        if ($b['moyenne_generale'] !== null) $avec_notes++;
                                    }
                                    echo $avec_notes;
                                    ?>
                            </div>
                            <div class="stat-label">Avec notes</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">
                                <?php
                                    $moyennes_classe = array_filter(array_column($bulletins, 'moyenne_generale'));
                                    $moyenne_classe = !empty($moyennes_classe) ?
                                        number_format(array_sum($moyennes_classe) / count($moyennes_classe), 2) : '0.00';
                                    echo $moyenne_classe;
                                    ?>
                            </div>
                            <div class="stat-label">Moy. classe</div>
                        </div>
                    </div>
                </div>

                <div class="bulletin-list">
                    <h3 class="section-title">
                        <i class="fas fa-list-check"></i>
                        Liste des bulletins (<?= count($bulletins) ?>)
                    </h3>

                    <?php if (empty($bulletins)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>Aucun élève dans cette classe</h3>
                        <p>Ajoutez des élèves à cette classe pour générer des bulletins</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($bulletins as $index => $bulletin):
                                $eleve = $bulletin['eleve'];
                                $moyenne = $bulletin['moyenne_generale'];
                                $rang = $bulletin['rang'];
                                $initials = strtoupper(substr($eleve['prenom'], 0, 1) . substr($eleve['nom'], 0, 1));
                            ?>
                    <div class="bulletin-item">
                        <div class="student-info">
                            <div class="student-avatar">
                                <?= $initials ?>
                            </div>
                            <div class="student-details">
                                <h4><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h4>
                                <p>
                                    <span><i class="fas fa-id-card"></i>
                                        <?= htmlspecialchars($eleve['matricule']) ?></span>
                                    <span><i class="fas fa-birthday-cake"></i>
                                        <?= date('d/m/Y', strtotime($eleve['date_naissance'])) ?></span>
                                    <span><i class="fas fa-<?= $eleve['sexe'] == 'M' ? 'mars' : 'venus' ?>"></i>
                                        <?= $eleve['sexe'] == 'M' ? 'Garçon' : 'Fille' ?></span>
                                </p>
                            </div>
                        </div>

                        <div class="bulletin-stats">
                            <div class="bulletin-stat">
                                <div class="bulletin-value rang"><?= $rang ?? '—' ?></div>
                                <div class="bulletin-label">Rang</div>
                            </div>
                            <div class="bulletin-stat">
                                <div class="bulletin-value moyenne">
                                    <?= $moyenne !== null ? number_format($moyenne, 2) : '—' ?></div>
                                <div class="bulletin-label">Moyenne</div>
                            </div>
                            <div class="bulletin-stat">
                                <div class="bulletin-value"><?= $bulletin['count_moyennes'] ?></div>
                                <div class="bulletin-label">Matières</div>
                            </div>
                        </div>

                        <div class="bulletin-actions">
                            <a href="imprimer.php?eleve=<?= $eleve['id'] ?>&trimestre=<?= $trimestre_id ?>&annee=<?= $annee_id ?>"
                                target="_blank" class="btn-action btn-print">
                                <i class="fas fa-print"></i> Imprimer
                            </a>
                            <a href="../notes/bulletins.php?eleve_id=<?= $eleve['id'] ?>&trimestre_id=<?= $trimestre_id ?>&annee_id=<?= $annee_id ?>"
                                class="btn-action btn-view">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Section impression en masse -->
                <div class="print-all-section">
                    <h3 class="print-all-header">
                        <i class="fas fa-print"></i>
                        Impression en masse
                    </h3>
                    <p class="print-all-desc">
                        Imprimez tous les bulletins de cette classe en une seule opération.
                        Les bulletins seront générés dans un seul fichier PDF.
                    </p>

                    <form action="imprimer_classe.php" method="GET" target="_blank" id="printAllForm">
                        <input type="hidden" name="classe_id" value="<?= $classe_id ?>">
                        <input type="hidden" name="trimestre_id" value="<?= $trimestre_id ?>">
                        <input type="hidden" name="annee_id" value="<?= $annee_id ?>">

                        <div class="print-options">
                            <div class="print-option">
                                <input type="checkbox" id="with_ranking" name="with_ranking" checked>
                                <label for="with_ranking">Inclure le classement</label>
                            </div>
                            <div class="print-option">
                                <input type="checkbox" id="with_details" name="with_details" checked>
                                <label for="with_details">Inclure les détails</label>
                            </div>
                            <div class="print-option">
                                <input type="checkbox" id="with_appreciation" name="with_appreciation">
                                <label for="with_appreciation">Inclure l'appréciation</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-print-all">
                            <i class="fas fa-print"></i> Imprimer toute la classe
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="nav-links">
            <a href="../notes/bulletins.php">
                <i class="fas fa-file-alt"></i> Bulletin individuel
            </a>
            <a href="../accueil.php">
                <i class="fas fa-home"></i> Tableau de bord
            </a>
            <a href="../eleves/liste.php">
                <i class="fas fa-users"></i> Gérer les élèves
            </a>
        </div>
    </div>

    <script>
    // Animation des éléments
    document.addEventListener('DOMContentLoaded', function() {
        const items = document.querySelectorAll('.bulletin-item');
        items.forEach((item, index) => {
            item.style.animationDelay = (index * 0.1) + 's';
            item.style.animation = 'fadeIn 0.5s ease-out forwards';
            item.style.opacity = '0';
        });

        // Confirmation avant impression en masse
        const printAllForm = document.getElementById('printAllForm');
        if (printAllForm) {
            printAllForm.addEventListener('submit', function(e) {
                const count = <?= isset($bulletins) ? count($bulletins) : 0 ?>;
                if (count > 10) {
                    if (!confirm(
                            `Êtes-vous sûr de vouloir imprimer ${count} bulletins ? Cela peut prendre quelques instants.`
                        )) {
                        e.preventDefault();
                    }
                }
            });
        }
    });
    </script>
</body>

</html>