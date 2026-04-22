<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";
$message_type = "";

// Suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM matieres WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "✅ Matière supprimée avec succès.";
        $message_type = "success";
    } else {
        $message = "❌ Erreur lors de la suppression.";
        $message_type = "error";
    }
}

// Modification
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $nom_matiere = trim($_POST['nom_matiere']);
    $coefficient = (int)$_POST['coefficient'];

    // Validation
    if (empty($nom_matiere)) {
        $message = "❌ Le nom de la matière est requis.";
        $message_type = "error";
    } elseif ($coefficient < 1) {
        $message = "❌ Le coefficient doit être au moins 1.";
        $message_type = "error";
    } else {
        $stmt = $pdo->prepare("UPDATE matieres SET nom_matiere = ?, coefficient = ? WHERE id = ?");
        if ($stmt->execute([$nom_matiere, $coefficient, $id])) {
            $message = "✅ Matière modifiée avec succès.";
            $message_type = "success";
            // Redirection pour sortir du mode édition
            header("Location: liste.php?message=" . urlencode($message) . "&type=" . $message_type);
            exit;
        } else {
            $message = "❌ Erreur lors de la modification.";
            $message_type = "error";
        }
    }
}

// Récupération des matières
$stmt = $pdo->query("SELECT * FROM matieres ORDER BY coefficient DESC, nom_matiere ASC");
$matieres = $stmt->fetchAll();

// Récupérer les messages de redirection
if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
    $message_type = $_GET['type'] ?? 'success';
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des matières | Gestion Scolaire</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        min-height: 100vh;
        padding: 30px 20px;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        overflow: hidden;
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

    .header {
        background: linear-gradient(to right, #7b3294, #c2a5cf);
        color: white;
        padding: 35px 40px;
        position: relative;
        overflow: hidden;
    }

    .header::before {
        content: '📚🧮🔬';
        position: absolute;
        font-size: 140px;
        opacity: 0.1;
        right: 40px;
        top: 50%;
        transform: translateY(-50%);
    }

    .header h1 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header h1 i {
        font-size: 40px;
    }

    .header p {
        font-size: 17px;
        opacity: 0.9;
        max-width: 600px;
    }

    .stats-bar {
        display: flex;
        gap: 20px;
        padding: 25px 40px;
        background: linear-gradient(to right, #f5f3ff, #ede9fe);
        border-bottom: 2px solid #e2e8f0;
        flex-wrap: wrap;
    }

    .stat-item {
        flex: 1;
        min-width: 200px;
        background: white;
        padding: 20px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 5px 20px rgba(123, 50, 148, 0.08);
        transition: transform 0.3s ease;
    }

    .stat-item:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }

    .stat-icon.total {
        background: linear-gradient(135deg, #7b3294, #5a2470);
    }

    .stat-icon.coeff {
        background: linear-gradient(135deg, #00876c, #006a52);
    }

    .stat-content h3 {
        font-size: 24px;
        color: #2d3748;
        margin-bottom: 5px;
    }

    .stat-content p {
        color: #718096;
        font-size: 14px;
    }

    .message {
        margin: 20px 40px;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        font-size: 16px;
        font-weight: 500;
        animation: fadeIn 0.3s ease-out;
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

    .actions-bar {
        padding: 20px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }

    .btn-add {
        padding: 16px 30px;
        background: linear-gradient(to right, #00876c, #006a52);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
    }

    .btn-add:hover {
        background: linear-gradient(to right, #006a52, #00533f);
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 135, 108, 0.3);
    }

    .table-container {
        padding: 0 40px 40px;
        overflow-x: auto;
    }

    .subjects-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }

    .subjects-table thead {
        background: linear-gradient(to right, #5a2470, #7b3294);
    }

    .subjects-table th {
        padding: 22px 20px;
        text-align: left;
        color: white;
        font-weight: 600;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
    }

    .subjects-table th:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 25%;
        height: 50%;
        width: 1px;
        background: rgba(255, 255, 255, 0.2);
    }

    .subjects-table tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid #f1f5f9;
    }

    .subjects-table tbody tr:hover {
        background: #f8fafc;
        transform: translateX(5px);
    }

    .subjects-table tbody tr.editing {
        background: #f0f9ff;
        box-shadow: inset 0 0 0 2px #7b3294;
    }

    .subjects-table td {
        padding: 20px;
        color: #4a5568;
        font-size: 15px;
        vertical-align: middle;
    }

    .subject-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .subject-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        background: linear-gradient(135deg, #7b3294, #5a2470);
    }

    .subject-name {
        font-weight: 600;
        color: #2d3748;
        font-size: 16px;
    }

    .coeff-badge {
        display: inline-block;
        padding: 8px 16px;
        background: #f0f9ff;
        color: #0369a1;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 600;
        border: 1px solid #bae6fd;
        min-width: 80px;
        text-align: center;
    }

    .coeff-badge.high {
        background: #fef2f2;
        color: #dc2626;
        border-color: #fecaca;
    }

    .coeff-badge.medium {
        background: #fffbeb;
        color: #d97706;
        border-color: #fde68a;
    }

    .coeff-badge.low {
        background: #f0fdf4;
        color: #16a34a;
        border-color: #bbf7d0;
    }

    .form-edit {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .form-edit input {
        padding: 12px 16px;
        border: 2px solid #cbd5e1;
        border-radius: 10px;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .form-edit input:focus {
        outline: none;
        border-color: #7b3294;
        box-shadow: 0 0 0 3px rgba(123, 50, 148, 0.1);
    }

    .form-edit input[type="text"] {
        min-width: 250px;
    }

    .form-edit input[type="number"] {
        width: 100px;
    }

    .btn-action {
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        min-width: 110px;
        justify-content: center;
    }

    .btn-edit {
        background: linear-gradient(to right, #3b82f6, #2563eb);
        color: white;
    }

    .btn-edit:hover {
        background: linear-gradient(to right, #2563eb, #1d4ed8);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
    }

    .btn-delete {
        background: linear-gradient(to right, #ef4444, #dc2626);
        color: white;
    }

    .btn-delete:hover {
        background: linear-gradient(to right, #dc2626, #b91c1c);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }

    .btn-save {
        background: linear-gradient(to right, #10b981, #059669);
        color: white;
    }

    .btn-save:hover {
        background: linear-gradient(to right, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
    }

    .btn-cancel {
        background: #f1f5f9;
        color: #64748b;
        border: 2px solid #cbd5e1;
    }

    .btn-cancel:hover {
        background: #e2e8f0;
        transform: translateY(-2px);
    }

    .actions-cell {
        display: flex;
        gap: 10px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #718096;
    }

    .empty-state i {
        font-size: 80px;
        color: #cbd5e0;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        font-size: 24px;
        margin-bottom: 10px;
        color: #4a5568;
    }

    .nav-links {
        display: flex;
        justify-content: center;
        gap: 25px;
        padding: 30px 40px;
        background: #f8fafc;
        border-top: 2px solid #e2e8f0;
    }

    .nav-links a {
        padding: 16px 30px;
        background: white;
        color: #4a5568;
        text-decoration: none;
        border-radius: 12px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 12px;
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

    @media (max-width: 1024px) {

        .header,
        .stats-bar,
        .actions-bar,
        .table-container,
        .nav-links {
            padding: 25px;
        }

        .header h1 {
            font-size: 28px;
        }
    }

    @media (max-width: 768px) {
        .stats-bar {
            flex-direction: column;
        }

        .stat-item {
            min-width: 100%;
        }

        .actions-bar {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }

        .actions-cell {
            flex-direction: column;
            gap: 8px;
        }

        .form-edit {
            flex-direction: column;
            align-items: stretch;
        }

        .form-edit input[type="text"] {
            min-width: auto;
        }
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-book-open"></i> Gestion des matières</h1>
            <p>Gérez le catalogue des matières enseignées et leurs coefficients</p>
        </div>

        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-icon total">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($matieres); ?></h3>
                    <p>Matières enseignées</p>
                </div>
            </div>

            <?php
            $total_coeff = array_sum(array_column($matieres, 'coefficient'));
            $avg_coeff = count($matieres) > 0 ? $total_coeff / count($matieres) : 0;
            ?>
            <div class="stat-item">
                <div class="stat-icon coeff">
                    <i class="fas fa-balance-scale"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo number_format($avg_coeff, 1); ?></h3>
                    <p>Coefficient moyen</p>
                </div>
            </div>
        </div>

        <div class="actions-bar">
            <div>
                <h3 style="color: #4a5568; font-size: 18px;">
                    <i class="fas fa-list-check"></i> Catalogue des matières
                </h3>
                <p style="color: #718096; font-size: 14px; margin-top: 5px;">
                    Cliquez sur "Modifier" pour éditer une matière en ligne
                </p>
            </div>
            <a href="ajouter.php" class="btn-add">
                <i class="fas fa-plus-circle"></i> Nouvelle matière
            </a>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($matieres)): ?>
            <div class="empty-state">
                <i class="fas fa-book"></i>
                <h3>Aucune matière enregistrée</h3>
                <p>Commencez par ajouter votre première matière d'enseignement</p>
                <br>
                <a href="ajouter.php" class="btn-add" style="display: inline-flex; width: auto;">
                    <i class="fas fa-plus-circle"></i> Ajouter la première matière
                </a>
            </div>
            <?php else: ?>
            <table class="subjects-table">
                <thead>
                    <tr>
                        <th>Matière</th>
                        <th>Coefficient</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matieres as $matiere): 
                            $is_editing = isset($_GET['edit']) && $_GET['edit'] == $matiere['id'];
                            $coeff_class = '';
                            if ($matiere['coefficient'] >= 5) {
                                $coeff_class = 'high';
                            } elseif ($matiere['coefficient'] >= 3) {
                                $coeff_class = 'medium';
                            } else {
                                $coeff_class = 'low';
                            }
                        ?>
                    <tr <?php echo $is_editing ? 'class="editing"' : ''; ?>>
                        <td>
                            <?php if ($is_editing): ?>
                            <form method="POST" action="" class="form-edit">
                                <input type="hidden" name="id" value="<?php echo $matiere['id']; ?>">
                                <input type="text" name="nom_matiere"
                                    value="<?php echo htmlspecialchars($matiere['nom_matiere']); ?>"
                                    placeholder="Nom de la matière" required>
                                <?php else: ?>
                                <div class="subject-info">
                                    <div class="subject-icon">
                                        <?php 
                                                    $icons = ['📚', '🧮', '🔬', '🌍', '💬', '🎨', '⚽', '🎵'];
                                                    echo $icons[$matiere['id'] % count($icons)] ?? '📖';
                                                ?>
                                    </div>
                                    <div>
                                        <div class="subject-name">
                                            <?php echo htmlspecialchars($matiere['nom_matiere']); ?>
                                        </div>
                                        <small style="color: #718096; font-size: 13px;">
                                            ID: <?php echo $matiere['id']; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_editing): ?>
                            <input type="number" name="coefficient" value="<?php echo $matiere['coefficient']; ?>"
                                min="1" max="10" required>
                            <?php else: ?>
                            <span class="coeff-badge <?php echo $coeff_class; ?>">
                                <i class="fas fa-weight-hanging"></i>
                                <?php echo $matiere['coefficient']; ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($is_editing): ?>
                            <div class="actions-cell">
                                <button type="submit" name="update" class="btn-action btn-save">
                                    <i class="fas fa-check"></i> Valider
                                </button>
                                <a href="liste.php" class="btn-action btn-cancel">
                                    <i class="fas fa-times"></i> Annuler
                                </a>
                            </div>
                            </form>
                            <?php else: ?>
                            <div class="actions-cell">
                                <a href="?edit=<?php echo $matiere['id']; ?>" class="btn-action btn-edit"
                                    title="Modifier cette matière">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                                <a href="?delete=<?php echo $matiere['id']; ?>" class="btn-action btn-delete"
                                    title="Supprimer"
                                    onclick="return confirmDelete('<?php echo addslashes($matiere['nom_matiere']); ?>')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="nav-links">
            <a href="../accueil.php">
                <i class="fas fa-home"></i> Tableau de bord
            </a>
            <a href="ajouter.php">
                <i class="fas fa-plus-circle"></i> Ajouter une matière
            </a>
            <a href="../eleves/liste.php">
                <i class="fas fa-users"></i> Gérer les élèves
            </a>
        </div>
    </div>

    <script>
    // Confirmation de suppression
    function confirmDelete(subjectName) {
        return confirm(
            `⚠️ Êtes-vous sûr de vouloir supprimer la matière "${subjectName}" ?\n\nCette action supprimera également toutes les notes associées.`
            );
    }

    // Animation pour les lignes du tableau
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.subjects-table tbody tr');
        rows.forEach((row, index) => {
            row.style.animationDelay = (index * 0.05) + 's';
            row.style.animation = 'fadeIn 0.5s ease-out forwards';
            row.style.opacity = '0';
        });

        // Focus sur le champ de modification
        const editingRow = document.querySelector('.subjects-table tbody tr.editing');
        if (editingRow) {
            const input = editingRow.querySelector('input[name="nom_matiere"]');
            if (input) {
                input.focus();
                input.select();
            }
        }
    });

    // Auto-hide message
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

    // Validation du coefficient
    document.addEventListener('submit', function(e) {
        if (e.target.querySelector('input[name="coefficient"]')) {
            const coeffInput = e.target.querySelector('input[name="coefficient"]');
            const coeff = parseInt(coeffInput.value);

            if (coeff < 1) {
                e.preventDefault();
                alert('Le coefficient doit être au moins 1.');
                coeffInput.focus();
                return false;
            }

            if (coeff > 10) {
                e.preventDefault();
                alert('Le coefficient ne peut pas dépasser 10.');
                coeffInput.focus();
                return false;
            }
        }
    });
    </script>
</body>

</html>