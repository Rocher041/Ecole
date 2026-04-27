<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$eleve_id = isset($_GET['eleve']) ? (int)$_GET['eleve'] : 0;
$trimestre_id = isset($_GET['trimestre']) ? (int)$_GET['trimestre'] : 0;
$annee_id = isset($_GET['annee']) ? (int)$_GET['annee'] : 0;

if (!$eleve_id || !$trimestre_id || !$annee_id) die("Paramètres manquants !");

// =====================
// FONCTIONS UTILITAIRES
// =====================

function formatTrimestre($nom_trimestre)
{
    $formatted = preg_replace('/(\d+)er/', '$1<sup>er</sup>', $nom_trimestre);
    $formatted = preg_replace('/(\d+)eme/', '$1<sup>ème</sup>', $formatted);
    $formatted = preg_replace('/(\d+)ème/', '$1<sup>ème</sup>', $formatted);
    return $formatted;
}

function formatSexe($code_sexe)
{
    return match (strtoupper($code_sexe)) {
        'M' => 'Masculin',
        'F' => 'Féminin',
        default => $code_sexe
    };
}

function formatRang($rang)
{
    if ($rang == 1) return $rang . "<sup>er</sup>";
    return $rang . "<sup>e</sup>";
}

function appreciation_matiere($moy)
{
    if ($moy === null) return '—';
    if ($moy >= 18) return 'Excellent';
    if ($moy >= 16) return 'Très Bien';
    if ($moy >= 14) return 'Bien';
    if ($moy >= 12) return 'Assez-Bien';
    if ($moy >= 10) return 'Passable';
    return 'Insuffisant';
}

// =====================
// CALCUL MOYENNE MATIÈRE
// =====================

function calculerMoyenneMatiere($notes_raw)
{
    $groupes = [
        'interro' => [],
        'mini_dev' => [],
        'dev_hebdo' => [],
        'compo' => []
    ];

    foreach ($notes_raw as $n) {
        $groupes[$n['type_note']][] = $n['note'];
    }

    $moy = function ($arr) {
        return count($arr) ? array_sum($arr) / count($arr) : null;
    };

    $moy_interro = $moy($groupes['interro']);
    $moy_mini_dev = $moy($groupes['mini_dev']);
    $moy_dev_hebdo = $moy($groupes['dev_hebdo']);
    $moy_compo = $moy($groupes['compo']);

    $moy_dev = $moy(array_filter([$moy_mini_dev, $moy_dev_hebdo]));
    $moy_int = $moy(array_filter([$moy_interro, $moy_dev]));

    return $moy(array_filter([$moy_int, $moy_compo]));
}

// =====================
// INFOS ÉLÈVE
// =====================

$stmt = $pdo->prepare("
    SELECT e.*, c.nom_classe 
    FROM eleves e
    JOIN classes c ON c.id = e.classe_id
    WHERE e.id = ?
");
$stmt->execute([$eleve_id]);
$eleve = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM trimestres WHERE id=?");
$stmt->execute([$trimestre_id]);
$trimestre = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM annees_scolaires WHERE id=?");
$stmt->execute([$annee_id]);
$annee = $stmt->fetch();

// =====================
// MATIÈRES
// =====================

$stmt = $pdo->prepare("
    SELECT m.id, m.nom_matiere, cm.coefficient
    FROM matieres m
    JOIN classe_matiere cm ON cm.matiere_id = m.id
    WHERE cm.classe_id = ?
    ORDER BY m.nom_matiere
");
$stmt->execute([$eleve['classe_id']]);
$matieres_toutes = $stmt->fetchAll();

$matieres = [];
$conduite = null;

foreach ($matieres_toutes as $m) {
    if (stripos($m['nom_matiere'], 'conduite') !== false || stripos($m['nom_matiere'], 'comportement') !== false) {
        $conduite = $m;
    } else {
        $matieres[] = $m;
    }
}

// =====================
// BULLETIN ÉLÈVE
// =====================

$bulletin = [];

foreach ($matieres as $m) {

    $stmt = $pdo->prepare("
        SELECT note, type_note 
        FROM notes 
        WHERE eleve_id=? AND matiere_id=? AND trimestre_id=? AND annee_id=?
    ");
    $stmt->execute([$eleve_id, $m['id'], $trimestre_id, $annee_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $moy = calculerMoyenneMatiere($notes);

    $bulletin[] = [
        'matiere_id' => $m['id'],
        'nom_matiere' => $m['nom_matiere'],
        'coefficient' => (int)$m['coefficient'] ?? 0,
        'moy_matiere' => $moy
    ];
}

// =====================
// CONDUITE
// =====================

$conduite_data = null;

if ($conduite) {
    $stmt = $pdo->prepare("
        SELECT note FROM notes 
        WHERE eleve_id=? AND matiere_id=? AND trimestre_id=? AND annee_id=?
    ");
    $stmt->execute([$eleve_id, $conduite['id'], $trimestre_id, $annee_id]);
    $notes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $moy = count($notes) ? array_sum($notes) / count($notes) : null;

    $conduite_data = [
        'nom_matiere' => $conduite['nom_matiere'],
        'coefficient' => (int)$conduite['coefficient'],
        'moy_matiere' => $moy
    ];
}

// =====================
// MOYENNE GÉNÉRALE CLASSE
// =====================

$eleves = $pdo->prepare("SELECT id FROM eleves WHERE classe_id=?");
$eleves->execute([$eleve['classe_id']]);
$eleves_ids = $eleves->fetchAll(PDO::FETCH_COLUMN);

$eleves_moyennes = [];

foreach ($eleves_ids as $eid) {

    $total = 0;
    $coeff_total = 0;

    foreach (array_merge($matieres, $conduite ? [$conduite] : []) as $m) {

        $stmt = $pdo->prepare("
            SELECT note, type_note 
            FROM notes 
            WHERE eleve_id=? AND matiere_id=? AND trimestre_id=? AND annee_id=?
        ");
        $stmt->execute([$eid, $m['id'], $trimestre_id, $annee_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $moy = ($conduite && $m['id'] == $conduite['id'])
            ? (count($notes) ? array_sum(array_column($notes, 'note')) / count($notes) : null)
            : calculerMoyenneMatiere($notes);

        if ($moy !== null) {
            $total += $moy * $m['coefficient'];
            $coeff_total += $m['coefficient'];
        }
    }

    $eleves_moyennes[$eid] = $coeff_total ? $total / $coeff_total : null;
}

// =====================
// CLASSEMENT
// =====================

arsort($eleves_moyennes);

$keys = array_keys($eleves_moyennes);
$pos = array_search($eleve_id, $keys);
$rang_eleve = $pos !== false ? $pos + 1 : null;

$valid = array_filter($eleves_moyennes);
$classe_max = max($valid);
$classe_min = min($valid);
$classe_avg = array_sum($valid) / count($valid);

// =====================
// MOYENNE ÉLÈVE
// =====================

$moyenne_generale = $eleves_moyennes[$eleve_id] ?? null;

$appreciation = match (true) {
    $moyenne_generale >= 18 => 'EXCELLENT',
    $moyenne_generale >= 16 => 'TRÈS BIEN',
    $moyenne_generale >= 14 => 'BIEN',
    $moyenne_generale >= 12 => 'ASSEZ-BIEN',
    $moyenne_generale >= 10 => 'PASSABLE',
    default => 'INSUFFISANT'
};

// =====================
// CONFIG ÉTABLISSEMENT
// =====================

$etablissement = "LYCEE POLYTECHNIQUE BLAISE PASCAL";
$ville = "Lot: 1298 Gbêdagba/ Sainte Rita/Cotonou";



$total_coeff_finale = 0;
$total_moy_coeff_finale = 0;

// recalcul propre depuis bulletin
foreach ($bulletin as $b) {
    if ($b['moy_matiere'] !== null) {
        $total_moy_coeff_finale += $b['moy_matiere'] * $b['coefficient'];
        $total_coeff_finale += $b['coefficient'];
    }
}

if ($conduite_data && $conduite_data['moy_matiere'] !== null) {
    $total_moy_coeff_finale += $conduite_data['moy_matiere'] * $conduite_data['coefficient'];
    $total_coeff_finale += $conduite_data['coefficient'];
}

$moyenne_generale_finale = $total_coeff_finale > 0
    ? $total_moy_coeff_finale / $total_coeff_finale
    : null;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Bulletin Scolaire - <?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></title>
    <style>
    /* RESET ET CONFIGURATION POUR L'IMPRESSION A4 */
    @page {
        size: A4 portrait;
        margin: 1cm;

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

    /* MASQUER LES ÉLÉMENTS POUR L'IMPRESSION */
    .no-print {
        display: none !important;
    }

    /* PAGE BREAK */
    .page-break {
        page-break-before: always;
    }

    /* EN-TÊTE DU BULLETIN */
    .header {
        text-align: center;
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
        padding: 0.2cm;
        border: 1pt solid #000;
        background-color: white;
    }

    /* Styles pour le superscript */
    .superscript {
        vertical-align: super;
        font-size: 0.7em;
    }

    /* INFORMATIONS ÉLÈVE */
    .student-info {
        margin-bottom: 0.2cm;
        border: 1pt solid #000;
        padding: 0.1cm;
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
        width: 10%;
        text-decoration: underline;

        white-space: nowrap;
        /* évite les retours à la ligne */
    }

    /* TABLEAU DES NOTES */
    .grades-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0.1cm;
        font-size: 8pt;
    }

    .grades-table th {
        border: 1pt solid #000;
        background-color: white;
        padding: 0.11cm;
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
        width: 30%;
    }

    .grades-table .average {
        font-weight: bold;
        background-color: white;
    }

    /* Style spécial pour la ligne Conduite - LES BORDURES COMME LES AUTRES */
    .conduite-row {
        background-color: white;
        font-weight: bold;
    }

    .conduite-row td {
        border: 1pt solid #000 !important;
        /* Même bordure que les autres lignes */
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
        margin: 0cm 0;
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
        font-size: 15px;

    }

    /* APPRÉCIATION */
    .appreciation-box {
        border: 1pt solid #000;
        padding: 0.2cm;
        margin-bottom: 0.2cm;
        min-height: 2cm;
    }

    .appreciation-title {
        font-weight: bold;
        margin-bottom: 0.1cm;
        text-decoration: underline;
        text-align: center;
    }

    /* SIGNATURES - MODIFICATION POUR LE DIRECTEUR A DROITE */
    .signatures {
        display: grid;
        grid-template-columns: 1fr 1fr;
        /* Deux colonnes égales */
        gap: 0.8cm;
        margin-top: 0.2cm;
        font-size: 9pt;
        position: relative;
    }

    /* Le Directeur sera placé dans la deuxième colonne (droite) */
    .directeur-box {
        grid-column: 2;
        /* Deuxième colonne */
        text-align: right;
        /* Alignement à droite */
        padding-right: 1cm;
        /* Décalage à droite */
    }

    /* Espace vide pour la première colonne (gauche) */
    .vide-box {
        grid-column: 1;
        /* Première colonne */
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
    }

    .signature-title {
        font-weight: bold;

    }

    .signature-name {
        font-size: 8pt;
        color: #666;

    }

    /* PIED DE PAGE */
    .footer {
        margin-top: 1cm;
        padding-top: 0.4cm;
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

    .mb-1 {
        margin-bottom: 0.25cm;
    }

    .mb-2 {
        margin-bottom: 0.5cm;
    }

    .mt-1 {
        margin-top: 0.25cm;
    }

    .mt-2 {
        margin-top: 0.5cm;
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

    /* Ajuster l'en-tête pour éviter la superposition */
    .header {
        position: relative;
        margin-left: 90px;
        text-align: center;
        margin-bottom: 0.8cm;
        /* border-bottom: 1.5pt solid #000; */
        padding-bottom: 0.4cm;
    }

    /* Pour l'impression */
    @media print {
        .logo-container {
            position: fixed;
            top: 0.8cm;
            left: 0.8cm;
        }

        /* S'assurer que la ligne Conduite a les mêmes bordures à l'impression */
        .conduite-row td {
            border: 1pt solid #000 !important;
        }
    }

    /* POUR LES PETITS ÉCRANS (PRÉVISUALISATION) */
    @media screen {
        body {
            background-color: #f0f0f0;
            padding: 1cm;
        }

        .print-container {
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            min-height: 29.7cm;
        }
    }
    </style>
</head>

<body>
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
                40 41 78 60 </div>
            <div class="school-address"><u>Année scolaire</u> <?= htmlspecialchars($annee['libelle']) ?></div>
            <div class="document-title">BULLETIN DU <?= formatTrimestre(htmlspecialchars($trimestre['nom'])) ?>
                TRIMESTRE</div>
        </div>

        <!-- INFORMATIONS ÉLÈVE -->
        <div class="student-info">
            <table>
                <tr>
                    <td class="label"><u>Nom et Prénoms</u>:</td>
                    <td><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></td>
                    <td class="label">Sexe:</td>
                    <td><?= formatSexe(htmlspecialchars($eleve['sexe'])) ?></td>
                </tr>
                <tr>
                    <td class="label">Classe:</td>
                    <td>
                        <?= preg_replace('/(\d+)(nde|eme|ème)/', '$1<sup>$2</sup>', htmlspecialchars($eleve['nom_classe'])) ?>
                    </td>
                    <td class="label">Trimestre:</td>
                    <td><?= formatTrimestre(htmlspecialchars($trimestre['nom'])) ?></td>
                </tr>
            </table>
        </div>

        <!-- TABLEAU DES NOTES -->
        <table class="grades-table">
            <thead>
                <tr>
                    <th>Matière</th>
                    <th>Coef</th>
                    <th>Moy Interro</th>
                    <th>Mini Dev.</th>
                    <th>Dev. Hebdo.</th>
                    <th>Composition</th>
                    <th>Moyenne</th>
                    <th>Moyenne Coeff</th>
                    <th>Rang</th>
                    <th>Appréciations</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_moy_coeff = 0;
                $total_coeff = 0;

                foreach ($bulletin as $b):
                    // Récupérer les notes détaillées pour l'affichage
                    $stmt = $pdo->prepare("
                        SELECT note, type_note 
                        FROM notes 
                        WHERE eleve_id=? AND matiere_id=? AND trimestre_id=? AND annee_id=?
                    ");
                    $stmt->execute([$eleve_id, $b['matiere_id'], $trimestre_id, $annee_id]);
                    $notes_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    $interros = $mini_devs = $dev_hebdos = $compos = [];
                    foreach ($notes_raw as $n) {
                        if ($n['type_note'] === 'interro') $interros[] = $n['note'];
                        if ($n['type_note'] === 'mini_dev') $mini_devs[] = $n['note'];
                        if ($n['type_note'] === 'dev_hebdo') $dev_hebdos[] = $n['note'];
                        if ($n['type_note'] === 'compo') $compos[] = $n['note'];
                    }

                    $moy_interro = count($interros) ? array_sum($interros) / count($interros) : null;
                    $moy_mini_dev = count($mini_devs) ? array_sum($mini_devs) / count($mini_devs) : null;
                    $moy_dev_hebdo = count($dev_hebdos) ? array_sum($dev_hebdos) / count($dev_hebdos) : null;
                    $moy_compo = count($compos) ? array_sum($compos) / count($compos) : null;

                    $dev_notes = array_filter([$moy_mini_dev, $moy_dev_hebdo], fn($v) => $v !== null);
                    $moy_dev = count($dev_notes) ? array_sum($dev_notes) / count($dev_notes) : null;

                    $int_notes = array_filter([$moy_interro, $moy_dev], fn($v) => $v !== null);
                    $moy_int = count($int_notes) ? array_sum($int_notes) / count($int_notes) : null;

                    $final_notes = array_filter([$moy_int, $moy_compo], fn($v) => $v !== null);
                    $moy_matiere = count($final_notes) ? array_sum($final_notes) / count($final_notes) : null;

                    $coef = (int)$b['coefficient'];
                    $moy_coeff = ($moy_matiere !== null) ? $moy_matiere * $coef : null;

                    // Totaux pour moyenne générale pondérée (sans conduite)
                    if ($moy_coeff !== null) {
                        $total_moy_coeff += $moy_coeff;
                        $total_coeff += $coef;
                    }

                    // ===== RANG PAR MATIÈRE (CORRECT) =====
                    $classe_moyennes = [];

                    foreach ($eleves_ids as $eid) {

                        $stmt = $pdo->prepare("
        SELECT note, type_note 
        FROM notes 
        WHERE eleve_id=? AND matiere_id=? AND trimestre_id=? AND annee_id=?
    ");
                        $stmt->execute([$eid, $b['matiere_id'], $trimestre_id, $annee_id]);
                        $notes_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        $moy = calculerMoyenneMatiere($notes_raw);

                        if ($moy !== null) {
                            $classe_moyennes[$eid] = $moy;
                        }
                    }

                    // Trier du meilleur au plus faible
                    arsort($classe_moyennes);

                    // Trouver le rang de l'élève
                    $rang = null;
                    $i = 1;

                    foreach ($classe_moyennes as $eid => $moy) {
                        if ($eid == $eleve_id) {
                            $rang = $i;
                            break;
                        }
                        $i++;
                    }
                ?>
                <tr>
                    <td class="subject"><?= htmlspecialchars($b['nom_matiere']) ?></td>
                    <td><?= $b['coefficient'] ?></td>
                    <td><?= $moy_interro !== null ? number_format($moy_interro, 1) : '—' ?></td>
                    <td><?= $moy_mini_dev !== null ? number_format($moy_mini_dev, 1) : '—' ?></td>
                    <td><?= $moy_dev_hebdo !== null ? number_format($moy_dev_hebdo, 1) : '—' ?></td>
                    <td><?= $moy_compo !== null ? number_format($moy_compo, 1) : '—' ?></td>
                    <td class="text-bold"><?= $moy_matiere !== null ? number_format($moy_matiere, 2) : '—' ?></td>
                    <td class="text-bold">
                        <?= $moy_coeff !== null ? number_format($moy_coeff, 2) : '—' ?>
                    </td>
                    <td><?= $rang !== null ? formatRang($rang) : '—' ?></td>
                    <td>
                        <?= appreciation_matiere($moy_matiere) ?>
                    </td>
                </tr>
                <?php endforeach; ?>

                <!-- LIGNE CONDUITE (tout en bas) -->
                <?php if ($conduite_data):
                    $moy_conduite = $conduite_data['moy_matiere'];
                    $coef_conduite = $conduite_data['coefficient'];
                    $moy_coeff_conduite = ($moy_conduite !== null) ? $moy_conduite * $coef_conduite : null;
                ?>
                <tr class="conduite-row">
                    <td class="subject"><?= htmlspecialchars($conduite_data['nom_matiere']) ?></td>
                    <td><?= $conduite_data['coefficient'] ?></td>
                    <!-- Fusion des 4 colonnes vides (Moy Interro, Mini Dev, Dev Hebdo, Composition) -->
                    <td colspan="4"></td>
                    <td class="text-bold"><?= $moy_conduite !== null ? number_format($moy_conduite, 2) : '—' ?></td>
                    <td class="text-bold">
                        <?= $moy_coeff_conduite !== null ? number_format($moy_coeff_conduite, 2) : '—' ?>
                    </td>
                    <td><?= formatRang(1) ?></td>
                    <td>
                        <?= appreciation_matiere($moy_conduite) ?>
                    </td>
                </tr>
                <?php endif; ?>

                <!-- LIGNE MOYENNE GÉNÉRALE AVEC CONDUITE -->
                <?php if ($total_coeff_finale > 0): ?>
                <tr>
                    <td colspan="9" class="text-bold text-right" style="text-align:right;padding-right:0.5cm;">
                        MOYENNE GÉNÉRALE:
                    </td>
                    <td class="average text-bold">
                        <?= number_format($moyenne_generale_finale, 2) ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- RÉSUMÉ ET STATISTIQUES -->
        <div class="summary-section">
            <div class="summary-grid">
                <div class="stat-box">
                    <div class="stat-label">Moyenne Générale</div>
                    <div class="stat-value">
                        <?= $moyenne_generale_finale !== null ? number_format($moyenne_generale_finale, 2) : '—' ?>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Rang</div>
                    <div class="stat-value"><?= formatRang($rang_eleve) ?></div>
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
                        <div class="text-bold">Plus forte Moyenne:
                            <?= $classe_max !== null ? number_format($classe_max, 2) : '—' ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="text-bold">Plus faible Moyenne:
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
                <!-- L'appréciation sera ajoutée ici -->
            </div>
        </div>

        <!-- SIGNATURES - MODIFIÉ POUR LE DIRECTEUR À DROITE -->
        <div class="signatures">
            <!-- Colonne gauche vide -->
            <div class="vide-box">
                <!-- Vide, laissé pour l'alignement -->
            </div>

            <!-- Colonne droite pour le Directeur -->
            <div class="signature-box directeur-box">
                <div class="signature-title">Le Directeur</div>
                <pre>




                </pre>
                <div class="signature-name">ADOUGAN Antoine Marie</div>
                <div class="signature-name">Ingénieur de Conception en Génie Civil</div>
            </div>
        </div>

        <!-- PIED DE PAGE -->
        <div class="footer">
            Bulletin édité le <?= date('d/m/Y') ?> à <?= date('H:i') ?>
            • Réf:
            BUL<?= str_pad($eleve_id, 4, '0', STR_PAD_LEFT) ?><?= $trimestre_id ?><?= substr($annee['libelle'], 2, 2) ?>
            • Document officiel - Ne pas reproduire
        </div>
    </div>

    <!-- BOUTON D'IMPRESSION (visible seulement à l'écran) -->
    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <button onclick="window.print()"
            style="padding: 10px 20px; background: #333; color: white; border: none; cursor: pointer;">
            Imprimer le bulletin
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

    // Retour à la page précédente après impression
    window.onafterprint = function() {
        // Optionnel: retour automatique après impression
        // window.history.back();
    };
    </script>
</body>

</html>