<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['classe_id']) || empty($_GET['classe_id'])) {
    echo json_encode([]);
    exit;
}

$classe_id = (int) $_GET['classe_id'];

$stmt = $pdo->prepare("
    SELECT id, nom, prenom
    FROM eleves
    WHERE classe_id = ?
    ORDER BY nom, prenom
");
$stmt->execute([$classe_id]);

$eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($eleves);
