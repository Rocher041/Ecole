<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $libelle = trim($_POST['libelle']);

    // Vérifier si l'année existe déjà
    $stmt = $pdo->prepare("SELECT * FROM annees_scolaires WHERE libelle = ?");
    $stmt->execute([$libelle]);
    if ($stmt->rowCount() > 0) {
        $message = "Cette année scolaire existe déjà.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO annees_scolaires (libelle) VALUES (?)");
        if ($stmt->execute([$libelle])) {
            $message = "Année scolaire ajoutée avec succès !";
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
    <title>Ajouter une année scolaire | Scolarité</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary: #6366f1;
        --primary-dark: #4f46e5;
        --secondary: #f0f9ff;
        --success: #10b981;
        --error: #ef4444;
        --warning: #f59e0b;
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
        max-width: 480px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.3);
        position: relative;
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
        width: 70px;
        height: 70px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        position: relative;
        z-index: 2;
    }

    .header-icon i {
        font-size: 28px;
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
        animation: slideIn 0.4s ease-out;
        transform-origin: top;
    }

    @keyframes slideIn {
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
        color: var(--error);
        border-left: 5px solid var(--error);
    }

    .alert i {
        font-size: 20px;
    }

    .form-group {
        margin-bottom: 28px;
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
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .form-control:focus+i {
        color: var(--primary);
    }

    .form-control::placeholder {
        color: #94a3b8;
        font-weight: 400;
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

    .link-primary:hover {
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        border-color: var(--primary);
    }

    .link-secondary:hover {
        background: white;
        border-color: var(--gray);
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

    /* Responsive */
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

        .btn,
        .link {
            padding: 18px;
        }
    }

    /* Animation pour le formulaire */
    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .content>* {
        animation: fadeUp 0.6s ease-out forwards;
    }

    .content>*:nth-child(2) {
        animation-delay: 0.1s;
    }

    .content>*:nth-child(3) {
        animation-delay: 0.2s;
    }

    .content>*:nth-child(4) {
        animation-delay: 0.3s;
    }

    /* Hover effect pour les cartes */
    .glass-card:hover {
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.12);
        transform: translateY(-5px);
        transition: var(--transition);
    }

    .glass-card {
        transition: var(--transition);
    }
    </style>
</head>

<body>
    <div class="glass-card">
        <div class="header">
            <div class="header-icon">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <h2>Nouvelle Année Scolaire</h2>
            <p>Ajoutez une nouvelle année académique</p>
        </div>

        <div class="content">
            <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'succès') !== false ? 'alert-success' : 'alert-error'; ?>">
                <i
                    class="fas <?php echo strpos($message, 'succès') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="libelle">
                        <i class="fas fa-graduation-cap"></i>
                        Libellé de l'année scolaire
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-hashtag"></i>
                        <input type="text" id="libelle" name="libelle" class="form-control"
                            placeholder="Ex: 2025-2026, 2024-2025" required pattern="\d{4}-\d{4}"
                            title="Format: 2025-2026">
                    </div>
                    <small style="color: var(--gray); margin-top: 8px; display: block;">
                        Format requis : AAAA-AAAA (ex: 2025-2026)
                    </small>
                </div>

                <button type="submit" name="submit" class="btn">
                    <i class="fas fa-plus-circle"></i>
                    Ajouter l'année scolaire
                </button>
            </form>

            <div class="links">
                <a href="activer.php" class="link link-primary">
                    <i class="fas fa-calendar-check"></i>
                    Gérer les années scolaires
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
    // Validation en temps réel du format
    document.getElementById('libelle').addEventListener('input', function(e) {
        const input = e.target;
        const pattern = /^\d{4}-\d{4}$/;

        if (input.value && !pattern.test(input.value)) {
            input.style.borderColor = 'var(--error)';
            input.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
        } else {
            input.style.borderColor = 'var(--border)';
            input.style.boxShadow = 'none';
        }
    });

    // Animation subtile au chargement
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

    // Feedback visuel à la soumission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const btn = this.querySelector('.btn');
        const originalText = btn.innerHTML;

        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours...';
        btn.style.opacity = '0.8';
        btn.disabled = true;

        // Réanimation après soumission (au cas où il y a une redirection)
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.opacity = '1';
            btn.disabled = false;
        }, 3000);
    });
    </script>
</body>

</html>