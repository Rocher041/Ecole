<?php
session_start();



if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";
$message_type = "";


// Charger les classes
$classes = $pdo->query("SELECT id, nom_classe FROM classes ORDER BY nom_classe")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom_matiere = trim($_POST['nom_matiere']);
    $coefficient = (int)$_POST['coefficient'];
    $classes_ids = $_POST['classes'] ?? [];

    if (empty($nom_matiere)) {
        $message = "Veuillez saisir un nom de matière.";
        $message_type = "error";
    } elseif ($coefficient < 1 || $coefficient > 10) {
        $message = "Coefficient invalide.";
        $message_type = "error";
    } elseif (empty($classes_ids)) {
        $message = "Veuillez sélectionner au moins une classe.";
        $message_type = "error";
    } else {

        try {
            $pdo->beginTransaction();

            // Vérifier si la matière existe déjà
            $stmt = $pdo->prepare("SELECT id FROM matieres WHERE LOWER(nom_matiere)=LOWER(?)");
            $stmt->execute([$nom_matiere]);
            $matiere = $stmt->fetch();

            if ($matiere) {
                $matiere_id = $matiere['id'];
            } else {
                // Insérer nouvelle matière
                $stmt = $pdo->prepare("INSERT INTO matieres (nom_matiere) VALUES (?)");
                $stmt->execute([$nom_matiere]);
                $matiere_id = $pdo->lastInsertId();
            }

            // Lier matière ↔ classes avec coefficient
            $stmtCheck = $pdo->prepare("SELECT 1 FROM classe_matiere WHERE classe_id=? AND matiere_id=?");
            $stmtInsert = $pdo->prepare("INSERT INTO classe_matiere (classe_id, matiere_id, coefficient) VALUES (?, ?, ?)");
            $stmtUpdate = $pdo->prepare("UPDATE classe_matiere SET coefficient=? WHERE classe_id=? AND matiere_id=?");

            foreach ($classes_ids as $classe_id) {
                $stmtCheck->execute([$classe_id, $matiere_id]);
                if ($stmtCheck->rowCount() === 0) {
                    $stmtInsert->execute([$classe_id, $matiere_id, $coefficient]);
                } else {
                    $stmtUpdate->execute([$coefficient, $classe_id, $matiere_id]);
                }
            }

            $pdo->commit();

            $message = "Matière ajoutée/associée aux classes avec succès.";
            $message_type = "success";
            $_POST = [];
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Erreur lors de l'enregistrement : " . $e->getMessage();
            $message_type = "error";
        }
    }
}



?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une matière | Gestion Scolaire</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f0f4ff 0%, #e6f0ff 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        .container {
            width: 100%;
            max-width: 550px;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(123, 50, 148, 0.15);
            overflow: hidden;
            animation: slideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .header {
            background: linear-gradient(135deg, #7b3294 0%, #5a2470 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 200" opacity="0.1"><path d="M0,100 C150,200 350,0 500,100 C650,200 850,0 1000,100 L1000,200 L0,200 Z" fill="white"/></svg>');
            background-size: cover;
        }

        .header-content {
            position: relative;
            z-index: 2;
        }

        .header-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 36px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .header h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .content {
            padding: 40px;
        }

        .message {
            padding: 20px;
            border-radius: 16px;
            margin-bottom: 30px;
            text-align: center;
            font-size: 16px;
            font-weight: 500;
            animation: messageIn 0.4s ease-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        @keyframes messageIn {
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
            background: linear-gradient(to right, #d1fae5, #a7f3d0);
            color: #065f46;
            border: 2px solid #34d399;
        }

        .message.error {
            background: linear-gradient(to right, #fee2e2, #fecaca);
            color: #7f1d1d;
            border: 2px solid #f87171;
        }

        .form-container {
            background: #f8fafc;
            padding: 35px;
            border-radius: 20px;
            border: 2px solid #f1f5f9;
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        label {
            display: block;
            margin-bottom: 12px;
            color: #334155;
            font-weight: 600;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        label i {
            color: #7b3294;
            font-size: 18px;
            width: 24px;
        }

        .input-wrapper {
            position: relative;
        }

        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 18px 20px;
            padding-left: 50px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            color: #1e293b;
        }

        input[type="text"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: #7b3294;
            box-shadow: 0 0 0 4px rgba(123, 50, 148, 0.15);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #7b3294;
            font-size: 18px;
            z-index: 2;
        }

        .coefficient-display {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
        }

        .coeff-slider {
            flex: 1;
            --webkit-appearance: none;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(to right, #10b981, #f59e0b, #ef4444);
            outline: none;
        }

        .coeff-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: white;
            border: 3px solid #7b3294;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(123, 50, 148, 0.3);
            transition: all 0.3s ease;
        }

        .coeff-slider::-webkit-slider-thumb:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(123, 50, 148, 0.4);
        }

        .coeff-value {
            min-width: 60px;
            text-align: center;
            padding: 12px;
            background: #7b3294;
            color: white;
            border-radius: 12px;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 4px 12px rgba(123, 50, 148, 0.2);
        }

        .form-actions {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }

        .btn {
            flex: 1;
            padding: 20px;
            border: none;
            border-radius: 14px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
        }

        .btn-submit {
            background: linear-gradient(135deg, #7b3294, #5a2470);
            color: white;
            box-shadow: 0 8px 25px rgba(123, 50, 148, 0.3);
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #5a2470, #441a57);
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(123, 50, 148, 0.4);
        }

        .btn-submit:active {
            transform: translateY(-1px);
        }

        .btn-reset {
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #cbd5e1;
        }

        .btn-reset:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .btn-icon {
            font-size: 20px;
        }

        .quick-coeffs {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-top: 15px;
        }

        .coeff-btn {
            padding: 12px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .coeff-btn:hover {
            background: #7b3294;
            color: white;
            border-color: #7b3294;
            transform: translateY(-2px);
        }

        .coeff-btn.active {
            background: #7b3294;
            color: white;
            border-color: #7b3294;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            margin-top: 35px;
            padding-top: 30px;
            border-top: 2px solid #f1f5f9;
        }

        .nav-links a {
            flex: 1;
            padding: 18px;
            background: #f8fafc;
            color: #475569;
            text-decoration: none;
            border-radius: 14px;
            font-weight: 500;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
            border: 2px solid #e2e8f0;
        }

        .nav-links a:hover {
            background: #7b3294;
            color: white;
            border-color: #7b3294;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(123, 50, 148, 0.2);
        }

        .nav-links a:first-child:hover {
            background: #10b981;
            border-color: #10b981;
        }

        .coeff-label {
            display: inline-block;
            margin-left: 10px;
            padding: 4px 12px;
            background: #f0f9ff;
            color: #0369a1;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .container {
                border-radius: 20px;
            }

            .header,
            .content {
                padding: 30px 25px;
            }

            .header h2 {
                font-size: 26px;
            }

            .form-container {
                padding: 25px;
            }

            .form-actions {
                flex-direction: column;
            }

            .nav-links {
                flex-direction: column;
            }

            .quick-coeffs {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-book-medical"></i>
                </div>
                <h2>Nouvelle Matière</h2>
                <p>Ajoutez une nouvelle matière au catalogue d'enseignement</p>
            </div>
        </div>

        <div class="content">
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i
                        class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="" id="subjectForm">
                    <div class="form-group">
                        <label for="nom_matiere">
                            <i class="fas fa-book"></i> Nom de la matière
                            <span class="coeff-label">OBLIGATOIRE</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-pen input-icon"></i>
                            <input type="text" id="nom_matiere" name="nom_matiere"
                                value="<?php echo isset($_POST['nom_matiere']) ? htmlspecialchars($_POST['nom_matiere']) : ''; ?>"
                                placeholder="Ex: Mathématiques, Physique, Français..." required>
                        </div>
                        <small style="display: block; margin-top: 8px; color: #64748b; font-size: 13px;">
                            <i class="fas fa-info-circle"></i> Saisissez le nom complet de la matière
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="coefficient">
                            <i class="fas fa-balance-scale"></i> Coefficient
                            <span class="coeff-label">1-10</span>
                        </label>

                        <div class="input-wrapper">
                            <i class="fas fa-weight-hanging input-icon"></i>
                            <input type="number" id="coefficient" name="coefficient"
                                value="<?php echo isset($_POST['coefficient']) ? $_POST['coefficient'] : '1'; ?>"
                                min="1" max="10" required>
                        </div>

                        <div class="coefficient-display">
                            <input type="range" class="coeff-slider" min="1" max="10"
                                value="<?php echo isset($_POST['coefficient']) ? $_POST['coefficient'] : '1'; ?>">
                            <div class="coeff-value" id="coeffDisplay">
                                <?php echo isset($_POST['coefficient']) ? $_POST['coefficient'] : '1'; ?>
                            </div>
                        </div>

                        <div class="quick-coeffs">
                            <div class="coeff-btn" data-value="1">Faible (1)</div>
                            <div class="coeff-btn" data-value="3">Moyen (3)</div>
                            <div class="coeff-btn" data-value="5">Fort (5)</div>
                        </div>

                        <small style="display: block; margin-top: 12px; color: #64748b; font-size: 13px;">
                            <i class="fas fa-lightbulb"></i> Le coefficient détermine l'importance de la matière dans le
                            calcul des moyennes
                        </small>
                    </div>

                    <div class="form-group">
                        <label>
                            <i class="fas fa-school"></i> Classes concernées
                            <span class="coeff-label">OBLIGATOIRE</span>
                        </label>

                        <div class="input-wrapper">
                            <select name="classes[]" multiple required style="height:120px;">
                                <?php foreach ($classes as $classe): ?>
                                    <option value="<?= $classe['id']; ?>"
                                        <?= (isset($_POST['classes']) && in_array($classe['id'], $_POST['classes'])) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($classe['nom_classe']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <small style="margin-top:8px; display:block; color:#64748b;">
                            <i class="fas fa-info-circle"></i>
                            Maintenez CTRL pour sélectionner plusieurs classes
                        </small>
                    </div>


                    <div class="form-actions">
                        <button type="reset" class="btn btn-reset">
                            <i class="fas fa-redo btn-icon"></i> Réinitialiser
                        </button>
                        <button type="submit" name="submit" class="btn btn-submit">
                            <i class="fas fa-plus-circle btn-icon"></i> Ajouter la matière
                        </button>
                    </div>
                </form>
            </div>

            <div class="nav-links">
                <a href="liste.php">
                    <i class="fas fa-list"></i> Liste des matières
                </a>
                <a href="../accueil.php">
                    <i class="fas fa-home"></i> Tableau de bord
                </a>
            </div>
        </div>
    </div>

    <script>
        // Synchronisation slider/number input
        const coeffInput = document.getElementById('coefficient');
        const coeffSlider = document.querySelector('.coeff-slider');
        const coeffDisplay = document.getElementById('coeffDisplay');
        const quickCoeffs = document.querySelectorAll('.coeff-btn');

        function updateCoefficient(value) {
            coeffInput.value = value;
            coeffSlider.value = value;
            coeffDisplay.textContent = value;

            // Update quick buttons active state
            quickCoeffs.forEach(btn => {
                btn.classList.remove('active');
                if (parseInt(btn.dataset.value) === parseInt(value)) {
                    btn.classList.add('active');
                }
            });

            // Update color intensity
            let color;
            if (value <= 3) {
                color = '#10b981'; // Green
            } else if (value <= 6) {
                color = '#f59e0b'; // Orange
            } else {
                color = '#ef4444'; // Red
            }
            coeffDisplay.style.background = color;
        }

        coeffSlider.addEventListener('input', function() {
            updateCoefficient(this.value);
        });

        coeffInput.addEventListener('input', function() {
            let value = Math.min(10, Math.max(1, parseInt(this.value) || 1));
            this.value = value;
            updateCoefficient(value);
        });

        quickCoeffs.forEach(btn => {
            btn.addEventListener('click', function() {
                updateCoefficient(this.dataset.value);
            });
        });

        // Auto-focus on subject name
        document.getElementById('nom_matiere').focus();

        // Form validation
        document.getElementById('subjectForm').addEventListener('submit', function(e) {
            const subjectName = document.getElementById('nom_matiere').value.trim();
            const coeff = parseInt(document.getElementById('coefficient').value);

            if (!subjectName) {
                e.preventDefault();
                showAlert('Veuillez saisir un nom de matière', 'error');
                document.getElementById('nom_matiere').focus();
                return false;
            }

            if (coeff < 1 || coeff > 10) {
                e.preventDefault();
                showAlert('Le coefficient doit être entre 1 et 10', 'error');
                document.getElementById('coefficient').focus();
                return false;
            }

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin btn-icon"></i> Ajout en cours...';
            submitBtn.disabled = true;
        });

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `message ${type}`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;

            const contentDiv = document.querySelector('.content');
            const existingAlert = document.querySelector('.message');
            if (existingAlert) {
                existingAlert.remove();
            }

            contentDiv.insertBefore(alertDiv, contentDiv.firstChild);

            setTimeout(() => {
                alertDiv.style.opacity = '0';
                alertDiv.style.transform = 'translateY(-10px)';
                setTimeout(() => alertDiv.remove(), 300);
            }, 4000);
        }

        // Auto-hide existing message
        const existingMessage = document.querySelector('.message');
        if (existingMessage) {
            setTimeout(() => {
                existingMessage.style.opacity = '0';
                existingMessage.style.transform = 'translateY(-10px)';
                setTimeout(() => existingMessage.remove(), 300);
            }, 5000);
        }

        // Animation on load
        document.addEventListener('DOMContentLoaded', function() {
            const formGroups = document.querySelectorAll('.form-group');
            formGroups.forEach((group, index) => {
                group.style.animationDelay = `${index * 0.1}s`;
                group.style.animation = 'messageIn 0.5s ease-out forwards';
                group.style.opacity = '0';
            });
        });

        // Initialize coefficient display color
        updateCoefficient(coeffInput.value);
    </script>
</body>

</html>