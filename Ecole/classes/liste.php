<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";

// Suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = '<div class="alert alert-success">Classe supprimée avec succès.</div>';
    } else {
        $message = '<div class="alert alert-error">Erreur lors de la suppression.</div>';
    }
}

// Modification
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $nom_classe = $_POST['nom_classe'];
    $stmt = $pdo->prepare("UPDATE classes SET nom_classe = ? WHERE id = ?");
    if ($stmt->execute([$nom_classe, $id])) {
        $message = '<div class="alert alert-success">Classe modifiée avec succès.</div>';
    } else {
        $message = '<div class="alert alert-error">Erreur lors de la modification.</div>';
    }
}

// Récupération des classes
$stmt = $pdo->query("SELECT * FROM classes ORDER BY id ASC");
$classes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Classes - Système de Gestion Scolaire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    :root {
        --primary: #2c3e50;
        --secondary: #3498db;
        --accent: #9b59b6;
        --success: #2ecc71;
        --warning: #f39c12;
        --danger: #e74c3c;
        --light: #ecf0f1;
        --dark: #2c3e50;
        --gray: #95a5a6;
        --light-gray: #f8f9fa;
        --shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        --border: 1px solid #e0e0e0;
    }

    body {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        color: #333;
    }

    /* Header */
    .page-header {
        background: linear-gradient(135deg, var(--primary) 0%, #34495e 100%);
        color: white;
        padding: 25px 40px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .page-title i {
        color: var(--secondary);
        font-size: 1.8rem;
    }

    .page-title h1 {
        font-size: 1.8rem;
    }

    .page-title span {
        color: var(--secondary);
        font-weight: 300;
    }

    .header-actions {
        display: flex;
        gap: 15px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        font-size: 0.95rem;
    }

    .btn-primary {
        background: var(--secondary);
        color: white;
    }

    .btn-primary:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    }

    .btn-secondary {
        background: var(--light);
        color: var(--dark);
    }

    .btn-secondary:hover {
        background: #d5dbdb;
        transform: translateY(-2px);
    }

    .btn-success {
        background: var(--success);
        color: white;
    }

    .btn-success:hover {
        background: #27ae60;
    }

    /* Conteneur principal */
    .main-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }

    /* Alertes */
    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideIn 0.3s ease-out;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 5px solid var(--success);
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border-left: 5px solid var(--danger);
    }

    .alert i {
        font-size: 1.2rem;
    }

    /* Tableau */
    .table-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
    }

    .table-header {
        background: var(--light);
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: var(--border);
    }

    .table-header h2 {
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .stats {
        display: flex;
        gap: 15px;
    }

    .stat {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--gray);
        font-size: 0.9rem;
    }

    .stat i {
        color: var(--secondary);
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }

    .data-table thead {
        background: var(--primary);
        color: white;
    }

    .data-table th {
        padding: 18px 15px;
        text-align: left;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .data-table tbody tr {
        border-bottom: var(--border);
        transition: background 0.3s;
    }

    .data-table tbody tr:hover {
        background: var(--light-gray);
    }

    .data-table td {
        padding: 18px 15px;
        color: #555;
    }

    /* Formulaires inline */
    .inline-form {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .form-input {
        padding: 10px 15px;
        border: 2px solid #ddd;
        border-radius: 6px;
        font-size: 0.95rem;
        transition: border 0.3s;
        flex: 1;
    }

    .form-input:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .btn-sm {
        padding: 8px 15px;
        font-size: 0.85rem;
    }

    /* Actions */
    .actions {
        display: flex;
        gap: 10px;
    }

    .action-btn {
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
    }

    .edit-btn {
        background: rgba(52, 152, 219, 0.1);
        color: var(--secondary);
        border: 1px solid rgba(52, 152, 219, 0.3);
    }

    .edit-btn:hover {
        background: var(--secondary);
        color: white;
    }

    .delete-btn {
        background: rgba(231, 76, 60, 0.1);
        color: var(--danger);
        border: 1px solid rgba(231, 76, 60, 0.3);
    }

    .delete-btn:hover {
        background: var(--danger);
        color: white;
    }

    .cancel-btn {
        background: rgba(149, 165, 166, 0.1);
        color: var(--gray);
        border: 1px solid rgba(149, 165, 166, 0.3);
    }

    .cancel-btn:hover {
        background: var(--gray);
        color: white;
    }

    /* ID Badge */
    .id-badge {
        display: inline-block;
        background: var(--light);
        color: var(--primary);
        padding: 5px 10px;
        border-radius: 20px;
        font-family: monospace;
        font-weight: 600;
        font-size: 0.9rem;
    }

    /* Classe badge */
    .class-badge {
        display: inline-block;
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        color: #1565c0;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 500;
        border-left: 4px solid var(--secondary);
    }

    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 10px;
        padding: 20px;
        background: white;
        border-top: var(--border);
    }

    .pagination a,
    .pagination span {
        padding: 8px 14px;
        border-radius: 6px;
        text-decoration: none;
        color: var(--dark);
        transition: all 0.3s;
    }

    .pagination a:hover {
        background: var(--light);
    }

    .pagination .current {
        background: var(--secondary);
        color: white;
    }

    /* Footer */
    .page-footer {
        text-align: center;
        padding: 20px;
        color: var(--gray);
        font-size: 0.9rem;
    }

    /* Animations */
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

    /* Responsive */
    @media (max-width: 768px) {
        .main-container {
            padding: 0 15px;
        }

        .header-content {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .table-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .stats {
            justify-content: center;
        }

        .data-table {
            display: block;
            overflow-x: auto;
        }

        .inline-form {
            flex-direction: column;
            align-items: stretch;
        }
    }

    /* État vide */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--gray);
    }

    .empty-state i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="page-header">
        <div class="header-content">
            <div class="page-title">
                <i class="fas fa-chalkboard-teacher"></i>
                <div>
                    <h1>Gestion des <span>Classes</span></h1>
                    <p>Système de Gestion Scolaire</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="../accueil.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Tableau de Bord
                </a>
                <a href="ajouter.php" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Nouvelle Classe
                </a>
            </div>
        </div>
    </header>

    <!-- Contenu principal -->
    <div class="main-container">
        <!-- Messages -->
        <?php echo $message; ?>

        <!-- Tableau des classes -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> Liste des Classes</h2>
                <div class="stats">
                    <div class="stat">
                        <i class="fas fa-layer-group"></i>
                        <span><?= count($classes) ?> classe(s)</span>
                    </div>
                    <div class="stat">
                        <i class="fas fa-sync-alt"></i>
                        <span>Actualisé à <?= date('H:i') ?></span>
                    </div>
                </div>
            </div>

            <?php if (empty($classes)): ?>
            <div class="empty-state">
                <i class="fas fa-chalkboard"></i>
                <h3>Aucune classe enregistrée</h3>
                <p>Commencez par ajouter votre première classe.</p>
                <a href="ajouter.php" class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-plus-circle"></i> Ajouter une classe
                </a>
            </div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th>Nom de la Classe</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $classe): ?>
                    <tr>
                        <td>
                            <span class="id-badge">#<?= $classe['id'] ?></span>
                        </td>
                        <td>
                            <?php if (isset($_GET['edit']) && $_GET['edit'] == $classe['id']): ?>
                            <!-- Formulaire de modification inline -->
                            <form method="POST" action="" class="inline-form">
                                <input type="hidden" name="id" value="<?= $classe['id'] ?>">
                                <input type="text" name="nom_classe"
                                    value="<?= htmlspecialchars($classe['nom_classe']) ?>" class="form-input"
                                    placeholder="Nom de la classe" required>
                                <button type="submit" name="update" class="btn btn-success btn-sm">
                                    <i class="fas fa-check"></i> Valider
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="class-badge"><?= htmlspecialchars($classe['nom_classe']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if (!isset($_GET['edit']) || $_GET['edit'] != $classe['id']): ?>
                                <a href="?edit=<?= $classe['id'] ?>" class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="?delete=<?= $classe['id'] ?>" class="action-btn delete-btn"
                                    onclick="return confirm('Voulez-vous vraiment supprimer la classe \'<?= addslashes($classe['nom_classe']) ?>\' ?');">
                                    <i class="fas fa-trash-alt"></i> Supprimer
                                </a>
                                <?php else: ?>
                                <a href="liste.php" class="action-btn cancel-btn">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Pied de page -->
        <div class="page-footer">
            <p><i class="fas fa-info-circle"></i> Pour modifier une classe, cliquez sur "Modifier" et saisissez le
                nouveau nom.</p>
            <p style="margin-top: 10px;">© <?= date('Y') ?> Système de Gestion Scolaire • Version 3.2.1</p>
        </div>
    </div>

    <script>
    // Animation pour les alertes
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '1';
                alert.style.transform = 'translateY(0)';
            }, 100);
        });

        // Confirmation de suppression personnalisée
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm(
                    "Cette action est irréversible. Confirmez-vous la suppression ?")) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>

</html>