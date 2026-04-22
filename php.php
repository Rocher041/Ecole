<?php
// Mot de passe saisi (ex: depuis un formulaire)
$motdepasse = "admin123";

// Hash du mot de passe
$motdepasse_hash = password_hash($motdepasse, PASSWORD_DEFAULT);

// Affichage du hash
echo $motdepasse_hash;