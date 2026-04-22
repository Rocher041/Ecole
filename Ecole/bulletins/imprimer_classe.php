<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$classe_id = isset($_GET['classe_id']) ? (int)$_GET['classe_id'] : 0;
$trimestre_id = isset($_GET['trimestre_id']) ? (int)$_GET['trimestre_id'] : 0;
$annee_id = isset($_GET['annee_id']) ? (int)$_GET['annee_id'] : 0;

if (!$classe_id || !$trimestre_id || !$annee_id) {
    die("Paramètres manquants !");
}

// Récupérer la classe
$stmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
$stmt->execute([$classe_id]);
$classe = $stmt->fetch();

// Récupérer les élèves de la classe
$stmt = $pdo->prepare("SELECT * FROM eleves WHERE classe_id = ? ORDER BY nom, prenom");
$stmt->execute([$classe_id]);
$eleves = $stmt->fetchAll();

// Récupérer les infos du trimestre et année
$stmt = $pdo->prepare("SELECT * FROM trimestres WHERE id = ?");
$stmt->execute([$trimestre_id]);
$trimestre = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM annees_scolaires WHERE id = ?");
$stmt->execute([$annee_id]);
$annee = $stmt->fetch();

// Récupérer les matières
$matieres = $pdo->query("SELECT * FROM matieres ORDER BY nom_matiere")->fetchAll();

// Calcul des moyennes pour chaque élève
$bulletins_data = [];
$moyennes_eleves = [];

foreach ($eleves as $eleve) {
    $notes_eleve = [];
    $total_moyennes = 0;
    $count_moyennes = 0;
    $total_coeff = 0;
    $total_moy_coeff = 0;

    foreach ($matieres as $matiere) {
        $stmt = $pdo->prepare("
            SELECT type_note, note 
            FROM notes 
            WHERE eleve_id = ? AND matiere_id = ? AND trimestre_id = ? AND annee_id = ?
        ");
        $stmt->execute([$eleve['id'], $matiere['id'], $trimestre_id, $annee_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Calcul de la moyenne
        $interro = $notes['interro'] ?? null;
        $mini_dev = $notes['mini_dev'] ?? null;
        $dev_hebdo = $notes['dev_hebdo'] ?? null;
        $compo = $notes['compo'] ?? null;

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

        $coef = (int)$matiere['coefficient'];
        $moy_coeff = ($moy_matiere !== null) ? $moy_matiere * $coef : null;

        if ($moy_coeff !== null) {
            $total_moy_coeff += $moy_coeff;
            $total_coeff += $coef;
        }

        // Calcul du rang par matière
        $stmt_rang = $pdo->prepare("
            SELECT n.eleve_id,
                   AVG(n.note) AS moyenne
            FROM notes n
            WHERE n.matiere_id = ?
              AND n.trimestre_id = ?
              AND n.annee_id = ?
            GROUP BY n.eleve_id
            ORDER BY moyenne DESC
        ");
        $stmt_rang->execute([$matiere['id'], $trimestre_id, $annee_id]);
        $classe_moyennes = $stmt_rang->fetchAll(PDO::FETCH_KEY_PAIR);

        $rang_matiere = null;
        $i = 1;
        foreach ($classe_moyennes as $eid => $moy) {
            if ($eid == $eleve['id']) {
                $rang_matiere = $i;
                break;
            }
            $i++;
        }

        $notes_eleve[] = [
            'matiere_id' => $matiere['id'],
            'matiere' => $matiere['nom_matiere'],
            'coefficient' => $matiere['coefficient'],
            'interro' => $interro,
            'mini_dev' => $mini_dev,
            'dev_hebdo' => $dev_hebdo,
            'compo' => $compo,
            'moyenne' => $moy_matiere,
            'moyenne_coeff' => $moy_coeff,
            'rang' => $rang_matiere
        ];
    }

    $moyenne_generale = $count_moyennes > 0 ? $total_moyennes / $count_moyennes : null;
    $moyenne_generale_coeff = $total_coeff > 0 ? $total_moy_coeff / $total_coeff : null;

    $bulletins_data[$eleve['id']] = [
        'eleve' => $eleve,
        'notes' => $notes_eleve,
        'moyenne_generale' => $moyenne_generale,
        'moyenne_generale_coeff' => $moyenne_generale_coeff,
        'count_moyennes' => $count_moyennes,
        'total_coeff' => $total_coeff,
        'total_moy_coeff' => $total_moy_coeff
    ];

    if ($moyenne_generale !== null) {
        $moyennes_eleves[$eleve['id']] = $moyenne_generale;
    }
}

// Calcul du classement général
arsort($moyennes_eleves);
$classement = array_keys($moyennes_eleves);

foreach ($bulletins_data as $eleve_id => &$bulletin) {
    $position = array_search($eleve_id, $classement);
    $bulletin['rang'] = $position !== false ? $position + 1 : null;

    // Calcul de l'appréciation
    $appreciation = '';
    if ($bulletin['moyenne_generale'] !== null) {
        if ($bulletin['moyenne_generale'] >= 16) {
            $appreciation = 'EXCELLENT';
        } elseif ($bulletin['moyenne_generale'] >= 14) {
            $appreciation = 'TRÈS BIEN';
        } elseif ($bulletin['moyenne_generale'] >= 12) {
            $appreciation = 'BIEN';
        } elseif ($bulletin['moyenne_generale'] >= 10) {
            $appreciation = 'ASSEZ BIEN';
        } else {
            $appreciation = 'INSUFFISANT';
        }
    }
    $bulletin['appreciation'] = $appreciation;
}

// Statistiques de la classe
$valid_moyennes = array_filter(array_column($bulletins_data, 'moyenne_generale'));
$classe_max = !empty($valid_moyennes) ? max($valid_moyennes) : null;
$classe_min = !empty($valid_moyennes) ? min($valid_moyennes) : null;
$classe_avg = !empty($valid_moyennes) ? array_sum($valid_moyennes) / count($valid_moyennes) : null;

// Nom de l'établissement
$etablissement = "LYCEE POLYTECHNIQUE BLAISE PASCAL";
$ville = "Lot: 1298 Gbêdagba/ Sainte Rita/Cotonou";

function appreciation_matiere($moy)
{
    if ($moy === null) return '—';
    if ($moy >= 16) return 'Excellent';
    if ($moy >= 14) return 'Très bien';
    if ($moy >= 12) return 'Bien';
    if ($moy >= 10) return 'Passable';
    return 'Insuffisant';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Bulletins - <?= htmlspecialchars($classe['nom_classe']) ?> - Trimestre
        <?= htmlspecialchars($trimestre['nom']) ?></title>
    <style>
    /* RESET ET CONFIGURATION POUR L'IMPRESSION A4 */
    @page {
        size: A4 portrait;
        margin: 1cm;
        background-color: #D6E6F5;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Times New Roman', Times, serif;
        font-size: 10pt;
        line-height: 1.3;
        color: #000;
        width: 21cm;
        min-height: 29.7cm;
        margin: 0 auto;
        padding: 0.8cm;
    }

    /* PAGE BREAK */
    .page-break {
        page-break-before: always;
    }

    /* MASQUER POUR L'ÉCRAN */
    .no-print {
        display: none !important;
    }

    /* LOGO */
    .logo-container {
        position: absolute;
        top: 0.8cm;
        left: 0.8cm;
        width: 80px;
        height: 80px;
        z-index: 10;
    }

    .logo-img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        border-radius: 4px;
    }

    /* EN-TÊTE DU BULLETIN */
    .header {
        position: relative;
        margin-left: 90px;
        text-align: center;
        margin-bottom: 0.5cm;
        border-bottom: 1.5pt solid #000;
        padding-bottom: 0.1cm;
    }

    .school-name {
        font-size: 14pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.2cm;
    }

    .school-address {
        font-size: 9pt;
        margin-bottom: 0.2cm;
    }

    .document-title {
        font-size: 12pt;
        font-weight: bold;
        text-transform: uppercase;
        margin: 0.6cm 0 0.4cm 0;
        padding: 0.2cm;
        border: 1pt solid #000;
        background-color: #D6E6F5;
    }

    /* INFORMATIONS CLASSE */
    .class-info {
        margin-bottom: 0.3cm;
        border: 1pt solid #000;
        padding: 0.2cm;
        font-size: 9pt;
    }

    .class-info table {
        width: 100%;
        border-collapse: collapse;
    }

    .class-info td {
        padding: 0.1cm 0.2cm;
        vertical-align: top;
    }

    .class-info .label {
        font-weight: bold;
        width: 30%;
    }

    /* INFORMATIONS ÉLÈVE */
    .student-info {
        margin-bottom: 0.5cm;
        border: 1pt solid #000;
        padding: 0.2cm;
    }

    .student-info table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9pt;
    }

    .student-info td {
        padding: 0.1cm 0.1cm;
        vertical-align: top;
    }

    .student-info .label {
        font-weight: bold;
        width: 40%;
    }

    /* TABLEAU DES NOTES */
    .grades-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0.3cm;
        font-size: 8pt;
    }

    .grades-table th {
        border: 1pt solid #000;
        background-color: #D6E6F5;
        padding: 0.15cm;
        text-align: center;
        font-weight: bold;
        text-transform: uppercase;
    }

    .grades-table td {
        border: 1pt solid #000;
        padding: 0.15cm;
        text-align: center;
        vertical-align: middle;
    }

    .grades-table .subject {
        text-align: left;
        font-weight: bold;
        width: 25%;
    }

    .grades-table .average {
        font-weight: bold;
        background-color: #D6E6F5;
    }

    /* RÉSUMÉ ET STATISTIQUES */
    .summary-section {
        border: 1pt solid #000;
        padding: 0.2cm;
        margin-bottom: 0.3cm;
        font-size: 9pt;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.4cm;
        margin-bottom: 0.2cm;
    }

    .stat-box {
        border: 1pt solid #000;
        padding: 0.1cm;
        text-align: center;
    }

    .stat-value {
        font-size: 12pt;
        font-weight: bold;
        margin: 0.1cm 0;
    }

    .stat-label {
        font-size: 8pt;
        text-transform: uppercase;
    }

    .class-stats {
        border-top: 1pt solid #000;
        padding-top: 0.1cm;
        margin-top: 0.1cm;
    }

    .stats-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.1cm;
    }

    .stat-item {
        flex: 1;
        text-align: center;
        padding: 0.1cm;
    }

    /* APPRÉCIATION */
    .appreciation-box {
        border: 1pt solid #000;
        padding: 0.2cm;
        margin-bottom: 0.2cm;
        min-height: 1.5cm;
    }

    .appreciation-title {
        font-weight: bold;
        margin-bottom: 0.1cm;
        text-decoration: underline;
    }

    /* SIGNATURES */
    .signatures {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.8cm;
        margin-top: 0.2cm;
        font-size: 9pt;
    }

    .signature-box {
        text-align: center;
        padding-top: 0.3cm;
        position: relative;
    }

    .signature-box:before {
        content: '';
        position: absolute;
        top: 0;
        left: 15%;
        right: 15%;
        border-top: 1pt solid #000;
    }

    .signature-title {
        font-weight: bold;
        margin-bottom: 0.2cm;
    }

    .signature-name {
        font-size: 8pt;
        color: #666;
    }

    /* PIED DE PAGE */
    .footer {
        margin-top: 0.5cm;
        padding-top: 0.3cm;
        border-top: 1pt solid #000;
        text-align: center;
        font-size: 7pt;
        color: #666;
    }

    /* UTILITAIRES */
    .text-center {
        text-align: center;
    }

    .text-bold {
        font-weight: bold;
    }

    .text-uppercase {
        text-transform: uppercase;
    }

    .text-right {
        text-align: right;
    }

    .mb-1 {
        margin-bottom: 0.25cm;
    }

    /* POUR LES PETITS ÉCRANS */
    @media screen {
        body {
            background-color: #f0f0f0;
            padding: 1cm;
        }

        .print-container {
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            min-height: 29.7cm;
            position: relative;
        }
    }

    @media print {
        .logo-container {
            position: fixed;
            top: 0.8cm;
            left: 0.8cm;
        }
    }
    </style>
</head>

<body>
    <?php $eleve_count = 0; ?>
    <?php foreach ($bulletins_data as $eleve_id => $bulletin):
        $eleve = $bulletin['eleve'];
        $notes = $bulletin['notes'];
        $moyenne_generale = $bulletin['moyenne_generale'];
        $moyenne_generale_coeff = $bulletin['moyenne_generale_coeff'];
        $rang = $bulletin['rang'];
        $appreciation = $bulletin['appreciation'];
        $total_coeff = $bulletin['total_coeff'];
        $total_moy_coeff = $bulletin['total_moy_coeff'];

        $total_moyennes = 0;
        $count_moyennes = 0;
        foreach ($notes as $note) {
            if ($note['moyenne'] !== null) {
                $total_moyennes += $note['moyenne'];
                $count_moyennes++;
            }
        }
    ?>

    <!-- Saut de page sauf pour le premier élève -->
    <?php if ($eleve_count > 0): ?>
    <div class="page-break"></div>
    <?php endif; ?>

    <div class="print-container">
        <!-- LOGO -->
        <div class="logo-container">
            <?php
                $logo_path = "../uploads/logo.png";
                $default_logo = "data:image/svg+xml;base64," . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#f0f0f0"/><text x="50" y="50" font-family="Arial" font-size="12" text-anchor="middle" dy=".3em">LOGO</text></svg>');

                if (file_exists($logo_path)) {
                    echo '<img src="' . $logo_path . '" class="logo-img" alt="Logo Établissement">';
                } else {
                    echo '<img src="' . $default_logo . '" class="logo-img" alt="Logo par défaut">';
                }
                ?>
        </div>

        <!-- EN-TÊTE -->
        <div class="header">
            <div class="school-name"><?= htmlspecialchars($etablissement) ?></div>
            <div class="school-address"><?= htmlspecialchars($ville) ?> • Tél: +229 01 47 15 01 85 / 01 95 58 07 09 / 01
                40 41 78 60</div>
            <div class="school-address">Année scolaire <?= htmlspecialchars($annee['libelle']) ?></div>
            <div class="document-title">BULLETIN SCOLAIRE - TRIMESTRE <?= htmlspecialchars($trimestre['nom']) ?></div>
        </div>

        <!-- INFORMATIONS CLASSE (seulement sur le premier bulletin) -->
        <?php if ($eleve_count == 0): ?>
        <div class="class-info">
            <table>
                <tr>
                    <td class="label">Classe:</td>
                    <td><?= htmlspecialchars($classe['nom_classe']) ?></td>
                    <td class="label">Trimestre:</td>
                    <td><?= htmlspecialchars($trimestre['nom']) ?></td>
                </tr>
                <tr>
                    <td class="label">Année scolaire:</td>
                    <td><?= htmlspecialchars($annee['libelle']) ?></td>
                    <td class="label">Effectif:</td>
                    <td><?= count($eleves) ?> élèves</td>
                </tr>
            </table>
        </div>
        <?php endif; ?>

        <!-- INFORMATIONS ÉLÈVE -->
        <div class="student-info">
            <table>
                <tr>
                    <td class="label">Nom et Prénom:</td>
                    <td><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></td>
                    <td class="label">Sexe:</td>
                    <td><?= htmlspecialchars($eleve['sexe']) ?></td>
                </tr>
                <tr>
                    <td class="label">Date de naissance:</td>
                    <td><?= htmlspecialchars($eleve['date_naissance']) ?></td>
                    <td class="label">Lieu de naissance:</td>
                    <td><?= htmlspecialchars($eleve['lieu_naissance']) ?></td>
                </tr>
                <tr>
                    <td class="label">Classe:</td>
                    <td><?= htmlspecialchars($classe['nom_classe']) ?></td>
                    <td class="label">Matricule:</td>
                    <td><?= htmlspecialchars($eleve['matricule'] ?? '—') ?></td>
                </tr>
            </table>
        </div>

        <!-- TABLEAU DES NOTES -->
        <table class="grades-table">
            <thead>
                <tr>
                    <th>Matière</th>
                    <th>Coef</th>
                    <th>Interro</th>
                    <th>Mini Dev.</th>
                    <th>Dev. Hebdo.</th>
                    <th>Composition</th>
                    <th>Moyenne</th>
                    <th>Moy. Coeff.</th>
                    <th>Rang</th>
                    <th>Appréciation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notes as $note): ?>
                <tr>
                    <td class="subject"><?= htmlspecialchars($note['matiere']) ?></td>
                    <td><?= $note['coefficient'] ?></td>
                    <td><?= $note['interro'] !== null ? number_format($note['interro'], 1) : '—' ?></td>
                    <td><?= $note['mini_dev'] !== null ? number_format($note['mini_dev'], 1) : '—' ?></td>
                    <td><?= $note['dev_hebdo'] !== null ? number_format($note['dev_hebdo'], 1) : '—' ?></td>
                    <td><?= $note['compo'] !== null ? number_format($note['compo'], 1) : '—' ?></td>
                    <td class="text-bold"><?= $note['moyenne'] !== null ? number_format($note['moyenne'], 2) : '—' ?>
                    </td>
                    <td class="text-bold">
                        <?= $note['moyenne_coeff'] !== null ? number_format($note['moyenne_coeff'], 2) : '—' ?></td>
                    <td><?= $note['rang'] ?? '—' ?></td>
                    <td><?= appreciation_matiere($note['moyenne']) ?></td>
                </tr>
                <?php endforeach; ?>

                <!-- MOYENNE GÉNÉRALE COEFFICIÉE -->
                <?php if ($total_coeff > 0): ?>
                <tr>
                    <td colspan="8" class="text-bold text-right" style="text-align:right;padding-right:0.5cm;">
                        MOYENNE GÉNÉRALE COEFFICIÉE :
                    </td>
                    <td colspan="2" class="average text-bold">
                        <?= number_format($total_moy_coeff, 2) ?>
                    </td>
                </tr>
                <?php endif; ?>

                <!-- MOYENNE GÉNÉRALE -->
                <?php if ($count_moyennes > 0): ?>
                <tr>
                    <td colspan="8" class="text-bold text-right" style="text-align: right; padding-right: 0.5cm;">
                        MOYENNE GÉNÉRALE:
                    </td>
                    <td colspan="2" class="average text-bold">
                        <?= number_format($total_moyennes / $count_moyennes, 2) ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- RÉSUMÉ ET STATISTIQUES -->
        <div class="summary-section">
            <div class="summary-grid">
                <div class="stat-box">
                    <div class="stat-label">Rang</div>
                    <div class="stat-value"><?= $rang ?? '—' ?><sup>e</sup></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Moyenne Générale</div>
                    <div class="stat-value">
                        <?= $moyenne_generale !== null ? number_format($moyenne_generale, 2) : '—' ?>
                    </div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Moyenne Classe</div>
                    <div class="stat-value"><?= $classe_avg !== null ? number_format($classe_avg, 2) : '—' ?></div>
                </div>

                <div class="stat-box">
                    <div class="stat-label">Appréciation</div>
                    <div class="stat-value"><?= $appreciation ?></div>
                </div>
            </div>

            <div class="class-stats">
                <div class="stats-row">
                    <div class="stat-item">
                        <div class="text-bold">Moyenne max:
                            <?= $classe_max !== null ? number_format($classe_max, 2) : '—' ?></div>
                    </div>

                    <div class="stat-item">
                        <div class="text-bold">Moyenne min:
                            <?= $classe_min !== null ? number_format($classe_min, 2) : '—' ?></div>
                    </div>

                    <div class="stat-item">
                        <div class="text-bold">Moyenne classe:
                            <?= $classe_avg !== null ? number_format($classe_avg, 2) : '—' ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- APPRÉCIATION -->
        <div class="appreciation-box">
            <div class="appreciation-title">Appréciation générale</div>
            <div>
                <?php if ($moyenne_generale >= 16): ?>
                Élève brillant, travail sérieux et régulier.
                <?php elseif ($moyenne_generale >= 14): ?>
                Très bon travail, résultats satisfaisants.
                <?php elseif ($moyenne_generale >= 12): ?>
                Bon travail, peut encore progresser.
                <?php elseif ($moyenne_generale >= 10): ?>
                Résultats suffisants, efforts à maintenir.
                <?php elseif ($moyenne_generale !== null): ?>
                Résultats insuffisants, efforts à fournir.
                <?php else: ?>
                Non évalué
                <?php endif; ?>
            </div>
        </div>

        <!-- SIGNATURES -->
        <div class="signatures">
            <div class="signature-box">
                <div class="signature-title">Le Chef d'Établissement</div>
                <div class="signature-name">Nom et signature</div>
            </div>
        </div>

        <!-- PIED DE PAGE -->
        <div class="footer">
            Bulletin édité le <?= date('d/m/Y') ?> à <?= date('H:i') ?>
            • Réf:
            BUL<?= str_pad($eleve['id'], 4, '0', STR_PAD_LEFT) ?><?= $trimestre_id ?><?= substr($annee['libelle'], 2, 2) ?>
            • Document officiel - Ne pas reproduire
        </div>
    </div>

    <?php $eleve_count++; ?>
    <?php endforeach; ?>

    <!-- BOUTON D'IMPRESSION (visible seulement à l'écran) -->
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer;">
            Imprimer tous les bulletins (<?= count($bulletins_data) ?>)
        </button>
        <button onclick="window.history.back()"
            style="padding: 10px 20px; background: #666; color: white; border: none; cursor: pointer; margin-left: 10px;">
            Retour
        </button>
    </div>

    <script>
    // Impression automatique après chargement
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 500);
    });

    // Message après impression
    window.onafterprint = function() {
        alert('Impression terminée ! <?= count($bulletins_data) ?> bulletins ont été générés.');
    };
    </script>
</body>

</html>