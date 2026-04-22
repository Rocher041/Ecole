<?php

date_default_timezone_set('Africa/Porto-Novo');



$host = 'localhost'; // ou 127.0.0.1
$dbname = 'gestion_ecole'; // ton nom de base
$username = 'root'; // ton user MySQL
$password = ''; // ton mot de passe MySQL (souvent vide sous XAMPP)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    // Définir les erreurs PDO sur Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
