<?php
session_start();
if(!isset($_SESSION['user_id'])){
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";

// Suppression
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
    if($stmt->execute([$id])){
        $message = "Utilisateur supprimé avec succès.";
    } else {
        $message = "Erreur lors de la suppression.";
    }
}

// Modification
if(isset($_POST['update'])){
    $id = (int)$_POST['id'];
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = $_POST['password'];

    if($password){
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE utilisateurs SET username = ?, role = ?, mot_de_passe = ? WHERE id = ?");
        $stmt->execute([$username, $role, $password_hash, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET username = ?, role = ? WHERE id = ?");
        $stmt->execute([$username, $role, $id]);
    }
    $message = "Utilisateur modifié avec succès.";
}

// Récupération des utilisateurs
$stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY id ASC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs | Scolarité</title>
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
        padding: 30px 20px;
        color: var(--dark);
        line-height: 1.6;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .header-content h1 {
        font-size: 36px;
        font-weight: 800;
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 8px;
    }

    .header-content p {
        color: var(--gray);
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-content p i {
        color: var(--primary);
    }

    .action-buttons {
        display: flex;
        gap: 15px;
    }

    .btn {
        padding: 15px 28px;
        border-radius: 12px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        font-size: 15px;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
    }

    .btn-secondary {
        background: white;
        color: var(--dark);
        border: 2px solid var(--border);
    }

    .btn-success {
        background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
        color: white;
    }

    .btn-danger {
        background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
        color: white;
    }

    .btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(20px);
        border-radius: var(--radius);
        box-shadow: var(--shadow);
        padding: 40px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        margin-bottom: 30px;
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert {
        padding: 20px 25px;
        border-radius: 12px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 15px;
        font-weight: 500;
        animation: fadeIn 0.5s ease-out;
        border-left: 5px solid;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    .alert-success {
        background: linear-gradient(135deg, #d1fae5, #ecfdf5);
        color: var(--success);
        border-left-color: var(--success);
    }

    .alert-error {
        background: linear-gradient(135deg, #fee2e2, #fef2f2);
        color: var(--danger);
        border-left-color: var(--danger);
    }

    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        transition: var(--transition);
        border: 1px solid var(--border);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        font-size: 24px;
    }

    .stat-admin {
        background: linear-gradient(135deg, var(--primary), #8b5cf6);
        color: white;
    }

    .stat-teacher {
        background: linear-gradient(135deg, var(--success), #0d9488);
        color: white;
    }

    .stat-total {
        background: linear-gradient(135deg, var(--info), #3b82f6);
        color: white;
    }

    .stat-number {
        font-size: 36px;
        font-weight: 800;
        margin-bottom: 8px;
        color: var(--dark);
    }

    .stat-label {
        color: var(--gray);
        font-size: 15px;
        font-weight: 500;
    }

    .table-container {
        overflow-x: auto;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: white;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1000px;
    }

    .table thead {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    }

    .table th {
        padding: 22px 20px;
        text-align: left;
        color: white;
        font-weight: 600;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
    }

    .table th:first-child {
        border-top-left-radius: 16px;
    }

    .table th:last-child {
        border-top-right-radius: 16px;
    }

    .table td {
        padding: 20px;
        border-bottom: 1px solid var(--border);
        color: var(--dark);
        font-weight: 500;
        vertical-align: middle;
    }

    .table tbody tr {
        transition: var(--transition);
    }

    .table tbody tr:hover {
        background: #f8fafc;
    }

    .table tbody tr.editing {
        background: linear-gradient(135deg, #fef3c7, #fef9c3);
    }

    .user-id {
        font-family: 'Monaco', 'Courier New', monospace;
        font-weight: 700;
        color: var(--primary);
        font-size: 16px;
    }

    .form-control {
        padding: 12px 16px;
        border: 2px solid var(--border);
        border-radius: 10px;
        font-size: 15px;
        font-weight: 500;
        color: var(--dark);
        background: white;
        transition: var(--transition);
        width: 100%;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--dark);
        font-size: 14px;
    }

    .form-small {
        font-size: 12px;
        color: var(--gray);
        margin-top: 5px;
        display: block;
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 14px;
    }

    .role-admin {
        background: linear-gradient(135deg, var(--primary), #8b5cf6);
        color: white;
    }

    .role-teacher {
        background: linear-gradient(135deg, var(--success), #0d9488);
        color: white;
    }

    .action-buttons-cell {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-action {
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        font-size: 14px;
        border: none;
        cursor: pointer;
    }

    .btn-edit {
        background: linear-gradient(135deg, var(--info), #3b82f6);
        color: white;
    }

    .btn-delete {
        background: linear-gradient(135deg, var(--danger), #dc2626);
        color: white;
    }

    .btn-save {
        background: linear-gradient(135deg, var(--success), #059669);
        color: white;
    }

    .btn-cancel {
        background: linear-gradient(135deg, var(--gray), #94a3b8);
        color: white;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .footer {
        text-align: center;
        margin-top: 50px;
        padding: 25px;
        color: var(--gray);
        font-size: 14px;
        border-top: 1px solid var(--border);
        background: white;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .footer i {
        color: var(--primary);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray);
    }

    .empty-state i {
        font-size: 64px;
        color: var(--border);
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .header {
            flex-direction: column;
            text-align: center;
        }

        .header-content {
            text-align: center;
        }

        .action-buttons {
            flex-direction: column;
            width: 100%;
        }

        .btn {
            justify-content: center;
        }

        .stats-cards {
            grid-template-columns: 1fr;
        }

        .glass-card {
            padding: 25px;
        }
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal {
        background: white;
        border-radius: var(--radius);
        padding: 40px;
        max-width: 500px;
        width: 90%;
        box-shadow: var(--shadow);
        animation: modalIn 0.3s ease-out;
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: translateY(-20px) scale(0.95);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal-header {
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .modal-header i {
        color: var(--danger);
        font-size: 28px;
    }

    .modal-buttons {
        display: flex;
        gap: 15px;
        margin-top: 30px;
    }

    .modal-buttons .btn {
        flex: 1;
        justify-content: center;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <h1>Gestion des Utilisateurs</h1>
                <p>
                    <i class="fas fa-users-cog"></i>
                    Gérez les accès et permissions des utilisateurs
                </p>
            </div>
            <div class="action-buttons">
                <a href="ajouter.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Nouvel Utilisateur
                </a>
                <a href="../accueil.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Tableau de Bord
                </a>
            </div>
        </div>

        <?php if($message): ?>
        <div class="alert <?php echo strpos($message, 'succès') !== false ? 'alert-success' : 'alert-error'; ?>">
            <i
                class="fas <?php echo strpos($message, 'succès') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>

        <?php 
            $adminCount = 0;
            $teacherCount = 0;
            $totalCount = count($users);
            
            foreach($users as $user) {
                if($user['role'] == 'admin') $adminCount++;
                if($user['role'] == 'enseignant') $teacherCount++;
            }
        ?>

        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon stat-total">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $totalCount; ?></div>
                <div class="stat-label">Utilisateurs Totaux</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-admin">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-number"><?php echo $adminCount; ?></div>
                <div class="stat-label">Administrateurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-teacher">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="stat-number"><?php echo $teacherCount; ?></div>
                <div class="stat-label">Enseignants</div>
            </div>
        </div>

        <div class="glass-card">
            <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <h3>Aucun utilisateur enregistré</h3>
                <p>Commencez par ajouter un nouvel utilisateur</p>
                <a href="ajouter.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i>
                    Ajouter le premier utilisateur
                </a>
            </div>
            <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-card"></i> ID</th>
                            <th><i class="fas fa-user"></i> Nom d'Utilisateur</th>
                            <th><i class="fas fa-user-tag"></i> Rôle</th>
                            <th><i class="fas fa-cogs"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr
                            class="<?php echo (isset($_GET['edit']) && $_GET['edit'] == $user['id']) ? 'editing' : ''; ?>">
                            <td>
                                <span class="user-id">#<?php echo $user['id']; ?></span>
                            </td>
                            <td>
                                <?php if(isset($_GET['edit']) && $_GET['edit'] == $user['id']): ?>
                                <form method="POST" action="" style="max-width: 300px;">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <div class="form-group">
                                        <input type="text" name="username" class="form-control"
                                            value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                    <?php else: ?>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div
                                            style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;">
                                                <?php echo htmlspecialchars($user['username']); ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                            </td>
                            <td>
                                <?php if(isset($_GET['edit']) && $_GET['edit'] == $user['id']): ?>
                                <div class="form-group">
                                    <select name="role" class="form-control" required>
                                        <option value="admin" <?php echo $user['role']=='admin'?'selected':''; ?>>
                                            Administrateur</option>
                                        <option value="enseignant"
                                            <?php echo $user['role']=='enseignant'?'selected':''; ?>>Enseignant</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <input type="password" name="password" class="form-control"
                                        placeholder="Nouveau mot de passe (optionnel)">
                                    <span class="form-small">Laisser vide pour conserver le mot de passe actuel</span>
                                </div>
                                <?php else: ?>
                                <span
                                    class="role-badge <?php echo $user['role'] == 'admin' ? 'role-admin' : 'role-teacher'; ?>">
                                    <i
                                        class="fas <?php echo $user['role'] == 'admin' ? 'fa-user-shield' : 'fa-chalkboard-teacher'; ?>"></i>
                                    <?php echo htmlspecialchars($user['role'] == 'admin' ? 'Administrateur' : 'Enseignant'); ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons-cell">
                                    <?php if(isset($_GET['edit']) && $_GET['edit'] == $user['id']): ?>
                                    <button type="submit" name="update" class="btn-action btn-save">
                                        <i class="fas fa-check"></i>
                                        Enregistrer
                                    </button>
                                    <a href="liste.php" class="btn-action btn-cancel">
                                        <i class="fas fa-times"></i>
                                        Annuler
                                    </a>
                                    </form>
                                    <?php else: ?>
                                    <a href="?edit=<?php echo $user['id']; ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i>
                                        Modifier
                                    </a>
                                    <a href="#" class="btn-action btn-delete"
                                        onclick="showDeleteModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>')">
                                        <i class="fas fa-trash"></i>
                                        Supprimer
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <i class="fas fa-shield-alt"></i>
            <span>Système de Gestion Scolaire • © <?php echo date('Y'); ?></span>
        </div>
    </div>

    <!-- Modal de suppression -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h2 style="margin: 0; color: var(--danger);">Confirmer la suppression</h2>
            </div>
            <p id="modalMessage" style="color: var(--dark); line-height: 1.6;">
                Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.
            </p>
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="hideDeleteModal()">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>
                <a href="#" id="confirmDelete" class="btn btn-danger">
                    <i class="fas fa-trash"></i>
                    Supprimer
                </a>
            </div>
        </div>
    </div>

    <script>
    // Modal de suppression
    let currentDeleteId = null;

    function showDeleteModal(id, username) {
        currentDeleteId = id;
        const modal = document.getElementById('deleteModal');
        const message = document.getElementById('modalMessage');
        const confirmLink = document.getElementById('confirmDelete');

        message.innerHTML = `Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>"${username}"</strong> ?<br><br>
                                <small style="color: var(--danger);">
                                    <i class="fas fa-exclamation-circle"></i> Cette action est irréversible.
                                </small>`;

        confirmLink.href = `?delete=${id}`;
        modal.style.display = 'flex';
    }

    function hideDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        currentDeleteId = null;
    }

    // Fermer la modal en cliquant à l'extérieur
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideDeleteModal();
        }
    });

    // Animation des statistiques
    document.addEventListener('DOMContentLoaded', function() {
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            const target = parseInt(stat.textContent);
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    stat.textContent = target;
                    clearInterval(timer);
                } else {
                    stat.textContent = Math.floor(current);
                }
            }, 30);
        });

        // Effet visuel pour les lignes en édition
        const editingRow = document.querySelector('tr.editing');
        if (editingRow) {
            editingRow.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
        }
    });

    // Gestion des messages de succès avec auto-dismiss
    const alert = document.querySelector('.alert');
    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    }
    </script>
</body>

</html>