<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";
$message_type = ""; // Pour gérer le style du message (succès/erreur)

if (isset($_POST['submit'])) {
    $nom_classe = trim($_POST['nom_classe']);

    // Validation
    if (empty($nom_classe)) {
        $message = "Veuillez saisir un nom de classe.";
        $message_type = "error";
    } else {
        // Vérifier si la classe existe déjà
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE nom_classe = ?");
        $stmt->execute([$nom_classe]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Cette classe existe déjà.";
            $message_type = "error";
        } else {
            $stmt = $pdo->prepare("INSERT INTO classes (nom_classe) VALUES (?)");
            if ($stmt->execute([$nom_classe])) {
                $message = "Classe ajoutée avec succès !";
                $message_type = "success";
                $_POST['nom_classe'] = ''; // Vider le champ après succès
            } else {
                $message = "Erreur lors de l'ajout.";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une classe | Gestion Scolaire</title>
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
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .container {
        width: 100%;
        max-width: 500px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        padding: 40px;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    h2 {
        color: #2c3e50;
        text-align: center;
        margin-bottom: 30px;
        font-size: 28px;
        position: relative;
        padding-bottom: 15px;
    }

    h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(to right, #3498db, #2ecc71);
        border-radius: 2px;
    }

    .message {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        text-align: center;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .form-group {
        margin-bottom: 25px;
    }

    label {
        display: block;
        margin-bottom: 10px;
        color: #34495e;
        font-weight: 600;
        font-size: 16px;
    }

    input[type="text"] {
        width: 100%;
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s ease;
    }

    input[type="text"]:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }

    .btn {
        display: block;
        width: 100%;
        padding: 16px;
        background: linear-gradient(to right, #3498db, #2980b9);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 10px;
    }

    .btn:hover {
        background: linear-gradient(to right, #2980b9, #2573a7);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    }

    .btn:active {
        transform: translateY(0);
    }

    .links {
        margin-top: 30px;
        text-align: center;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .links a {
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
        padding: 12px;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .links a:hover {
        background-color: #f8f9fa;
        border-color: #3498db;
        padding: 12px 20px;
    }

    .links a:first-child {
        color: #2ecc71;
    }

    .links a:first-child:hover {
        border-color: #2ecc71;
    }

    @media (max-width: 600px) {
        .container {
            padding: 30px 20px;
        }

        h2 {
            font-size: 24px;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>➕ Ajouter une classe</h2>

        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nom_classe">Nom de la classe :</label>
                <input type="text" id="nom_classe" name="nom_classe"
                    value="<?php echo isset($_POST['nom_classe']) ? htmlspecialchars($_POST['nom_classe']) : ''; ?>"
                    placeholder="Ex: Terminale S1" required>
            </div>

            <button type="submit" name="submit" class="btn">
                ✅ Ajouter la classe
            </button>
        </form>

        <div class="links">
            <a href="liste.php">📋 Voir toutes les classes</a>
            <a href="../accueil.php">🏠 Retour au tableau de bord</a>
        </div>
    </div>

    <script>
    // Animation supplémentaire pour l'input
    document.getElementById('nom_classe').addEventListener('focus', function() {
        this.parentElement.style.transform = 'translateY(-5px)';
    });

    document.getElementById('nom_classe').addEventListener('blur', function() {
        this.parentElement.style.transform = 'translateY(0)';
    });

    // Disparition automatique du message après 5 secondes
    setTimeout(function() {
        const messageDiv = document.querySelector('.message');
        if (messageDiv) {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateY(-10px)';
            setTimeout(() => messageDiv.remove(), 300);
        }
    }, 5000);
    </script>
</body>

</html>