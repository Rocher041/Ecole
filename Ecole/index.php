<?php
session_start();
require_once 'config/database.php';

$erreur = "";

if(isset($_POST['submit'])){
    $nom_utilisateur = $_POST['nom_utilisateur'];
    $mot_de_passe = $_POST['mot_de_passe'];

    // Préparation PDO
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = ?");
    $stmt->execute([$nom_utilisateur]);
    $user = $stmt->fetch();

    if($user && password_verify($mot_de_passe, $user['mot_de_passe'])){
        // Connexion réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        header("Location: accueil.php");
        exit;
    } else {
        $erreur = "Nom d'utilisateur ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Système de Gestion Scolaire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #1a237e 0%, #283593 50%, #3949ab 100%);
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .login-wrapper {
        display: flex;
        width: 100%;
        max-width: 1000px;
        min-height: 600px;
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
    }

    .login-side {
        flex: 1;
        background: linear-gradient(135deg, #283593 0%, #3949ab 100%);
        color: white;
        padding: 50px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .login-side h1 {
        font-size: 2.5rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .login-side h1 i {
        background: rgba(255, 255, 255, 0.2);
        padding: 15px;
        border-radius: 50%;
    }

    .login-side p {
        font-size: 1.1rem;
        line-height: 1.6;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    .features {
        list-style: none;
        margin-top: 30px;
    }

    .features li {
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .features i {
        background: rgba(255, 255, 255, 0.15);
        padding: 8px;
        border-radius: 50%;
        font-size: 0.9rem;
    }

    .login-container {
        flex: 1;
        padding: 60px 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .logo {
        text-align: center;
        margin-bottom: 40px;
    }

    .logo h2 {
        color: #283593;
        font-size: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .logo span {
        color: #3949ab;
    }

    .login-form {
        width: 100%;
    }

    .login-form h3 {
        color: #333;
        font-size: 1.8rem;
        margin-bottom: 10px;
    }

    .login-form p {
        color: #666;
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #444;
        font-weight: 500;
    }

    .input-with-icon {
        position: relative;
    }

    .input-with-icon i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }

    .input-with-icon input {
        width: 100%;
        padding: 15px 15px 15px 50px;
        border: 2px solid #ddd;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .input-with-icon input:focus {
        border-color: #3949ab;
        outline: none;
        box-shadow: 0 0 0 3px rgba(57, 73, 171, 0.1);
    }

    .btn-login {
        background: linear-gradient(to right, #283593, #3949ab);
        color: white;
        border: none;
        padding: 16px;
        width: 100%;
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-top: 10px;
    }

    .btn-login:hover {
        background: linear-gradient(to right, #1a237e, #283593);
        transform: translateY(-2px);
        box-shadow: 0 7px 14px rgba(57, 73, 171, 0.2);
    }

    .error-message {
        background: #ffebee;
        color: #c62828;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        border-left: 5px solid #c62828;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .error-message i {
        font-size: 1.2rem;
    }

    .footer-links {
        margin-top: 30px;
        text-align: center;
        color: #666;
        font-size: 0.9rem;
    }

    .footer-links a {
        color: #3949ab;
        text-decoration: none;
        font-weight: 500;
    }

    .footer-links a:hover {
        text-decoration: underline;
    }

    .copyright {
        text-align: center;
        margin-top: 30px;
        color: #999;
        font-size: 0.85rem;
    }

    @media (max-width: 850px) {
        .login-wrapper {
            flex-direction: column;
            max-width: 500px;
        }

        .login-side,
        .login-container {
            padding: 40px 30px;
        }
    }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <!-- Section latérale avec présentation -->
        <div class="login-side">
            <h1><i class="fas fa-graduation-cap"></i> Gestion Scolaire</h1>
            <p>Plateforme de gestion complète pour établissements scolaires. Gérez les étudiants, les professeurs, les
                cours et les notes en toute simplicité.</p>

            <ul class="features">
                <li><i class="fas fa-check"></i> Gestion centralisée des étudiants</li>
                <li><i class="fas fa-check"></i> Suivi des notes et évaluations</li>
                <li><i class="fas fa-check"></i> Rapports et statistiques détaillés</li>
            </ul>
        </div>

        <!-- Section de connexion -->
        <div class="login-container">
            <div class="logo">
                <h2><i class="fas fa-school"></i> <span>Gestion</span>Scolaire</h2>
            </div>

            <div class="login-form">
                <h3>Connexion au système</h3>
                <p>Accédez à votre espace de gestion scolaire</p>

                <?php if($erreur): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo $erreur; ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nom_utilisateur">Nom d'utilisateur</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user"></i>
                            <input type="text" id="nom_utilisateur" name="nom_utilisateur"
                                placeholder="Entrez votre nom d'utilisateur" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe">Mot de passe</label>
                        <div class="input-with-icon">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="mot_de_passe" name="mot_de_passe"
                                placeholder="Entrez votre mot de passe" required>
                        </div>
                    </div>

                    <button type="submit" name="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                </form>


                <div class="copyright">
                    <p>&copy; 2023 GestionScolaire Pro - Tous droits réservés</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>