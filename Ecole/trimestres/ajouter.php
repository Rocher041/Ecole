<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";
$message_type = "";

// Récupérer les années
$stmt = $pdo->query("SELECT * FROM annees_scolaires ORDER BY libelle DESC");
$annees = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim($_POST['nom']);
    $ordre = (int)$_POST['ordre'];
    $annee_id = (int)$_POST['annee_id'];

    // Validation
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = "Le nom du trimestre est requis.";
    }
    
    if ($ordre < 1 || $ordre > 4) {
        $errors[] = "L'ordre doit être compris entre 1 et 4.";
    }
    
    if (empty($annee_id)) {
        $errors[] = "L'année scolaire est requise.";
    }
    
    if (empty($errors)) {
        // Vérifier si le trimestre existe déjà pour cette année
        $stmt_check = $pdo->prepare("SELECT * FROM trimestres WHERE LOWER(nom) = LOWER(?) AND annee_id = ?");
        $stmt_check->execute([$nom, $annee_id]);
        
        if ($stmt_check->rowCount() > 0) {
            $message = "❌ Ce trimestre existe déjà pour cette année.";
            $message_type = "error";
        } else {
            // Vérifier si l'ordre existe déjà pour cette année
            $stmt_check_order = $pdo->prepare("SELECT * FROM trimestres WHERE ordre = ? AND annee_id = ?");
            $stmt_check_order->execute([$ordre, $annee_id]);
            
            if ($stmt_check_order->rowCount() > 0) {
                $message = "❌ Un trimestre avec cet ordre existe déjà pour cette année.";
                $message_type = "error";
            } else {
                $stmt = $pdo->prepare("INSERT INTO trimestres (nom, ordre, annee_id, actif) VALUES (?, ?, ?, 0)");
                if ($stmt->execute([$nom, $ordre, $annee_id])) {
                    $message = "✅ Trimestre ajouté avec succès !";
                    $message_type = "success";
                    // Réinitialiser les champs
                    $_POST['nom'] = '';
                    $_POST['ordre'] = '';
                } else {
                    $message = "❌ Erreur lors de l'ajout.";
                    $message_type = "error";
                }
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
    <title>Ajouter un trimestre | Gestion Scolaire</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 30px 20px;
    }

    .container {
        width: 100%;
        max-width: 600px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 60px rgba(245, 158, 11, 0.15);
        overflow: hidden;
        animation: slideIn 0.6s cubic-bezier(0.16, 1, 0.3, 1);
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
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
        max-width: 500px;
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
        color: #f59e0b;
        font-size: 18px;
        width: 24px;
    }

    .required::after {
        content: ' *';
        color: #ef4444;
        font-weight: bold;
    }

    .input-wrapper {
        position: relative;
    }

    select,
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

    select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23f59e0b' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 20px center;
        background-size: 16px;
        padding-right: 50px;
    }

    select:focus,
    input:focus {
        outline: none;
        border-color: #f59e0b;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.15);
        transform: translateY(-2px);
    }

    .input-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #f59e0b;
        font-size: 18px;
        z-index: 2;
    }

    .order-selector {
        display: flex;
        gap: 15px;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .order-option {
        flex: 1;
        min-width: 120px;
        padding: 20px;
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 14px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .order-option:hover {
        background: #fef3c7;
        border-color: #f59e0b;
        transform: translateY(-3px);
    }

    .order-option.active {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        border-color: #f59e0b;
        color: white;
    }

    .order-number {
        font-size: 28px;
        font-weight: 800;
        line-height: 1;
    }

    .order-label {
        font-size: 14px;
        font-weight: 600;
    }

    .order-option.active .order-number {
        color: white;
    }

    .year-info {
        margin-top: 15px;
        padding: 20px;
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-radius: 14px;
        border: 2px dashed #f59e0b;
        display: none;
    }

    .year-info h4 {
        color: #92400e;
        margin-bottom: 10px;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .year-info p {
        color: #92400e;
        font-size: 14px;
        line-height: 1.6;
    }

    .year-info small {
        display: block;
        margin-top: 10px;
        color: #92400e;
        opacity: 0.8;
        font-size: 12px;
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
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
    }

    .btn-submit:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
        transform: translateY(-4px);
        box-shadow: 0 15px 35px rgba(245, 158, 11, 0.4);
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
        background: #f59e0b;
        color: white;
        border-color: #f59e0b;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(245, 158, 11, 0.2);
    }

    .nav-links a:first-child:hover {
        background: #10b981;
        border-color: #10b981;
    }

    .hint-text {
        display: block;
        margin-top: 8px;
        color: #64748b;
        font-size: 13px;
        font-style: italic;
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

        .order-selector {
            flex-direction: column;
        }

        .order-option {
            min-width: 100%;
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
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h2>Nouveau Trimestre</h2>
                <p>Créez un nouveau trimestre pour organiser l'année scolaire</p>
            </div>
        </div>

        <div class="content">
            <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i
                    class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="" id="trimesterForm">
                    <div class="form-group">
                        <label for="annee_id" class="required">
                            <i class="fas fa-calendar-star"></i> Année scolaire
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-school input-icon"></i>
                            <select name="annee_id" id="annee_id" required>
                                <option value="">-- Sélectionner une année --</option>
                                <?php foreach ($annees as $annee): ?>
                                <option value="<?= $annee['id'] ?>"
                                    <?php echo isset($_POST['annee_id']) && $_POST['annee_id'] == $annee['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($annee['libelle']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <span class="hint-text">L'année scolaire à laquelle ce trimestre appartient</span>
                        <div id="yearInfo" class="year-info">
                            <!-- Rempli dynamiquement avec les trimestres existants -->
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="nom" class="required">
                            <i class="fas fa-tag"></i> Nom du trimestre
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-pen input-icon"></i>
                            <input type="text" id="nom" name="nom"
                                value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>"
                                placeholder="Ex: Trimestre 1, Semestre 1, Période d'examen..." required>
                        </div>
                        <span class="hint-text">Un nom clair pour identifier ce trimestre</span>
                    </div>

                    <div class="form-group">
                        <label class="required">
                            <i class="fas fa-sort-numeric-down"></i> Ordre du trimestre
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-list-ol input-icon"></i>
                            <input type="number" id="ordre" name="ordre"
                                value="<?php echo isset($_POST['ordre']) ? $_POST['ordre'] : '1'; ?>" min="1" max="4"
                                required>
                        </div>

                        <div class="order-selector">
                            <div class="order-option" data-order="1">
                                <div class="order-number">1</div>
                                <div class="order-label">Premier</div>
                                <small>Sep - Nov</small>
                            </div>
                            <div class="order-option" data-order="2">
                                <div class="order-number">2</div>
                                <div class="order-label">Deuxième</div>
                                <small>Dec - Fév</small>
                            </div>
                            <div class="order-option" data-order="3">
                                <div class="order-number">3</div>
                                <div class="order-label">Troisième</div>
                                <small>Mar - Mai</small>
                            </div>
                            <div class="order-option" data-order="4">
                                <div class="order-number">4</div>
                                <div class="order-label">Quatrième*</div>
                                <small>Juin - Juil</small>
                            </div>
                        </div>
                        <span class="hint-text">*Optionnel selon le système éducatif</span>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn btn-reset">
                            <i class="fas fa-redo btn-icon"></i> Réinitialiser
                        </button>
                        <button type="submit" name="submit" class="btn btn-submit">
                            <i class="fas fa-plus-circle btn-icon"></i> Créer le trimestre
                        </button>
                    </div>
                </form>
            </div>

            <div class="nav-links">
                <a href="liste.php">
                    <i class="fas fa-list"></i> Liste des trimestres
                </a>
                <a href="../accueil.php">
                    <i class="fas fa-home"></i> Tableau de bord
                </a>
            </div>
        </div>
    </div>

    <script>
    // Sélection visuelle de l'ordre
    const orderOptions = document.querySelectorAll('.order-option');
    const orderInput = document.getElementById('ordre');

    orderOptions.forEach(option => {
        option.addEventListener('click', function() {
            orderOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            orderInput.value = this.dataset.order;
        });
    });

    // Initialiser la sélection
    document.addEventListener('DOMContentLoaded', function() {
        const currentOrder = orderInput.value;
        orderOptions.forEach(option => {
            if (option.dataset.order === currentOrder) {
                option.classList.add('active');
            }
        });

        // Mettre à jour l'info de l'année sélectionnée
        updateYearInfo();
    });

    // Mise à jour des informations sur l'année sélectionnée
    const anneeSelect = document.getElementById('annee_id');
    const yearInfo = document.getElementById('yearInfo');

    anneeSelect.addEventListener('change', function() {
        updateYearInfo();
    });

    function updateYearInfo() {
        const anneeId = anneeSelect.value;

        if (!anneeId) {
            yearInfo.style.display = 'none';
            return;
        }

        // Récupérer les trimestres existants pour cette année
        fetch(`get_trimestres.php?annee_id=${anneeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    yearInfo.innerHTML = `
                            <h4><i class="fas fa-info-circle"></i> Aucun trimestre existant</h4>
                            <p>Cette année scolaire ne contient aucun trimestre pour le moment.</p>
                            <small>Vous allez créer le premier trimestre.</small>
                        `;
                } else {
                    let html = `<h4><i class="fas fa-calendar-check"></i> Trimestres existants</h4>`;
                    html += `<p>Cette année contient déjà ${data.length} trimestre(s):</p>`;
                    html += '<ul style="margin: 10px 0; padding-left: 20px;">';

                    data.forEach(trimestre => {
                        html += `<li><strong>${trimestre.nom}</strong> (Ordre: ${trimestre.ordre})</li>`;
                    });

                    html += '</ul>';
                    html += `<small>Assurez-vous que l'ordre que vous choisissez n'existe pas déjà.</small>`;

                    yearInfo.innerHTML = html;
                }
                yearInfo.style.display = 'block';
            })
            .catch(error => {
                console.error('Erreur:', error);
                yearInfo.innerHTML = `
                        <h4><i class="fas fa-exclamation-triangle"></i> Information non disponible</h4>
                        <p>Impossible de charger les informations sur les trimestres existants.</p>
                    `;
                yearInfo.style.display = 'block';
            });
    }

    // Synchronisation entre l'input et les options visuelles
    orderInput.addEventListener('input', function() {
        const value = Math.min(4, Math.max(1, parseInt(this.value) || 1));
        this.value = value;

        orderOptions.forEach(option => {
            option.classList.remove('active');
            if (option.dataset.order === value.toString()) {
                option.classList.add('active');
            }
        });
    });

    // Validation du formulaire
    document.getElementById('trimesterForm').addEventListener('submit', function(e) {
        const nom = document.getElementById('nom').value.trim();
        const ordre = parseInt(document.getElementById('ordre').value);
        const anneeId = document.getElementById('annee_id').value;

        let errors = [];

        if (!nom) {
            errors.push('Veuillez saisir un nom pour le trimestre');
        }

        if (ordre < 1 || ordre > 4) {
            errors.push('L\'ordre doit être compris entre 1 et 4');
        }

        if (!anneeId) {
            errors.push('Veuillez sélectionner une année scolaire');
        }

        if (errors.length > 0) {
            e.preventDefault();
            showAlert(errors.join('<br>'), 'error');
            return false;
        }

        // Afficher l'état de chargement
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin btn-icon"></i> Création...';
        submitBtn.disabled = true;

        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 3000);
    });

    // Auto-focus sur le nom
    document.getElementById('nom').focus();

    // Fonction d'affichage des alertes
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
        }, 5000);
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

    // Animation des éléments du formulaire
    document.addEventListener('DOMContentLoaded', function() {
        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach((group, index) => {
            group.style.animationDelay = `${index * 0.1}s`;
            group.style.animation = 'messageIn 0.5s ease-out forwards';
            group.style.opacity = '0';
        });
    });
    </script>
</body>

</html>