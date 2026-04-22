<?php
session_start();
require_once 'config/database.php';

$classe_id = (int)($_GET['classe_id'] ?? 0);

// Infos classe
$classe = $pdo->prepare("SELECT nom_classe FROM classes WHERE id=?");
$classe->execute([$classe_id]);
$classe = $classe->fetch();

// Matières enseignées dans la classe
$sql = "
SELECT DISTINCT m.id, m.nom_matiere
FROM matieres m
JOIN notes n ON n.matiere_id = m.id
JOIN eleves e ON e.id = n.eleve_id
WHERE e.classe_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$classe_id]);
$matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Matières</title>
    <style>
    body {
        font-family: Arial;
        background: #f4f6f8;
        padding: 30px
    }

    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px
    }

    .card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        cursor: pointer;
        text-align: center
    }

    .card:hover {
        background: #3498db;
        color: white
    }

    .popup {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, .6)
    }

    .popup-content {
        background: white;
        width: 500px;
        margin: 80px auto;
        padding: 25px;
        border-radius: 10px;
    }

    .close {
        float: right;
        cursor: pointer;
        font-weight: bold
    }
    </style>
</head>

<body>

    <h2>📘 <?= htmlspecialchars($classe['nom_classe']) ?></h2>

    <div class="grid">
        <?php foreach ($matieres as $m): ?>
        <div class="card" onclick="loadNotes(<?= $m['id'] ?>)">
            <?= htmlspecialchars($m['nom_matiere']) ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- POPUP -->
    <div class="popup" id="popup">
        <div class="popup-content">
            <span class="close" onclick="closePopup()">X</span>
            <div id="popup-body"></div>
        </div>
    </div>

    <script>
    function loadNotes(matiere_id) {
        fetch('notes_ajax.php?classe_id=<?= $classe_id ?>&matiere_id=' + matiere_id)
            .then(r => r.text())
            .then(html => {
                document.getElementById('popup-body').innerHTML = html;
                document.getElementById('popup').style.display = 'block';
            });
    }

    function closePopup() {
        document.getElementById('popup').style.display = 'none';
    }
    </script>

</body>

</html>