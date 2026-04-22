<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';
$message = "";
$message_type = "";

// Récupérer les classes, matières, trimestres et années scolaires
$classes = $pdo->query("SELECT * FROM classes ORDER BY nom_classe")->fetchAll();
$matieres = $pdo->query("SELECT * FROM matieres ORDER BY nom_matiere")->fetchAll();
$trimestres = $pdo->query("SELECT * FROM trimestres WHERE actif=1 ORDER BY ordre")->fetchAll();
$annees = $pdo->query("SELECT * FROM annees_scolaires ORDER BY libelle DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $eleve_id = (int)$_POST['eleve_id'];
    $matiere_id = (int)$_POST['matiere_id'];
    $type_note = $_POST['type_note'];
    $note = floatval($_POST['note']);
    $date_note = $_POST['date_note'];
    $trimestre_id = (int)$_POST['trimestre_id'];
    $annee_id = (int)$_POST['annee_id'];

    // Validation
    if ($note < 0 || $note > 20) {
        $message = "La note doit être comprise entre 0 et 20.";
        $message_type = "error";
    } else {
        // Vérifier si une note du même type existe déjà
        $stmt_check = $pdo->prepare("SELECT * FROM notes WHERE eleve_id=? AND matiere_id=? AND type_note=? AND trimestre_id=? AND annee_id=?");
        $stmt_check->execute([$eleve_id, $matiere_id, $type_note, $trimestre_id, $annee_id]);

        if ($stmt_check->rowCount() > 0) {
            // Mise à jour
            $stmt = $pdo->prepare("UPDATE notes SET note=?, date_note=? WHERE eleve_id=? AND matiere_id=? AND type_note=? AND trimestre_id=? AND annee_id=?");
            if ($stmt->execute([$note, $date_note, $eleve_id, $matiere_id, $type_note, $trimestre_id, $annee_id])) {
                $message = "✅ Note mise à jour avec succès !";
                $message_type = "success";
                // Réinitialiser certains champs
                $_POST['note'] = '';
                $_POST['date_note'] = '';
            } else {
                $message = "❌ Erreur lors de la mise à jour.";
                $message_type = "error";
            }
        } else {
            // Insertion
            $stmt = $pdo->prepare("INSERT INTO notes (eleve_id, matiere_id, type_note, note, date_note, trimestre_id, annee_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$eleve_id, $matiere_id, $type_note, $note, $date_note, $trimestre_id, $annee_id])) {
                $message = "✅ Note ajoutée avec succès !";
                $message_type = "success";
                // Réinitialiser certains champs
                $_POST['note'] = '';
                $_POST['date_note'] = '';
            } else {
                $message = "❌ Erreur lors de l'ajout.";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des notes | Gestion Scolaire</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f8f9ff 0%, #e8eefe 100%);
        min-height: 100vh;
        padding: 30px 20px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .container {
        width: 100%;
        max-width: 1000px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 25px 70px rgba(59, 130, 246, 0.15);
        overflow: hidden;
        animation: slideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(40px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .header {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
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
        font-size: 34px;
        font-weight: 700;
        margin-bottom: 12px;
        letter-spacing: -0.5px;
    }

    .header p {
        font-size: 16px;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.6;
    }

    .content {
        padding: 40px;
    }

    .message {
        padding: 22px;
        border-radius: 16px;
        margin-bottom: 35px;
        text-align: center;
        font-size: 16px;
        font-weight: 500;
        animation: messageIn 0.4s ease-out;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
    }

    @keyframes messageIn {
        from {
            opacity: 0;
            transform: translateY(-15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.success {
        background: linear-gradient(to right, #d1fae5, #a7f3d0);
        color: #065f46;
        border: 2px solid #34d399;
    }

    .message.error {
        background: linear-gradient(to right, #fee2e2, #fecaca);
        color: #7f1d1d;
        border: 2px solid #f87171;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
        margin-bottom: 40px;
    }

    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-section {
        background: #f8fafc;
        padding: 35px;
        border-radius: 20px;
        border: 2px solid #f1f5f9;
    }

    .form-section h3 {
        font-size: 18px;
        color: #1e40af;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e2e8f0;
    }

    .form-section h3 i {
        color: #3b82f6;
    }

    .form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    label {
        display: block;
        margin-bottom: 12px;
        color: #334155;
        font-weight: 600;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    label i {
        color: #3b82f6;
        font-size: 16px;
        width: 24px;
    }

    .required::after {
        content: ' *';
        color: #ef4444;
        font-weight: bold;
    }

    select,
    input[type="number"],
    input[type="date"] {
        width: 100%;
        padding: 18px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: white;
        color: #1e293b;
        appearance: none;
    }

    select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%233b82f6' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 20px center;
        background-size: 16px;
        padding-right: 50px;
    }

    select:focus,
    input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
        transform: translateY(-2px);
    }

    .note-preview {
        margin-top: 15px;
        padding: 20px;
        background: white;
        border-radius: 14px;
        border: 2px dashed #e2e8f0;
        text-align: center;
        transition: all 0.3s ease;
    }

    .note-value {
        font-size: 48px;
        font-weight: 800;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        --webkit-background-clip: text;
        --webkit-text-fill-color: transparent;
        margin-bottom: 10px;
    }

    .note-label {
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
    }

    .note-grade {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        margin-top: 10px;
    }

    .note-excellent {
        background: #d1fae5;
        color: #065f46;
    }

    .note-good {
        background: #fef3c7;
        color: #92400e;
    }

    .note-average {
        background: #fde68a;
        color: #92400e;
    }

    .note-poor {
        background: #fee2e2;
        color: #7f1d1d;
    }

    .student-info {
        margin-top: 15px;
        padding: 20px;
        background: white;
        border-radius: 14px;
        border: 2px solid #e2e8f0;
        text-align: center;
        display: none;
    }

    .student-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        font-weight: 600;
        margin: 0 auto 15px;
    }

    .student-name {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 5px;
    }

    .student-details {
        color: #64748b;
        font-size: 14px;
    }

    .form-actions {
        display: flex;
        gap: 20px;
        padding: 35px 0;
        border-top: 2px solid #e2e8f0;
    }

    .btn {
        flex: 1;
        padding: 22px;
        border: none;
        border-radius: 16px;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        text-decoration: none;
    }

    .btn-submit {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
    }

    .btn-submit:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(59, 130, 246, 0.4);
    }

    .btn-submit:active {
        transform: translateY(-1px);
    }

    .btn-reset {
        background: #f1f5f9;
        color: #64748b;
        border: 2px solid #cbd5e1;
    }

    .btn-reset:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }

    .btn-icon {
        font-size: 20px;
    }

    .nav-links {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        padding-top: 30px;
        border-top: 2px solid #f1f5f9;
    }

    @media (max-width: 768px) {
        .nav-links {
            grid-template-columns: 1fr;
        }
    }

    .nav-links a {
        padding: 20px;
        background: #f8fafc;
        color: #475569;
        text-decoration: none;
        border-radius: 16px;
        font-weight: 500;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        transition: all 0.3s ease;
        border: 2px solid #e2e8f0;
    }

    .nav-links a:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
    }

    .nav-links a:nth-child(2):hover {
        background: #10b981;
        border-color: #10b981;
    }

    .nav-links a:nth-child(3):hover {
        background: #8b5cf6;
        border-color: #8b5cf6;
    }

    .type-badges {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-top: 15px;
    }

    .type-badge {
        padding: 14px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        text-align: center;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .type-badge:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
        transform: translateY(-2px);
    }

    .type-badge.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    .type-label {
        font-size: 14px;
        font-weight: 600;
    }

    .type-desc {
        font-size: 12px;
        opacity: 0.8;
        font-weight: normal;
    }

    .loading {
        display: none;
        text-align: center;
        padding: 20px;
        color: #64748b;
    }

    .loading i {
        font-size: 24px;
        margin-bottom: 10px;
        color: #3b82f6;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    @media (max-width: 600px) {
        .container {
            border-radius: 20px;
        }

        .header,
        .content {
            padding: 30px 25px;
        }

        .header h1 {
            font-size: 26px;
        }

        .form-section {
            padding: 25px;
        }

        .form-actions {
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
                <div class="header-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h1>Gestion des Notes</h1>
                <p>Ajoutez ou modifiez les notes des élèves - Mise à jour automatique en cas de doublon</p>
            </div>
        </div>

        <div class="content">
            <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i
                    class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="gradeForm">
                <div class="form-grid">
                    <div class="form-section">
                        <h3><i class="fas fa-user-graduate"></i> Informations Élève</h3>

                        <div class="form-group">
                            <label for="classe_id" class="required">
                                <i class="fas fa-school"></i> Classe
                            </label>
                            <select name="classe_id" id="classe_id" required>
                                <option value="">-- Sélectionner une classe --</option>
                                <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    <?php echo isset($_POST['classe_id']) && $_POST['classe_id'] == $c['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($c['nom_classe']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="eleve_id" class="required">
                                <i class="fas fa-user"></i> Élève
                            </label>
                            <select name="eleve_id" id="eleve_id" required>
                                <option value="">-- Sélectionner une classe d'abord --</option>
                            </select>
                            <div id="studentLoading" class="loading">
                                <i class="fas fa-spinner"></i>
                                <p>Chargement des élèves...</p>
                            </div>
                            <div id="studentInfo" class="student-info">
                                <!-- Rempli dynamiquement -->
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-book"></i> Information Note</h3>

                        <div class="form-group">
                            <label for="matiere_id" class="required">
                                <i class="fas fa-book-open"></i> Matière
                            </label>
                            <select name="matiere_id" id="matiere_id" required>
                                <option value="">-- Sélectionner une matière --</option>
                                <?php foreach ($matieres as $m): ?>
                                <option value="<?= $m['id'] ?>"
                                    <?php echo isset($_POST['matiere_id']) && $_POST['matiere_id'] == $m['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($m['nom_matiere']) ?> (Coeff: <?= $m['coefficient'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="required">
                                <i class="fas fa-tag"></i> Type de note
                            </label>
                            <input type="hidden" name="type_note" id="type_note" required>
                            <div class="type-badges">
                                <div class="type-badge" data-value="interro">
                                    <span class="type-label">Interro</span>
                                    <span class="type-desc">Court, 15-20 min</span>
                                </div>
                                <div class="type-badge" data-value="mini_dev">
                                    <span class="type-label">Mini devoir</span>
                                    <span class="type-desc">30-45 min</span>
                                </div>
                                <div class="type-badge" data-value="dev_hebdo">
                                    <span class="type-label">Devoir hebdo</span>
                                    <span class="type-desc">1h-1h30</span>
                                </div>
                                <div class="type-badge" data-value="compo">
                                    <span class="type-label">Composition</span>
                                    <span class="type-desc">2h ou plus</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="note" class="required">
                                <i class="fas fa-star"></i> Note /20
                            </label>
                            <input type="number" id="note" name="note" step="0.01" min="0" max="20"
                                value="<?php echo isset($_POST['note']) ? $_POST['note'] : ''; ?>"
                                placeholder="0.00 - 20.00" required>
                            <div class="note-preview" id="notePreview">
                                <div class="note-value">--</div>
                                <div class="note-label">Prévisualisation</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3><i class="fas fa-calendar-alt"></i> Période</h3>

                        <div class="form-group">
                            <label for="date_note" class="required">
                                <i class="fas fa-calendar-day"></i> Date de la note
                            </label>
                            <input type="date" id="date_note" name="date_note"
                                value="<?php echo isset($_POST['date_note']) ? $_POST['date_note'] : date('Y-m-d'); ?>"
                                required>
                        </div>

                        <div class="form-group">
                            <label for="trimestre_id" class="required">
                                <i class="fas fa-calendar-check"></i> Trimestre
                            </label>
                            <select name="trimestre_id" id="trimestre_id" required>
                                <option value="">-- Sélectionner un trimestre --</option>
                                <?php foreach ($trimestres as $t): ?>
                                <option value="<?= $t['id'] ?>"
                                    <?php echo isset($_POST['trimestre_id']) && $_POST['trimestre_id'] == $t['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($t['nom'] . ' (T' . $t['ordre'] . ')') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="annee_id" class="required">
                                <i class="fas fa-calendar-star"></i> Année scolaire
                            </label>
                            <select name="annee_id" id="annee_id" required>
                                <option value="">-- Sélectionner une année --</option>
                                <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>"
                                    <?php echo isset($_POST['annee_id']) && $_POST['annee_id'] == $a['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($a['libelle']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-reset">
                        <i class="fas fa-redo btn-icon"></i> Réinitialiser
                    </button>
                    <button type="submit" name="submit" class="btn btn-submit">
                        <i class="fas fa-save btn-icon"></i> Enregistrer la note
                    </button>
                </div>
            </form>

            <div class="nav-links">
                <a href="liste.php">
                    <i class="fas fa-list-check"></i> Voir toutes les notes
                </a>
                <a href="../eleves/liste.php">
                    <i class="fas fa-users"></i> Gérer les élèves
                </a>
                <a href="../accueil.php">
                    <i class="fas fa-home"></i> Tableau de bord
                </a>
            </div>
        </div>
    </div>

    <script>
    // Gestion du type de note
    const typeBadges = document.querySelectorAll('.type-badge');
    const typeInput = document.getElementById('type_note');

    typeBadges.forEach(badge => {
        badge.addEventListener('click', function() {
            typeBadges.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            typeInput.value = this.dataset.value;
        });
    });

    // Prévisualisation de la note
    const noteInput = document.getElementById('note');
    const notePreview = document.getElementById('notePreview');
    const noteValue = notePreview.querySelector('.note-value');

    noteInput.addEventListener('input', function() {
        const value = parseFloat(this.value) || 0;

        if (value >= 0 && value <= 20) {
            noteValue.textContent = value.toFixed(2);

            // Déterminer la classe de la note
            let gradeClass = '';
            let gradeText = '';

            if (value >= 16) {
                gradeClass = 'note-excellent';
                gradeText = 'Excellent';
            } else if (value >= 14) {
                gradeClass = 'note-good';
                gradeText = 'Très bien';
            } else if (value >= 10) {
                gradeClass = 'note-average';
                gradeText = 'Moyen';
            } else {
                gradeClass = 'note-poor';
                gradeText = 'À améliorer';
            }

            // Mettre à jour l'affichage
            const existingGrade = notePreview.querySelector('.note-grade');
            if (existingGrade) {
                existingGrade.remove();
            }

            const gradeDiv = document.createElement('div');
            gradeDiv.className = `note-grade ${gradeClass}`;
            gradeDiv.textContent = gradeText;
            notePreview.appendChild(gradeDiv);
        } else {
            noteValue.textContent = '--';
            const existingGrade = notePreview.querySelector('.note-grade');
            if (existingGrade) {
                existingGrade.remove();
            }
        }
    });

    // Chargement des élèves par classe
    const classeSelect = document.getElementById('classe_id');
    const eleveSelect = document.getElementById('eleve_id');
    const studentLoading = document.getElementById('studentLoading');
    const studentInfo = document.getElementById('studentInfo');

    classeSelect.addEventListener('change', function() {
        const classeId = this.value;

        if (!classeId) {
            eleveSelect.innerHTML = '<option value="">-- Sélectionner une classe d\'abord --</option>';
            studentInfo.style.display = 'none';
            return;
        }

        // Afficher le loading
        studentLoading.style.display = 'block';
        eleveSelect.style.display = 'none';
        studentInfo.style.display = 'none';

        // Charger les élèves
        fetch('get_eleves.php?classe_id=' + classeId)
            .then(response => response.json())
            .then(data => {
                eleveSelect.innerHTML = '<option value="">-- Sélectionner un élève --</option>';

                data.forEach(eleve => {
                    const option = document.createElement('option');
                    option.value = eleve.id;
                    option.textContent = `${eleve.nom} ${eleve.prenom}`;
                    option.dataset.info = JSON.stringify(eleve);
                    eleveSelect.appendChild(option);
                });

                studentLoading.style.display = 'none';
                eleveSelect.style.display = 'block';
            })
            .catch(error => {
                console.error('Erreur:', error);
                studentLoading.style.display = 'none';
                eleveSelect.style.display = 'block';
                eleveSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    });

    // Afficher les informations de l'élève sélectionné
    eleveSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];

        if (selectedOption.value && selectedOption.dataset.info) {
            const eleve = JSON.parse(selectedOption.dataset.info);

            studentInfo.innerHTML = `
                    <div class="student-avatar">
                        ${eleve.prenom.charAt(0)}${eleve.nom.charAt(0)}
                    </div>
                    <div class="student-name">${eleve.prenom} ${eleve.nom}</div>
                    <div class="student-details">
                        Matricule: ${eleve.matricule}<br>
                        Classe: ${eleve.classe_nom}
                    </div>
                `;
            studentInfo.style.display = 'block';
        } else {
            studentInfo.style.display = 'none';
        }
    });

    // Validation du formulaire
    document.getElementById('gradeForm').addEventListener('submit', function(e) {
        let isValid = true;
        const errors = [];

        // Vérifier le type de note
        if (!typeInput.value) {
            errors.push('Veuillez sélectionner un type de note');
            isValid = false;
        }

        // Vérifier la note
        const note = parseFloat(noteInput.value);
        if (isNaN(note) || note < 0 || note > 20) {
            errors.push('La note doit être comprise entre 0 et 20');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            showAlert(errors.join('<br>'), 'error');
            return false;
        }

        // Afficher l'état de chargement
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin btn-icon"></i> Enregistrement...';
        submitBtn.disabled = true;

        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    });

    // Initialiser les badges de type
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_POST['type_note'])): ?>
        const typeValue = '<?php echo $_POST['type_note']; ?>';
        typeBadges.forEach(badge => {
            if (badge.dataset.value === typeValue) {
                badge.classList.add('active');
                typeInput.value = typeValue;
            }
        });
        <?php endif; ?>

        // Déclencher l'événement note pour la prévisualisation
        if (noteInput.value) {
            noteInput.dispatchEvent(new Event('input'));
        }
    });

    // Fonction d'affichage des alertes
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `message ${type}`;
        alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;

        const contentDiv = document.querySelector('.content');
        const existingAlert = document.querySelector('.message');
        if (existingAlert) {
            existingAlert.remove();
        }

        contentDiv.insertBefore(alertDiv, contentDiv.firstChild);

        setTimeout(() => {
            alertDiv.style.opacity = '0';
            alertDiv.style.transform = 'translateY(-15px)';
            setTimeout(() => alertDiv.remove(), 300);
        }, 5000);
    }

    // Auto-hide existing message
    const existingMessage = document.querySelector('.message');
    if (existingMessage) {
        setTimeout(() => {
            existingMessage.style.opacity = '0';
            existingMessage.style.transform = 'translateY(-15px)';
            setTimeout(() => existingMessage.remove(), 300);
        }, 6000);
    }
    </script>
</body>

</html>