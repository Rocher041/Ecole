<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: liste.php");
    exit;
}

$id = (int)$_GET['id'];

// Récupérer les infos de l'élève
$stmt = $pdo->prepare("
    SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, e.lieu_naissance, e.sexe, c.nom_classe
    FROM eleves e
    JOIN classes c ON e.classe_id = c.id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$eleve = $stmt->fetch();

if (!$eleve) {
    die("Élève introuvable !");
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'élève - Système de Gestion Scolaire</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 1000px;
        margin: 30px auto;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .header {
        background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        color: white;
        padding: 25px 30px;
        text-align: center;
        position: relative;
    }

    .header h2 {
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .header p {
        opacity: 0.9;
        font-size: 16px;
    }

    .badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: #e74c3c;
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: bold;
    }

    .content {
        padding: 35px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    .info-section {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        border-left: 5px solid #3498db;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
    }

    .info-section:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    .info-section h3 {
        color: #2c3e50;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #ecf0f1;
        font-size: 20px;
    }

    .info-item {
        margin-bottom: 15px;
        display: flex;
        align-items: center;
    }

    .info-item:last-child {
        margin-bottom: 0;
    }

    .info-label {
        font-weight: 600;
        color: #34495e;
        width: 150px;
        font-size: 15px;
    }

    .info-value {
        color: #2c3e50;
        background: white;
        padding: 10px 15px;
        border-radius: 6px;
        flex: 1;
        border: 1px solid #e0e0e0;
        font-size: 15px;
        min-height: 40px;
        display: flex;
        align-items: center;
    }

    .avatar-section {
        text-align: center;
        padding: 25px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        margin-bottom: 30px;
    }

    .avatar {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        border-radius: 50%;
        margin: 0 auto 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 40px;
        color: white;
        font-weight: bold;
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    }

    .actions {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid #ecf0f1;
    }

    .btn {
        padding: 12px 30px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: #3498db;
        color: white;
        border: 2px solid #3498db;
    }

    .btn-primary:hover {
        background: #2980b9;
        border-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    }

    .btn-secondary {
        background: #ecf0f1;
        color: #2c3e50;
        border: 2px solid #bdc3c7;
    }

    .btn-secondary:hover {
        background: #d5dbdb;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(189, 195, 199, 0.3);
    }

    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }

        .actions {
            flex-direction: column;
            align-items: center;
        }

        .btn {
            width: 100%;
            max-width: 300px;
            justify-content: center;
        }

        .info-item {
            flex-direction: column;
            align-items: flex-start;
        }

        .info-label {
            width: 100%;
            margin-bottom: 5px;
        }
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h2><i class="fas fa-user-graduate"></i> Détails de l'élève</h2>
            <p>Fiche complète des informations personnelles</p>
            <div class="badge">ID: <?= $eleve['id'] ?></div>
        </div>

        <div class="content">
            <div class="avatar-section">
                <div class="avatar">
                    <?= strtoupper(substr($eleve['prenom'], 0, 1) . substr($eleve['nom'], 0, 1)) ?>
                </div>
                <h3><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h3>
                <p style="color: #7f8c8d; margin-top: 5px;"><?= htmlspecialchars($eleve['nom_classe']) ?></p>
            </div>

            <div class="info-grid">
                <div class="info-section">
                    <h3><i class="fas fa-id-card"></i> Informations personnelles</h3>
                    <div class="info-item">
                        <span class="info-label">Matricule</span>
                        <span class="info-value"><?= htmlspecialchars($eleve['matricule']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nom complet</span>
                        <span class="info-value"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date de naissance</span>
                        <span class="info-value"><?= htmlspecialchars($eleve['date_naissance']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Lieu de naissance</span>
                        <span class="info-value"><?= htmlspecialchars($eleve['lieu_naissance']) ?></span>
                    </div>
                </div>

                <div class="info-section">
                    <h3><i class="fas fa-school"></i> Informations scolaires</h3>
                    <div class="info-item">
                        <span class="info-label">Sexe</span>
                        <span class="info-value"><?= htmlspecialchars($eleve['sexe']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Classe</span>
                        <span class="info-value"><?= htmlspecialchars($eleve['nom_classe']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Statut</span>
                        <span class="info-value" style="color: #27ae60; font-weight: bold;">
                            <i class="fas fa-circle" style="font-size: 10px;"></i> Actif
                        </span>
                    </div>
                </div>
            </div>

            <div class="actions">
                <a href="liste.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
                <a href="../accueil.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Tableau de bord
                </a>
            </div>
        </div>
    </div>

    <script>
    // Animation pour les sections d'information
    document.addEventListener('DOMContentLoaded', function() {
        const sections = document.querySelectorAll('.info-section');
        sections.forEach((section, index) => {
            setTimeout(() => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

                setTimeout(() => {
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, 100);
            }, index * 100);
        });
    });
    </script>
</body>

</html>