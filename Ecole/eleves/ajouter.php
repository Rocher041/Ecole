<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";
$message_type = "";

// Récupérer les classes
$stmt = $pdo->query("SELECT * FROM classes ORDER BY nom_classe ASC");
$classes = $stmt->fetchAll();

if (isset($_POST['submit'])) {
    $matricule = trim($_POST['matricule']);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $classe_id = (int)$_POST['classe_id'];
    $date_naissance = $_POST['date_naissance'];
    $lieu_naissance = trim($_POST['lieu_naissance']);
    $sexe = $_POST['sexe'];

    // Validation
    $errors = [];
    
    if (empty($matricule)) $errors[] = "Le matricule est requis.";
    if (empty($nom)) $errors[] = "Le nom est requis.";
    if (empty($prenom)) $errors[] = "Le prénom est requis.";
    if (empty($classe_id)) $errors[] = "La classe est requise.";
    if (empty($date_naissance)) $errors[] = "La date de naissance est requise.";
    if (empty($lieu_naissance)) $errors[] = "Le lieu de naissance est requis.";
    if (empty($sexe)) $errors[] = "Le sexe est requis.";

    if (empty($errors)) {
        // Vérifier si l'élève existe déjà
        $stmt_check = $pdo->prepare("SELECT * FROM eleves WHERE matricule = ?");
        $stmt_check->execute([$matricule]);

        if ($stmt_check->rowCount() > 0) {
            $message = "Un élève avec ce matricule existe déjà.";
            $message_type = "error";
        } else {
            $stmt = $pdo->prepare("INSERT INTO eleves (matricule, nom, prenom, classe_id, date_naissance, lieu_naissance, sexe) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$matricule, $nom, $prenom, $classe_id, $date_naissance, $lieu_naissance, $sexe])) {
                $message = "✅ Élève ajouté avec succès !";
                $message_type = "success";
                // Réinitialiser les champs
                $_POST = array();
            } else {
                $message = "❌ Erreur lors de l'ajout.";
                $message_type = "error";
            }
        }
    } else {
        $message = implode("<br>", $errors);
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un élève | Gestion Scolaire</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        padding: 40px 20px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .container {
        width: 100%;
        max-width: 900px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .header {
        background: linear-gradient(to right, #4a6fa5, #2c5282);
        color: white;
        padding: 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .header::before {
        content: '👨‍🎓👩‍🎓';
        position: absolute;
        font-size: 120px;
        opacity: 0.1;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
    }

    .header h2 {
        font-size: 32px;
        font-weight: 600;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }

    .header p {
        font-size: 16px;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    .content {
        padding: 40px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }

    @media (max-width: 768px) {
        .content {
            grid-template-columns: 1fr;
            padding: 30px 20px;
        }
    }

    .message {
        grid-column: 1 / -1;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-size: 16px;
        text-align: center;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message.success {
        background: linear-gradient(to right, #d4edda, #c3e6cb);
        color: #155724;
        border-left: 5px solid #28a745;
    }

    .message.error {
        background: linear-gradient(to right, #f8d7da, #f5c6cb);
        color: #721c24;
        border-left: 5px solid #dc3545;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        color: #2d3748;
        font-weight: 600;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-group label i {
        color: #4a6fa5;
        width: 20px;
    }

    input[type="text"],
    input[type="date"],
    select {
        width: 100%;
        padding: 16px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: #f8fafc;
    }

    input[type="text"]:focus,
    input[type="date"]:focus,
    select:focus {
        outline: none;
        border-color: #4a6fa5;
        background: white;
        box-shadow: 0 0 0 4px rgba(74, 111, 165, 0.1);
    }

    select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%234a6fa5' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 20px center;
        background-size: 16px;
        padding-right: 50px;
    }

    .grid-2 {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    @media (max-width: 768px) {
        .grid-2 {
            grid-template-columns: 1fr;
        }
    }

    .form-actions {
        grid-column: 1 / -1;
        display: flex;
        gap: 20px;
        margin-top: 20px;
        padding-top: 30px;
        border-top: 2px solid #e2e8f0;
    }

    .btn {
        flex: 1;
        padding: 18px;
        border: none;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-primary {
        background: linear-gradient(to right, #4a6fa5, #2c5282);
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(to right, #3a5f95, #1c4272);
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(74, 111, 165, 0.3);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 2px solid #cbd5e1;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
        transform: translateY(-3px);
    }

    .btn-icon {
        font-size: 20px;
    }

    .links {
        grid-column: 1 / -1;
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid #e2e8f0;
    }

    .links a {
        color: #4a6fa5;
        text-decoration: none;
        font-weight: 500;
        padding: 12px 25px;
        border-radius: 10px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #f8fafc;
    }

    .links a:hover {
        background: #4a6fa5;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(74, 111, 165, 0.2);
    }

    .required::after {
        content: ' *';
        color: #e53e3e;
    }

    .student-icon {
        display: inline-block;
        width: 24px;
        height: 24px;
        background: #4a6fa5;
        color: white;
        border-radius: 6px;
        text-align: center;
        line-height: 24px;
        font-size: 14px;
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h2><span class="student-icon">👤</span> Ajouter un nouvel élève</h2>
            <p>Remplissez le formulaire ci-dessous pour inscrire un nouvel élève dans le système</p>
        </div>

        <div class="content">
            <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="matricule" class="required">
                        <i class="fas fa-id-card"></i> Matricule :
                    </label>
                    <input type="text" id="matricule" name="matricule"
                        value="<?php echo isset($_POST['matricule']) ? htmlspecialchars($_POST['matricule']) : ''; ?>"
                        placeholder="Ex: MAT2024001" required>
                </div>

                <div class="form-group">
                    <label for="nom" class="required">
                        <i class="fas fa-user"></i> Nom :
                    </label>
                    <input type="text" id="nom" name="nom"
                        value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>"
                        placeholder="Nom de famille" required>
                </div>

                <div class="form-group">
                    <label for="prenom" class="required">
                        <i class="fas fa-user-tag"></i> Prénom :
                    </label>
                    <input type="text" id="prenom" name="prenom"
                        value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>"
                        placeholder="Prénom" required>
                </div>

                <div class="form-group">
                    <label for="classe_id" class="required">
                        <i class="fas fa-school"></i> Classe :
                    </label>
                    <select id="classe_id" name="classe_id" required>
                        <option value="">-- Sélectionner une classe --</option>
                        <?php foreach ($classes as $classe): ?>
                        <option value="<?= $classe['id'] ?>"
                            <?php echo (isset($_POST['classe_id']) && $_POST['classe_id'] == $classe['id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($classe['nom_classe']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label for="date_naissance" class="required">
                            <i class="fas fa-calendar-alt"></i> Date de naissance :
                        </label>
                        <input type="date" id="date_naissance" name="date_naissance"
                            value="<?php echo isset($_POST['date_naissance']) ? $_POST['date_naissance'] : ''; ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label for="lieu_naissance" class="required">
                            <i class="fas fa-map-marker-alt"></i> Lieu de naissance :
                        </label>
                        <input type="text" id="lieu_naissance" name="lieu_naissance"
                            value="<?php echo isset($_POST['lieu_naissance']) ? htmlspecialchars($_POST['lieu_naissance']) : ''; ?>"
                            placeholder="Ville, Pays" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="sexe" class="required">
                        <i class="fas fa-venus-mars"></i> Sexe :
                    </label>
                    <select id="sexe" name="sexe" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="M"
                            <?php echo (isset($_POST['sexe']) && $_POST['sexe'] == 'M') ? 'selected' : ''; ?>>Masculin
                        </option>
                        <option value="F"
                            <?php echo (isset($_POST['sexe']) && $_POST['sexe'] == 'F') ? 'selected' : ''; ?>>Féminin
                        </option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="reset" class="btn btn-secondary">
                        <i class="fas fa-redo btn-icon"></i> Réinitialiser
                    </button>
                    <button type="submit" name="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus btn-icon"></i> Ajouter l'élève
                    </button>
                </div>
            </form>
        </div>

        <div class="links">
            <a href="liste.php">
                <i class="fas fa-list"></i> Voir tous les élèves
            </a>
            <a href="../accueil.php">
                <i class="fas fa-home"></i> Retour au tableau de bord
            </a>
        </div>
    </div>

    <script>
    // Validation en temps réel
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input[required], select[required]');

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.style.borderColor = '#e53e3e';
                    this.style.boxShadow = '0 0 0 4px rgba(229, 62, 62, 0.1)';
                } else {
                    this.style.borderColor = '#4a6fa5';
                    this.style.boxShadow = '0 0 0 4px rgba(74, 111, 165, 0.1)';
                }
            });

            input.addEventListener('input', function() {
                this.style.borderColor = '#4a6fa5';
            });
        });

        // Mettre le focus sur le premier champ
        document.getElementById('matricule').focus();

        // Disparition automatique des messages
        const messageDiv = document.querySelector('.message');
        if (messageDiv) {
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                messageDiv.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    if (messageDiv.parentNode) {
                        messageDiv.parentNode.removeChild(messageDiv);
                    }
                }, 300);
            }, 5000);
        }

        // Calculer l'âge à partir de la date
        const dateInput = document.getElementById('date_naissance');
        dateInput.addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            // Optionnel: afficher l'âge quelque part
            console.log('Âge calculé:', age);
        });
    });
    </script>
</body>

</html>