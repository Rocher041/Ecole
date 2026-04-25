<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

// Vérifier les paramètres
if (!isset($_GET['classe_id']) || !isset($_GET['matiere_id'])) {
    header("Location: classes.php");
    exit;
}

$classe_id = (int)$_GET['classe_id'];
$matiere_id = (int)$_GET['matiere_id'];

// Récupérer les informations de la classe et de la matière
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$classe_id]);
$classe = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT 
        m.*,
        cm.coefficient AS coef_classe
    FROM matieres m
    JOIN classe_matiere cm ON cm.matiere_id = m.id
    WHERE m.id = ? AND cm.classe_id = ?
");
$stmt->execute([$matiere_id, $classe_id]);
$matiere = $stmt->fetch();

if (!$classe || !$matiere) {
    header("Location: classes.php");
    exit;
}

// Récupérer les élèves de la classe
$stmt = $pdo->prepare("SELECT * FROM eleves WHERE classe_id = ? ORDER BY nom, prenom");
$stmt->execute([$classe_id]);
$eleves = $stmt->fetchAll();

// Récupérer les notes existantes pour cette matière et cette classe
$stmt = $pdo->prepare("
    SELECT n.*, e.nom, e.prenom, e.matricule,
           t.nom as trimestre_nom, a.libelle as annee_libelle
    FROM notes n
    JOIN eleves e ON n.eleve_id = e.id
    JOIN trimestres t ON n.trimestre_id = t.id
    JOIN annees_scolaires a ON n.annee_id = a.id
    WHERE n.matiere_id = ? AND e.classe_id = ?
    ORDER BY e.nom, e.prenom, t.ordre, n.type_note, n.date_note
");
$stmt->execute([$matiere_id, $classe_id]);
$notes = $stmt->fetchAll();

// Organiser les notes par élève et par type
$notes_par_eleve = [];
foreach ($notes as $note) {
    $notes_par_eleve[$note['eleve_id']][$note['type_note']][] = $note;
}

// Récupérer les trimestres actifs et années
$trimestres = $pdo->query("SELECT * FROM trimestres WHERE actif = 1 ORDER BY ordre")->fetchAll();
$annees = $pdo->query("SELECT * FROM annees_scolaires WHERE active = 1")->fetchAll();

// Traitement de l'enregistrement des notes en masse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_notes'])) {
    $trimestre_id = (int)$_POST['trimestre_id'];
    $annee_id = (int)$_POST['annee_id'];

    // Parcourir tous les élèves
    foreach ($eleves as $eleve) {
        $eleve_id = $eleve['id'];

        // Interrogations (5 colonnes) - type_note: 'interro'
        for ($i = 1; $i <= 5; $i++) {
            $input_name = 'interro_' . $eleve_id . '_' . $i;
            if (isset($_POST[$input_name]) && $_POST[$input_name] !== '') {
                $note_val = (float)$_POST[$input_name];
                if ($note_val >= 0 && $note_val <= 20) {
                    // Vérifier si la note existe déjà
                    $stmt = $pdo->prepare("
    SELECT id FROM notes 
    WHERE eleve_id = ?
    AND matiere_id = ?
    AND trimestre_id = ?
    AND annee_id = ?
    AND type_note = 'interro'
    AND numero_interro = ?
");
                    $stmt->execute([
                        $eleve_id,
                        $matiere_id,
                        $trimestre_id,
                        $annee_id,
                        $i
                    ]);
                    $existing = $stmt->fetch();


                    if ($existing) {
                        // Mettre à jour
                        $stmt = $pdo->prepare("UPDATE notes SET note = ?, date_note = NOW() WHERE id = ?");
                        $stmt->execute([$note_val, $existing['id']]);
                    } else {
                        // Insérer avec le bon type_note
                       $stmt = $pdo->prepare("
    INSERT INTO notes 
    (eleve_id, matiere_id, type_note, numero_interro, note, date_note, trimestre_id, annee_id)
    VALUES (?, ?, 'interro', ?, ?, NOW(), ?, ?)
");
$stmt->execute([
    $eleve_id,
    $matiere_id,
    $i,
    $note_val,
    $trimestre_id,
    $annee_id
]);

                    }
                }
            }
        }

        // Devoir (dev_hebdo) - type_note: 'dev_hebdo'
        if (isset($_POST['devoir_' . $eleve_id]) && $_POST['devoir_' . $eleve_id] !== '') {
            $note_val = (float)$_POST['devoir_' . $eleve_id];
            if ($note_val >= 0 && $note_val <= 20) {
                $stmt = $pdo->prepare("SELECT id FROM notes WHERE eleve_id = ? AND matiere_id = ? AND trimestre_id = ? AND annee_id = ? AND type_note = 'dev_hebdo'");
                $stmt->execute([$eleve_id, $matiere_id, $trimestre_id, $annee_id]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE notes SET note = ?, date_note = NOW() WHERE id = ?");
                    $stmt->execute([$note_val, $existing['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notes (eleve_id, matiere_id, type_note, note, date_note, trimestre_id, annee_id) VALUES (?, ?, 'dev_hebdo', ?, NOW(), ?, ?)");
                    $stmt->execute([$eleve_id, $matiere_id, $note_val, $trimestre_id, $annee_id]);
                }
            }
        }

        // Mini-devoir (mini_dev) - type_note: 'mini_dev'
        if (isset($_POST['mini_devoir_' . $eleve_id]) && $_POST['mini_devoir_' . $eleve_id] !== '') {
            $note_val = (float)$_POST['mini_devoir_' . $eleve_id];
            if ($note_val >= 0 && $note_val <= 20) {
                $stmt = $pdo->prepare("SELECT id FROM notes WHERE eleve_id = ? AND matiere_id = ? AND trimestre_id = ? AND annee_id = ? AND type_note = 'mini_dev'");
                $stmt->execute([$eleve_id, $matiere_id, $trimestre_id, $annee_id]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE notes SET note = ?, date_note = NOW() WHERE id = ?");
                    $stmt->execute([$note_val, $existing['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notes (eleve_id, matiere_id, type_note, note, date_note, trimestre_id, annee_id) VALUES (?, ?, 'mini_dev', ?, NOW(), ?, ?)");
                    $stmt->execute([$eleve_id, $matiere_id, $note_val, $trimestre_id, $annee_id]);
                }
            }
        }

        // Composition (compo) - type_note: 'compo'
        if (isset($_POST['composition_' . $eleve_id]) && $_POST['composition_' . $eleve_id] !== '') {
            $note_val = (float)$_POST['composition_' . $eleve_id];
            if ($note_val >= 0 && $note_val <= 20) {
                $stmt = $pdo->prepare("SELECT id FROM notes WHERE eleve_id = ? AND matiere_id = ? AND trimestre_id = ? AND annee_id = ? AND type_note = 'compo'");
                $stmt->execute([$eleve_id, $matiere_id, $trimestre_id, $annee_id]);
                $existing = $stmt->fetch();

                if ($existing) {
                    $stmt = $pdo->prepare("UPDATE notes SET note = ?, date_note = NOW() WHERE id = ?");
                    $stmt->execute([$note_val, $existing['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO notes (eleve_id, matiere_id, type_note, note, date_note, trimestre_id, annee_id) VALUES (?, ?, 'compo', ?, NOW(), ?, ?)");
                    $stmt->execute([$eleve_id, $matiere_id, $note_val, $trimestre_id, $annee_id]);
                }
            }
        }
    }

    header("Location: notes_matiere.php?classe_id=$classe_id&matiere_id=$matiere_id&success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes | <?php echo htmlspecialchars($matiere['nom_matiere']); ?> -
        <?php echo htmlspecialchars($classe['nom_classe']); ?></title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #fdf4ff 0%, #fce7f3 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 100%;
        margin: 0 auto;
        background: white;
        border-radius: 24px;
        box-shadow: 0 25px 70px rgba(192, 38, 211, 0.15);
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

    .section {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .section-title {
        font-size: 24px;
        color: #1e293b;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f1f5f9;
    }

    /* Style pour le tableau de notes */
    .notes-table-container {
        overflow-x: auto;
        margin-top: 30px;
        border-radius: 16px;
        border: 2px solid #e2e8f0;
    }

    .notes-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1200px;
    }

    .notes-table th {
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        color: #374151;
        font-weight: 600;
        text-align: center;
        padding: 18px 12px;
        border: 1px solid #d1d5db;
        font-size: 14px;
        white-space: nowrap;
    }

    .notes-table td {
        padding: 15px 12px;
        border: 1px solid #e5e7eb;
        text-align: center;
        vertical-align: middle;
    }

    .student-cell {
        background: #f9fafb;
        font-weight: 500;
        color: #111827;
        text-align: left;
        padding-left: 20px;
        position: sticky;
        left: 0;
        z-index: 2;
        border-right: 2px solid #d1d5db;
    }

    .notes-table input[type="number"] {
        width: 70px;
        padding: 10px;
        border: 2px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        text-align: center;
        background: white;
        transition: all 0.3s ease;
    }

    .notes-table input[type="number"]:focus {
        outline: none;
        border-color: #8b5cf6;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
    }

    .calculated-cell {
        background: #fef3c7;
        font-weight: 600;
        color: #92400e;
        min-width: 80px;
    }

    .rank-cell {
        background: #dcfce7;
        font-weight: 600;
        color: #166534;
        min-width: 70px;
    }

    .appreciation-cell {
        min-width: 150px;
        text-align: left;
        padding-left: 15px;
    }

    .notes-table tr:nth-child(even) {
        background: #f9fafb;
    }

    .notes-table tr:hover {
        background: #f3f4f6;
    }

    .notes-table input[disabled] {
        background: #f3f4f6;
        color: #6b7280;
        border-color: #d1d5db;
        cursor: not-allowed;
    }

    /* Groupes de colonnes */
    .interro-group th {
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        color: #1e40af;
    }

    .devoir-group th {
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        color: #0c4a6e;
    }

    .composition-group th {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        color: #92400e;
    }

    .moyenne-group th {
        background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        color: #166534;
    }

    .evaluation-group th {
        background: linear-gradient(135deg, #f3e8ff, #e9d5ff);
        color: #6b21a8;
    }

    /* Boutons d'action */
    .action-buttons {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #e5e7eb;
    }

    .btn-save {
        padding: 16px 32px;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-save:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
    }

    .btn-calculate {
        padding: 16px 32px;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-calculate:hover {
        background: linear-gradient(135deg, #7c3aed, #6d28d9);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
    }

    /* Alertes */
    .alert {
        padding: 16px 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 2px solid #a7f3d0;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .content {
            padding: 20px;
        }

        .header {
            padding: 30px 20px;
        }

        .header-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .action-buttons {
            flex-direction: column;
        }

        .btn-save,
        .btn-calculate {
            width: 100%;
            justify-content: center;
        }
    }

    /* Icônes dans le tableau */
    .table-icon {
        margin-right: 8px;
        font-size: 14px;
        opacity: 0.8;
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <a href="matieres.php?classe_id=<?= $classe_id ?>" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Retour aux matières
                </a>
                <h1><i class="fas fa-table"></i> Tableau de Notes -
                    <?php echo htmlspecialchars($matiere['nom_matiere']); ?></h1>
                <p>Classe: <?php echo htmlspecialchars($classe['nom_classe']); ?></p>

                <div class="header-info">
                    <div class="info-item">
                        <i class="fas fa-book"></i>
                        <span>Matière: <?php echo htmlspecialchars($matiere['nom_matiere']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Classe: <?php echo htmlspecialchars($classe['nom_classe']); ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-balance-scale"></i>
                        <span>Coefficient: <?php echo $matiere['coef_classe']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Notes enregistrées avec succès !
            </div>
            <?php endif; ?>

            <div class="section">
                <h2 class="section-title">
                    <i class="fas fa-calculator"></i>
                    Paramètres d'évaluation
                </h2>

                <form method="POST" action="" id="notesForm">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                        <div class="form-group">
                            <label for="trimestre_id"><i class="fas fa-calendar-check"></i> Trimestre</label>
                            <select id="trimestre_id" name="trimestre_id" required>
                                <?php foreach ($trimestres as $trimestre): ?>
                                <option value="<?= $trimestre['id'] ?>">
                                    <?php echo htmlspecialchars($trimestre['nom']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="annee_id"><i class="fas fa-calendar-star"></i> Année scolaire</label>
                            <select id="annee_id" name="annee_id" required>
                                <?php foreach ($annees as $annee): ?>
                                <option value="<?= $annee['id'] ?>">
                                    <?php echo htmlspecialchars($annee['libelle']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="notes-table-container">
                        <table class="notes-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="student-cell">Élèves</th>
                                    <!-- Colonnes Interrogations -->
                                    <th colspan="5" class="interro-group">
                                        <i class="fas fa-pencil-alt table-icon"></i>Interrogations
                                    </th>
                                    <th rowspan="2" class="moyenne-group">
                                        <i class="fas fa-calculator table-icon"></i>Moy. Interro
                                    </th>
                                    <!-- Colonnes Devoirs -->
                                    <th colspan="2" class="devoir-group">
                                        <i class="fas fa-file-alt table-icon"></i>Devoirs
                                    </th>
                                    <th rowspan="2" class="moyenne-group">
                                        <i class="fas fa-calculator table-icon"></i>Moy. Devoirs
                                    </th>
                                    <!-- Composition -->
                                    <th rowspan="2" class="composition-group">
                                        <i class="fas fa-clipboard-list table-icon"></i>Composition
                                    </th>
                                    <!-- Moyennes -->
                                    <th colspan="2" class="moyenne-group">
                                        <i class="fas fa-chart-line table-icon"></i>Moyennes
                                    </th>
                                    <!-- Rang et Appréciation -->
                                    <th rowspan="2" class="evaluation-group">
                                        <i class="fas fa-trophy table-icon"></i>Rang
                                    </th>
                                    <th rowspan="2" class="evaluation-group">
                                        <i class="fas fa-comment table-icon"></i>Appréciation
                                    </th>
                                </tr>
                                <tr>
                                    <!-- Sous-titres Interrogations -->
                                    <th class="interro-group">Interro 1</th>
                                    <th class="interro-group">Interro 2</th>
                                    <th class="interro-group">Interro 3</th>
                                    <th class="interro-group">Interro 4</th>
                                    <th class="interro-group">Interro 5</th>
                                    <!-- Sous-titres Devoirs -->
                                    <th class="devoir-group">Devoir</th>
                                    <th class="devoir-group">Mini-Devoir</th>
                                    <!-- Sous-titres Moyennes -->
                                    <th class="moyenne-group">Moy. Coef.</th>
                                    <th class="moyenne-group">Moy. Gén.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eleves as $index => $eleve):
                                    $eleve_id = $eleve['id'];
                                    $eleve_notes = $notes_par_eleve[$eleve_id] ?? [];

                                    // Récupérer les notes spécifiques
                                    $interro1 = '';
                                    $interro2 = '';
                                    $interro3 = '';
                                    $interro4 = '';
                                    $interro5 = '';
                                    $devoir = '';
                                    $mini_devoir = '';
                                    $composition = '';

                                    // Chercher les notes existantes
                                    if (isset($eleve_notes['interro'])) {
                                        // Si on a plusieurs notes de type 'interro', on les répartit
                                        foreach ($eleve_notes['interro'] as $note) {
    switch ($note['numero_interro']) {
        case 1: $interro1 = $note['note']; break;
        case 2: $interro2 = $note['note']; break;
        case 3: $interro3 = $note['note']; break;
        case 4: $interro4 = $note['note']; break;
        case 5: $interro5 = $note['note']; break;
    }
}

                                    }

                                    if (isset($eleve_notes['dev_hebdo'][0])) {
                                        $devoir = $eleve_notes['dev_hebdo'][0]['note'];
                                    }

                                    if (isset($eleve_notes['mini_dev'][0])) {
                                        $mini_devoir = $eleve_notes['mini_dev'][0]['note'];
                                    }

                                    if (isset($eleve_notes['compo'][0])) {
                                        $composition = $eleve_notes['compo'][0]['note'];
                                    }
                                ?>
                                <tr data-eleve-id="<?= $eleve_id ?>">
                                    <td class="student-cell">
                                        <?php echo htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']); ?><br>
                                        <small
                                            style="color: #6b7280;"><?php echo htmlspecialchars($eleve['matricule']); ?></small>
                                    </td>

                                    <!-- Interrogations -->
                                    <td><input type="number" name="interro_<?= $eleve_id ?>_1" value="<?= $interro1 ?>"
                                            min="0" max="20" step="0.01" placeholder="0-20"></td>
                                    <td><input type="number" name="interro_<?= $eleve_id ?>_2" value="<?= $interro2 ?>"
                                            min="0" max="20" step="0.01" placeholder="0-20"></td>
                                    <td><input type="number" name="interro_<?= $eleve_id ?>_3" value="<?= $interro3 ?>"
                                            min="0" max="20" step="0.01" placeholder="0-20"></td>
                                    <td><input type="number" name="interro_<?= $eleve_id ?>_4" value="<?= $interro4 ?>"
                                            min="0" max="20" step="0.01" placeholder="0-20"></td>
                                    <td><input type="number" name="interro_<?= $eleve_id ?>_5" value="<?= $interro5 ?>"
                                            min="0" max="20" step="0.01" placeholder="0-20"></td>

                                    <!-- Moyenne Interro (calculée) -->
                                    <td class="calculated-cell" id="moy_interro_<?= $eleve_id ?>">0.00</td>

                                    <!-- Devoirs -->
                                    <td><input type="number" name="devoir_<?= $eleve_id ?>" value="<?= $devoir ?>"
                                            min="0" max="20" step="0.01" placeholder="0-20"></td>
                                    <td><input type="number" name="mini_devoir_<?= $eleve_id ?>"
                                            value="<?= $mini_devoir ?>" min="0" max="20" step="0.01" placeholder="0-20">
                                    </td>

                                    <!-- Moyenne Devoirs (calculée) -->
                                    <td class="calculated-cell" id="moy_devoirs_<?= $eleve_id ?>">0.00</td>

                                    <!-- Composition -->
                                    <td><input type="number" name="composition_<?= $eleve_id ?>"
                                            value="<?= $composition ?>" min="0" max="20" step="0.01" placeholder="0-20">
                                    </td>

                                    <!-- Moyenne Coefficient -->
                                    <td class="calculated-cell" id="moy_coef_<?= $eleve_id ?>">0.00</td>

                                    <!-- Moyenne Générale (calculée selon votre formule) -->
                                    <td class="calculated-cell" id="moy_generale_<?= $eleve_id ?>">0.00</td>

                                    <!-- Rang (calculé) -->
                                    <td class="rank-cell" id="rang_<?= $eleve_id ?>">-</td>

                                    <!-- Appréciation (calculée) -->
                                    <td class="appreciation-cell" id="appreciation_<?= $eleve_id ?>">-</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="action-buttons">
                        <button type="button" class="btn-calculate" onclick="calculerMoyennes()">
                            <i class="fas fa-calculator"></i> Calculer les Moyennes
                        </button>

                        <button type="submit" name="enregistrer_notes" class="btn-save">
                            <i class="fas fa-save"></i> Enregistrer toutes les Notes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function calculerMoyennes() {
        const eleves = document.querySelectorAll('tr[data-eleve-id]');
        const moyennesGenerales = [];

        eleves.forEach(row => {
            const eleveId = row.dataset.eleveId;

            // Calcul de la moyenne des interrogations (moyenne des 5 interrogations)
            let sumInterro = 0;
            let countInterro = 0;
            for (let i = 1; i <= 5; i++) {
                const input = row.querySelector(`input[name="interro_${eleveId}_${i}"]`);
                if (input.value && !isNaN(parseFloat(input.value))) {
                    sumInterro += parseFloat(input.value);
                    countInterro++;
                }
            }
            const moyInterro = countInterro > 0 ? sumInterro / countInterro : 0;
            document.getElementById(`moy_interro_${eleveId}`).textContent = moyInterro.toFixed(2);

            // Calcul de la moyenne des devoirs (moyenne du devoir et mini-devoir)
            const devoirInput = row.querySelector(`input[name="devoir_${eleveId}"]`);
            const miniDevoirInput = row.querySelector(`input[name="mini_devoir_${eleveId}"]`);

            let sumDevoirs = 0;
            let countDevoirs = 0;

            if (devoirInput.value && !isNaN(parseFloat(devoirInput.value))) {
                sumDevoirs += parseFloat(devoirInput.value);
                countDevoirs++;
            }
            if (miniDevoirInput.value && !isNaN(parseFloat(miniDevoirInput.value))) {
                sumDevoirs += parseFloat(miniDevoirInput.value);
                countDevoirs++;
            }

            const moyDevoirs = countDevoirs > 0 ? sumDevoirs / countDevoirs : 0;
            document.getElementById(`moy_devoirs_${eleveId}`).textContent = moyDevoirs.toFixed(2);

            // Récupération de la composition
            const compoInput = row.querySelector(`input[name="composition_${eleveId}"]`);
            const composition = compoInput.value && !isNaN(parseFloat(compoInput.value)) ? parseFloat(compoInput
                .value) : 0;

            // Calcul de la moyenne générale selon la formule: (((Moy interro + Moy dev)/2) + Compo)/2
            const moyGenerale = (((moyInterro + moyDevoirs) / 2) + composition) / 2;

            // Arrondir à 2 décimales
            const moyGeneraleArrondie = moyGenerale.toFixed(2);

            // Calcul de la moyenne avec coefficient
            const coefficient = <?= $matiere['coef_classe'] ?>;
            const moyCoef = (moyGenerale * coefficient).toFixed(2);

            // Stocker la moyenne générale pour le classement
            moyennesGenerales.push({
                eleveId: eleveId,
                moyenne: parseFloat(moyCoef) // 🔥 classement basé sur coef
            });

            // Mettre à jour les cellules
            document.getElementById(`moy_coef_${eleveId}`).textContent = moyCoef;
            document.getElementById(`moy_generale_${eleveId}`).textContent = moyGeneraleArrondie;

            // Déterminer l'appréciation
            let appreciation = '';
            if (moyGenerale >= 16) appreciation = 'Excellent';
            else if (moyGenerale >= 14) appreciation = 'Très bien';
            else if (moyGenerale >= 12) appreciation = 'Bien';
            else if (moyGenerale >= 10) appreciation = 'Assez bien';
            else if (moyGenerale >= 8) appreciation = 'Passable';
            else if (moyGenerale >= 5) appreciation = 'Insuffisant';
            else appreciation = 'Très faible';

            document.getElementById(`appreciation_${eleveId}`).textContent = appreciation;
        });

        // Calcul du rang
        moyennesGenerales.sort((a, b) => b.moyenne - a.moyenne);

        let currentRank = 1;
        let previousMoyenne = null;
        let skipCount = 0;

        for (let i = 0; i < moyennesGenerales.length; i++) {
            if (previousMoyenne !== null && moyennesGenerales[i].moyenne < previousMoyenne) {
                currentRank += 1 + skipCount;
                skipCount = 0;
            } else if (previousMoyenne !== null && moyennesGenerales[i].moyenne === previousMoyenne) {
                skipCount++;
            }

            document.getElementById(`rang_${moyennesGenerales[i].eleveId}`).textContent = currentRank;
            previousMoyenne = moyennesGenerales[i].moyenne;
        }
    }

    // Calculer automatiquement au chargement si des notes existent
    document.addEventListener('DOMContentLoaded', function() {
        calculerMoyennes();

        // Ajouter des écouteurs d'événements pour le recalcul automatique
        document.querySelectorAll('input[type="number"]').forEach(input => {
            input.addEventListener('change', calculerMoyennes);
            input.addEventListener('input', calculerMoyennes);
        });
    });
    </script>
</body>

</html>