<?php
require_once 'config/database.php';

$classe_id = (int)$_GET['classe_id'];
$matiere_id = (int)$_GET['matiere_id'];

$sql = "
SELECT e.nom, e.prenom, n.note
FROM eleves e
LEFT JOIN notes n 
    ON n.eleve_id = e.id AND n.matiere_id = ?
WHERE e.classe_id = ?
ORDER BY e.nom
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$matiere_id, $classe_id]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Notes des élèves</h3><ul>";
foreach ($notes as $n){
    echo "<li>{$n['nom']} {$n['prenom']} : <b>{$n['note']}</b></li>";
}
echo "</ul>";