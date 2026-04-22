<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Vérifier si l'utilisateur existe déjà
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = ?");
    $stmt->execute([$username]);
    if($stmt->rowCount() > 0){
        $message = "Cet utilisateur existe déjà.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (username, mot_de_passe, role) VALUES (?, ?, ?)");
        if($stmt->execute([$username, $password_hash, $role])){
            $message = "Utilisateur ajouté avec succès !";
        } else {
            $message = "Erreur lors de l'ajout.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvel Utilisateur | Scolarité</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --secondary: #f0f9ff;
        --success: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
        --info: #0ea5e9;
        --light: #f8fafc;
        --dark: #1e293b;
        --gray: #64748b;
        --border: #e2e8f0;
        --shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
        --radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #dbeafe 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        color: var(--dark);
        line-height: 1.6;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        width: 100%;
        max-width: 500px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.3);
        position: relative;
        animation: slideIn 0.6s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .glass-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, #6366f1, #8b5cf6, #a855f7);
        z-index: 10;
    }

    .header {
        padding: 32px 40px 24px;
        text-align: center;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        position: relative;
        overflow: hidden;
    }

    .header::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200px;
        height: 200px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .header::before {
        content: '';
        position: absolute;
        bottom: -30px;
        left: -30px;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .header-icon {
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        position: relative;
        z-index: 2;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }

    .header-icon i {
        font-size: 32px;
        color: white;
    }

    .header h2 {
        color: white;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -0.5px;
        margin-bottom: 8px;
        position: relative;
        z-index: 2;
    }

    .header p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 15px;
        position: relative;
        z-index: 2;
    }

    .content {
        padding: 40px;
    }

    .alert {
        padding: 18px 20px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: linear-gradient(135deg, #d1fae5, #ecfdf5);
        color: var(--success);
        border-left: 5px solid var(--success);
    }

    .alert-error {
        background: linear-gradient(135deg, #fee2e2, #fef2f2);
        color: var(--danger);
        border-left: 5px solid var(--danger);
    }

    .alert i {
        font-size: 20px;
    }

    .form-group {
        margin-bottom: 28px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: var(--dark);
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-group label i {
        color: var(--primary);
        width: 20px;
        text-align: center;
    }

    .input-wrapper {
        position: relative;
    }

    .input-wrapper i {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
        font-size: 18px;
        transition: var(--transition);
        z-index: 1;
    }

    .form-control {
        width: 100%;
        padding: 18px 20px 18px 52px;
        border: 2px solid var(--border);
        border-radius: 12px;
        font-size: 16px;
        font-weight: 500;
        color: var(--dark);
        background: white;
        transition: var(--transition);
        position: relative;
        z-index: 2;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .form-control:focus+i {
        color: var(--primary);
    }

    .password-strength {
        height: 6px;
        background: var(--border);
        border-radius: 3px;
        margin-top: 10px;
        overflow: hidden;
        position: relative;
    }

    .strength-meter {
        height: 100%;
        width: 0%;
        border-radius: 3px;
        transition: var(--transition);
    }

    .strength-weak {
        background: linear-gradient(90deg, #ef4444, #f87171);
    }

    .strength-medium {
        background: linear-gradient(90deg, #f59e0b, #fbbf24);
    }

    .strength-strong {
        background: linear-gradient(90deg, #10b981, #34d399);
    }

    .strength-text {
        font-size: 12px;
        color: var(--gray);
        margin-top: 5px;
        text-align: right;
    }

    .select-wrapper {
        position: relative;
    }

    .select-wrapper i {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
        font-size: 18px;
        pointer-events: none;
        z-index: 1;
    }

    select.form-control {
        appearance: none;
        background: white url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E") no-repeat right 18px center;
        background-size: 20px;
        padding-right: 50px;
        cursor: pointer;
    }

    .role-options {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-top: 10px;
    }

    .role-option {
        padding: 20px;
        border: 2px solid var(--border);
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .role-option:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .role-option.selected {
        border-color: var(--primary);
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.05), rgba(79, 70, 229, 0.05));
        box-shadow: 0 5px 15px rgba(99, 102, 241, 0.1);
    }

    .role-option input[type="radio"] {
        display: none;
    }

    .role-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 22px;
        color: white;
    }

    .role-admin .role-icon {
        background: linear-gradient(135deg, var(--primary), #8b5cf6);
    }

    .role-teacher .role-icon {
        background: linear-gradient(135deg, var(--success), #0d9488);
    }

    .role-option h4 {
        margin-bottom: 5px;
        font-weight: 600;
        color: var(--dark);
    }

    .role-option p {
        font-size: 13px;
        color: var(--gray);
        line-height: 1.4;
    }

    .btn {
        display: block;
        width: 100%;
        padding: 20px;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 10px;
        letter-spacing: 0.5px;
        position: relative;
        overflow: hidden;
    }

    .btn::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: 0.5s;
    }

    .btn:hover::after {
        left: 100%;
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
    }

    .btn:active {
        transform: translateY(-1px);
    }

    .btn i {
        font-size: 18px;
    }

    .links {
        margin-top: 35px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 18px;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 600;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .link-primary {
        background: linear-gradient(135deg, #e0f2fe, #f0f9ff);
        color: var(--primary);
        border: 2px solid #bae6fd;
    }

    .link-secondary {
        background: linear-gradient(135deg, #f1f5f9, #f8fafc);
        color: var(--gray);
        border: 2px solid var(--border);
    }

    .link:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
    }

    .link:hover i {
        transform: translateX(3px);
    }

    .link i {
        transition: var(--transition);
        font-size: 18px;
    }

    .footer {
        text-align: center;
        padding: 25px 40px;
        color: var(--gray);
        font-size: 14px;
        border-top: 1px solid var(--border);
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .footer i {
        color: var(--primary);
    }

    .password-toggle {
        position: absolute;
        right: 18px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: var(--gray);
        cursor: pointer;
        z-index: 3;
        font-size: 18px;
        transition: var(--transition);
    }

    .password-toggle:hover {
        color: var(--primary);
    }

    @media (max-width: 640px) {
        .glass-card {
            max-width: 100%;
        }

        .header,
        .content {
            padding: 25px;
        }

        .header h2 {
            font-size: 24px;
        }

        .role-options {
            grid-template-columns: 1fr;
        }

        .btn,
        .link {
            padding: 18px;
        }
    }
    </style>
</head>

<body>
    <div class="glass-card">
        <div class="header">
            <div class="header-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Nouvel Utilisateur</h2>
            <p>Créez un nouveau compte utilisateur</p>
        </div>

        <div class="content">
            <?php if($message): ?>
            <div class="alert <?php echo strpos($message, 'succès') !== false ? 'alert-success' : 'alert-error'; ?>">
                <i
                    class="fas <?php echo strpos($message, 'succès') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="userForm">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Nom d'utilisateur
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-at"></i>
                        <input type="text" id="username" name="username" class="form-control"
                            placeholder="ex: admin, enseignant1" required autocomplete="off">
                    </div>
                    <small style="color: var(--gray); margin-top: 8px; display: block;">
                        Choisissez un nom d'utilisateur unique
                    </small>
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Mot de passe
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="Saisissez un mot de passe sécurisé" required minlength="6">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-meter" id="strengthMeter"></div>
                    </div>
                    <div class="strength-text" id="strengthText">Faible</div>
                </div>

                <div class="form-group">
                    <label>
                        <i class="fas fa-user-tag"></i>
                        Rôle de l'utilisateur
                    </label>
                    <div class="role-options">
                        <label class="role-option role-admin" for="role_admin">
                            <input type="radio" id="role_admin" name="role" value="admin" required>
                            <div class="role-icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <h4>Administrateur</h4>
                            <p>Accès complet au système</p>
                        </label>

                        <label class="role-option role-teacher" for="role_teacher">
                            <input type="radio" id="role_teacher" name="role" value="enseignant" required>
                            <div class="role-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <h4>Enseignant</h4>
                            <p>Gestion des classes et notes</p>
                        </label>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn" id="submitBtn">
                    <i class="fas fa-user-plus"></i>
                    Créer l'utilisateur
                </button>
            </form>

            <div class="links">
                <a href="liste.php" class="link link-primary">
                    <i class="fas fa-list"></i>
                    Voir tous les utilisateurs
                </a>
                <a href="../accueil.php" class="link link-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Retour au tableau de bord
                </a>
            </div>
        </div>

        <div class="footer">
            <i class="fas fa-shield-alt"></i>
            <span>Système de Gestion Scolaire Sécurisé</span>
        </div>
    </div>

    <script>
    // Gestion de l'affichage du mot de passe
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        toggleIcon.classList.toggle('fa-eye');
        toggleIcon.classList.toggle('fa-eye-slash');
    });

    // Vérification de la force du mot de passe
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strengthMeter = document.getElementById('strengthMeter');
        const strengthText = document.getElementById('strengthText');

        let strength = 0;
        let text = 'Faible';
        let className = 'strength-weak';

        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
        if (password.match(/\d/)) strength++;
        if (password.match(/[^a-zA-Z\d]/)) strength++;

        switch (strength) {
            case 0:
            case 1:
                text = 'Faible';
                className = 'strength-weak';
                break;
            case 2:
                text = 'Moyen';
                className = 'strength-medium';
                break;
            case 3:
            case 4:
                text = 'Fort';
                className = 'strength-strong';
                break;
        }

        strengthMeter.style.width = (strength * 25) + '%';
        strengthMeter.className = 'strength-meter ' + className;
        strengthText.textContent = text;
    });

    // Sélection des rôles
    const roleOptions = document.querySelectorAll('.role-option');
    roleOptions.forEach(option => {
        const radio = option.querySelector('input[type="radio"]');

        option.addEventListener('click', () => {
            radio.checked = true;
            roleOptions.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
        });

        if (radio.checked) {
            option.classList.add('selected');
        }
    });

    // Animation au chargement
    document.addEventListener('DOMContentLoaded', () => {
        const card = document.querySelector('.glass-card');
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';

        setTimeout(() => {
            card.style.transition = 'all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1)';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100);
    });

    // Validation du formulaire
    const form = document.getElementById('userForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function(e) {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const roleSelected = document.querySelector('input[name="role"]:checked');

        if (!roleSelected) {
            e.preventDefault();
            alert('Veuillez sélectionner un rôle pour l\'utilisateur.');
            return;
        }

        if (username.length < 3) {
            e.preventDefault();
            alert('Le nom d\'utilisateur doit contenir au moins 3 caractères.');
            return;
        }

        if (password.length < 6) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 6 caractères.');
            return;
        }

        // Animation de chargement
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';
        submitBtn.disabled = true;

        // Réinitialisation après 5 secondes (au cas où)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 5000);
    });

    // Vérification en temps réel de la disponibilité du nom d'utilisateur
    let checkTimeout;
    const usernameInput = document.getElementById('username');

    usernameInput.addEventListener('input', function() {
        clearTimeout(checkTimeout);

        const username = this.value.trim();
        if (username.length < 3) return;

        checkTimeout = setTimeout(() => {
            // Simuler une vérification AJAX
            const wrapper = this.parentElement;
            const icon = wrapper.querySelector('i');

            icon.style.color = 'var(--warning)';
            icon.className = 'fas fa-spinner fa-spin';

            setTimeout(() => {
                // Pour l'exemple, on simule que tous les noms sont disponibles sauf "admin"
                if (username.toLowerCase() === 'admin') {
                    icon.style.color = 'var(--danger)';
                    icon.className = 'fas fa-times-circle';
                    this.style.borderColor = 'var(--danger)';
                } else {
                    icon.style.color = 'var(--success)';
                    icon.className = 'fas fa-check-circle';
                    this.style.borderColor = 'var(--success)';
                }
            }, 800);
        }, 500);
    });
    </script>
</body>

</html>