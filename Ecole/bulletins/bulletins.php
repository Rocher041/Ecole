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
$trimestres = $pdo->query("SELECT * FROM trimestres WHERE actif=1 ORDER BY ordre")->fetchAll();
$annees = $pdo->query("SELECT * FROM annees_scolaires ORDER BY libelle DESC")->fetchAll();

$bulletin = null;
$eleve = null;
$eleve_id = null;
$trimestre_id = null;
$annee_id = null;

/* =====================
   TRAITEMENT FORMULAIRE
===================== */
if (isset($_POST['voir'])) {
    $eleve_id = (int)$_POST['eleve_id'];
    $trimestre_id = (int)$_POST['trimestre_id'];
    $annee_id = (int)$_POST['annee_id'];

    /* Infos élève */
    $stmt = $pdo->prepare("
        SELECT e.*, c.nom_classe
        FROM eleves e
        JOIN classes c ON c.id = e.classe_id
        WHERE e.id = ?
    ");
    $stmt->execute([$eleve_id]);
    $eleve = $stmt->fetch();

    /* Notes par matière */
    $stmt = $pdo->prepare("
    SELECT 
        m.nom_matiere,
        AVG(
    CASE 
        WHEN n.type_note = 'interro' 
        AND n.numero_interro IS NOT NULL 
        THEN n.note 
    END
) AS interro,

        MAX(CASE WHEN n.type_note = 'mini_dev' THEN n.note END) AS mini_dev,
        MAX(CASE WHEN n.type_note = 'dev_hebdo' THEN n.note END) AS dev_hebdo,
        MAX(CASE WHEN n.type_note = 'compo' THEN n.note END) AS compo
    FROM eleves e
    JOIN classe_matiere cm ON cm.classe_id = e.classe_id
    JOIN matieres m ON m.id = cm.matiere_id
    LEFT JOIN notes n 
        ON n.matiere_id = m.id
        AND n.eleve_id = e.id
        AND n.trimestre_id = ?
        AND n.annee_id = ?
    WHERE e.id = ?
    GROUP BY m.id
    ORDER BY m.nom_matiere
");

    $stmt->execute([$trimestre_id, $annee_id, $eleve_id]);

    $bulletin = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulletin Scolaire | Scolarité</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #f0f9ff;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #0ea5e9;
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
            text-align: center;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .header p {
            color: var(--gray);
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .header p i {
            color: var(--primary);
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

        .form-section {
            margin-bottom: 40px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            color: var(--dark);
            font-size: 22px;
            font-weight: 700;
        }

        .section-title i {
            color: var(--primary);
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(79, 70, 229, 0.1));
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            font-weight: 600;
            color: var(--dark);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--primary);
            width: 20px;
            text-align: center;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--border);
            border-radius: 12px;
            font-size: 16px;
            font-weight: 500;
            color: var(--dark);
            background: white;
            transition: var(--transition);
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 20px;
            padding-right: 50px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .btn {
            padding: 18px 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 12px;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .btn i {
            font-size: 18px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }

        .loading i {
            font-size: 48px;
            margin-bottom: 20px;
            color: var(--primary);
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

        /* Bulletin Styles */
        .bulletin-header {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: var(--radius);
            color: white;
            margin-bottom: 30px;
        }

        .bulletin-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .bulletin-header p {
            opacity: 0.9;
            font-size: 16px;
        }

        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .info-card h4 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card p {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 16px;
            border: 1px solid var(--border);
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
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
            padding: 20px;
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

        .subject-name {
            font-weight: 600;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .subject-name i {
            color: var(--primary);
        }

        .grade-cell {
            text-align: center;
            font-weight: 700;
            font-size: 18px;
        }

        .grade-excellent {
            color: var(--success);
        }

        .grade-good {
            color: #3b82f6;
        }

        .grade-average {
            color: var(--warning);
        }

        .grade-poor {
            color: var(--danger);
        }

        .summary-card {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 16px;
            padding: 30px;
            border: 1px solid var(--border);
            margin-top: 30px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-item {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .summary-item h4 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .summary-value {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .overall-grade .summary-value {
            font-size: 48px;
            color: var(--primary);
        }

        .rank {
            color: var(--success);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .btn-print {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
        }

        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .btn-back {
            background: white;
            color: var(--dark);
            border: 2px solid var(--border);
            text-decoration: none;
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
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
            .glass-card {
                padding: 25px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn,
            .btn-print,
            .btn-back {
                width: 100%;
                justify-content: center;
            }
        }

        .grade-details {
            font-size: 12px;
            color: var(--gray);
            margin-top: 5px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .grade-detail {
            background: var(--light);
            padding: 4px 8px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Bulletin Scolaire</h1>
            <p>
                <i class="fas fa-chart-line"></i>
                Consultez les résultats académiques des élèves
            </p>
        </div>

        <div class="glass-card">
            <div class="form-section">
                <div class="section-title">
                    <i class="fas fa-search"></i>
                    <span>Rechercher un bulletin</span>
                </div>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="classe_id">
                                <i class="fas fa-school"></i>
                                Classe
                            </label>
                            <select id="classe_id" class="form-control" required>
                                <option value="">-- Sélectionner une classe --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom_classe']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="eleve_id">
                                <i class="fas fa-user-graduate"></i>
                                Élève
                            </label>
                            <select name="eleve_id" id="eleve_id" class="form-control" required>
                                <option value="">-- Choisissez d'abord une classe --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="trimestre_id">
                                <i class="fas fa-calendar-alt"></i>
                                Trimestre
                            </label>
                            <select name="trimestre_id" id="trimestre_id" class="form-control" required>
                                <?php foreach ($trimestres as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="annee_id">
                                <i class="fas fa-calendar-star"></i>
                                Année scolaire
                            </label>
                            <select name="annee_id" id="annee_id" class="form-control" required>
                                <?php foreach ($annees as $a): ?>
                                    <option value="<?= $a['id'] ?>" <?= $a['active'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="text-align: center;">
                        <button type="submit" name="voir" class="btn">
                            <i class="fas fa-eye"></i>
                            Consulter le bulletin
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($bulletin && $eleve): ?>
                <div class="bulletin-result" id="bulletinResult">
                    <div class="bulletin-header">
                        <h2>BULLETIN SCOLAIRE</h2>
                        <p>Résultats académiques de l'élève</p>
                    </div>

                    <div class="student-info">
                        <div class="info-card">
                            <h4>ÉLÈVE</h4>
                            <p><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></p>
                        </div>
                        <div class="info-card">
                            <h4>CLASSE</h4>
                            <p><?= htmlspecialchars($eleve['nom_classe']) ?></p>
                        </div>
                        <div class="info-card">
                            <h4>TRIMESTRE</h4>
                            <p>
                                <?php
                                $trimestre_name = '';
                                foreach ($trimestres as $t) {
                                    if ($t['id'] == $trimestre_id) {
                                        $trimestre_name = $t['nom'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($trimestre_name);
                                ?>
                            </p>
                        </div>
                        <div class="info-card">
                            <h4>ANNÉE SCOLAIRE</h4>
                            <p>
                                <?php
                                $annee_name = '';
                                foreach ($annees as $a) {
                                    if ($a['id'] == $annee_id) {
                                        $annee_name = $a['libelle'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($annee_name);
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-book"></i> Matière</th>
                                    <th><i class="fas fa-chart-bar"></i> Moyenne</th>
                                    <th><i class="fas fa-info-circle"></i> Détails</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total = 0;
                                $count = 0;
                                $subjects_data = [];

                                foreach ($bulletin as $b):
                                    $interro   = $b['interro'];
                                    $mini_dev  = $b['mini_dev'];
                                    $dev_hebdo = $b['dev_hebdo'];
                                    $compo     = $b['compo'];

                                    /* ---- Moyenne devoirs ---- */
                                    $dev_notes = [];
                                    if ($mini_dev !== null)  $dev_notes[] = $mini_dev;
                                    if ($dev_hebdo !== null) $dev_notes[] = $dev_hebdo;
                                    $moy_dev = count($dev_notes) ? array_sum($dev_notes) / count($dev_notes) : null;

                                    /* ---- Moyenne interro + devoirs ---- */
                                    $int_notes = [];
                                    if ($interro !== null) $int_notes[] = $interro;
                                    if ($moy_dev !== null) $int_notes[] = $moy_dev;
                                    $moy_int = count($int_notes) ? array_sum($int_notes) / count($int_notes) : null;

                                    /* ---- Moyenne finale ---- */
                                    $final_notes = [];
                                    if ($moy_int !== null) $final_notes[] = $moy_int;
                                    if ($compo !== null)   $final_notes[] = $compo;
                                    $moy = count($final_notes) ? array_sum($final_notes) / count($final_notes) : null;

                                    if ($moy !== null) {
                                        $total += $moy;
                                        $count++;
                                    }

                                    $grade_class = '';
                                    if ($moy !== null) {
                                        if ($moy >= 16) $grade_class = 'grade-excellent';
                                        elseif ($moy >= 14) $grade_class = 'grade-good';
                                        elseif ($moy >= 10) $grade_class = 'grade-average';
                                        else $grade_class = 'grade-poor';
                                    }

                                    $subjects_data[] = [
                                        'name' => $b['nom_matiere'],
                                        'average' => $moy,
                                        'details' => compact('interro', 'mini_dev', 'dev_hebdo', 'compo', 'moy_dev', 'moy_int')
                                    ];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="subject-name">
                                                <i class="fas fa-book-open"></i>
                                                <?= htmlspecialchars($b['nom_matiere']) ?>
                                            </div>
                                        </td>
                                        <td class="grade-cell <?= $grade_class ?>">
                                            <?= $moy !== null ? number_format($moy, 2) : '—' ?>
                                            <?php if ($moy !== null): ?>
                                                <div style="font-size: 12px; color: var(--gray); margin-top: 5px;">
                                                    <?= $moy >= 10 ? '✓ Validé' : '✗ Non validé' ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="grade-details">
                                                <?php if ($interro !== null): ?>
                                                    <span class="grade-detail">Interro: <?= $interro ?>/20</span>
                                                <?php endif; ?>
                                                <?php if ($mini_dev !== null): ?>
                                                    <span class="grade-detail">Mini-dev: <?= $mini_dev ?>/20</span>
                                                <?php endif; ?>
                                                <?php if ($dev_hebdo !== null): ?>
                                                    <span class="grade-detail">Dev. Hebdo: <?= $dev_hebdo ?>/20</span>
                                                <?php endif; ?>
                                                <?php if ($compo !== null): ?>
                                                    <span class="grade-detail">Composition: <?= $compo ?>/20</span>
                                                <?php endif; ?>
                                                <?php if ($moy === null): ?>
                                                    <span class="grade-detail" style="color: var(--danger);">Aucune note</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php
                    $moyenne_generale = $count > 0 ? $total / $count : 0;
                    $mention = '';
                    $mention_color = '';

                    if ($moyenne_generale >= 16) {
                        $mention = 'Très Bien';
                        $mention_color = 'var(--success)';
                    } elseif ($moyenne_generale >= 14) {
                        $mention = 'Bien';
                        $mention_color = '#3b82f6';
                    } elseif ($moyenne_generale >= 12) {
                        $mention = 'Assez Bien';
                        $mention_color = '#8b5cf6';
                    } elseif ($moyenne_generale >= 10) {
                        $mention = 'Passable';
                        $mention_color = 'var(--warning)';
                    } else {
                        $mention = 'Insuffisant';
                        $mention_color = 'var(--danger)';
                    }
                    ?>

                    <div class="summary-card">
                        <div class="summary-grid">
                            <div class="summary-item">
                                <h4>Matières évaluées</h4>
                                <div class="summary-value"><?= $count ?></div>
                            </div>
                            <div class="summary-item">
                                <h4>Moyenne générale</h4>
                                <div class="summary-value overall-grade">
                                    <?= $count > 0 ? number_format($moyenne_generale, 2) : '—' ?>
                                </div>
                            </div>
                            <div class="summary-item">
                                <h4>Mention</h4>
                                <div class="summary-value" style="color: <?= $mention_color ?>;">
                                    <?= $mention ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($count > 0): ?>
                            <div style="text-align: center;">
                                <div style="font-size: 18px; color: var(--dark); margin-bottom: 20px;">
                                    <i class="fas fa-chart-pie"></i>
                                    Statistiques des notes
                                </div>
                                <div
                                    style="background: linear-gradient(90deg, 
                            var(--success) <?= ($moyenne_generale / 20) * 100 ?>%, 
                            var(--light) <?= ($moyenne_generale / 20) * 100 ?>%); height: 20px; border-radius: 10px; margin: 0 auto; max-width: 600px;">
                                </div>
                                <div
                                    style="display: flex; justify-content: space-between; max-width: 600px; margin: 10px auto 0; color: var(--gray); font-size: 14px;">
                                    <span>0</span>
                                    <span>10</span>
                                    <span>20</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="action-buttons">
                        <a href="imprimer.php?eleve=<?= $eleve_id ?>&trimestre=<?= $trimestre_id ?>&annee=<?= $annee_id ?>"
                            target="_blank" class="btn-print">
                            <i class="fas fa-print"></i>
                            Imprimer le bulletin
                        </a>
                        <button onclick="window.location.href=''" class="btn-back">
                            <i class="fas fa-redo"></i>
                            Nouvelle recherche
                        </button>
                    </div>
                </div>
            <?php elseif (isset($_POST['voir'])): ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Aucune donnée disponible</h3>
                    <p>Les notes ne sont pas encore saisies pour cette période</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <i class="fas fa-shield-alt"></i>
            <span>Système de Gestion Scolaire • © <?= date('Y') ?></span>
        </div>
    </div>

    <script>
        document.getElementById('classe_id').addEventListener('change', function() {
            const classeId = this.value;
            const eleveSelect = document.getElementById('eleve_id');

            if (!classeId) {
                eleveSelect.innerHTML = '<option value="">-- Choisissez d\'abord une classe --</option>';
                return;
            }

            eleveSelect.innerHTML = '<option value="">Chargement...</option>';
            eleveSelect.disabled = true;

            // Ajout d'un spinner visuel
            eleveSelect.style.backgroundImage =
                "url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"%2364748b\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><circle cx=\"12\" cy=\"12\" r=\"10\"/><path d=\"M12 6v6l4 2\"/></svg>')";
            eleveSelect.style.backgroundPosition = "right 18px center";
            eleveSelect.style.backgroundSize = "20px";

            fetch('../notes/get_eleves.php?classe_id=' + classeId)
                .then(res => res.json())
                .then(data => {
                    eleveSelect.innerHTML = '<option value="">-- Sélectionner un élève --</option>';
                    data.forEach(e => {
                        let opt = document.createElement('option');
                        opt.value = e.id;
                        opt.textContent = e.nom + ' ' + e.prenom;
                        eleveSelect.appendChild(opt);
                    });
                    eleveSelect.disabled = false;
                    eleveSelect.style.backgroundImage =
                        "url('data:image/svg+xml,%3Csvg xmlns=\"http://www.w3.org/2000/svg\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"%2364748b\"%3E%3Cpath stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19 9l-7 7-7-7\"%3E%3C/path%3E%3C/svg%3E')";
                })
                .catch(error => {
                    eleveSelect.innerHTML = '<option value="">Erreur de chargement</option>';
                    console.error('Error:', error);
                });
        });

        // Animation pour l'affichage du bulletin
        document.addEventListener('DOMContentLoaded', function() {
            const bulletinResult = document.getElementById('bulletinResult');
            if (bulletinResult) {
                bulletinResult.style.opacity = '0';
                setTimeout(() => {
                    bulletinResult.style.transition = 'opacity 0.6s ease';
                    bulletinResult.style.opacity = '1';
                }, 100);
            }
        });

        // Gestion des classes CSS pour les notes
        function getGradeClass(grade) {
            if (grade >= 16) return 'grade-excellent';
            if (grade >= 14) return 'grade-good';
            if (grade >= 10) return 'grade-average';
            return 'grade-poor';
        }
    </script>
</body>

</html>