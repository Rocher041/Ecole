<?php
$base = 'ecole';

// Liste des dossiers à créer
$folders = [
    $base,
    "$base/config",
    "$base/includes",
    "$base/classes",
    "$base/eleves",
    "$base/matieres",
    "$base/notes",
    "$base/trimestres",
    "$base/annees",
    "$base/parametres",
    "$base/bulletins",
    "$base/assets",
    "$base/assets/css",
    "$base/assets/js",
    "$base/sql"
];

// Créer tous les dossiers
foreach($folders as $folder){
    if(!is_dir($folder)){
        mkdir($folder, 0777, true);
        echo "Dossier créé: $folder\n";
    }
}

// Fichiers vides à créer
$files = [
    "$base/index.php",
    "$base/config/database.php",
    "$base/includes/header.php",
    "$base/includes/footer.php",
    "$base/includes/fonctions.php",
    "$base/classes/ajouter.php",
    "$base/classes/liste.php",
    "$base/eleves/ajouter.php",
    "$base/eleves/liste.php",
    "$base/eleves/details.php",
    "$base/matieres/ajouter.php",
    "$base/matieres/liste.php",
    "$base/notes/saisir.php",
    "$base/notes/liste.php",
    "$base/trimestres/ajouter.php",
    "$base/trimestres/activer.php",
    "$base/trimestres/liste.php",
    "$base/annees/ajouter.php",
    "$base/annees/activer.php",
    "$base/parametres/index.php",
    "$base/bulletins/bulletin.php",
    "$base/bulletins/imprimer.php",
    "$base/assets/css/style.css",
    "$base/assets/js/script.js",
    "$base/sql/gestion_ecole.sql"
];

// Créer tous les fichiers vides
foreach($files as $file){
    if(!file_exists($file)){
        file_put_contents($file, "");
        echo "Fichier créé: $file\n";
    }
}

echo "\n✅ Arborescence complète créée avec succès !\n";
?>