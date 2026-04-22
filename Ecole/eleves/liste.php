<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/database.php';

$message = "";
$message_type = "";

// Suppression d'un élève
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM eleves WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "✅ Élève supprimé avec succès.";
        $message_type = "success";
    } else {
        $message = "❌ Erreur lors de la suppression.";
        $message_type = "error";
    }
}

// Récupération des élèves avec recherche et filtres
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$classe_filter = isset($_GET['classe']) ? (int)$_GET['classe'] : '';

$sql = "
    SELECT e.id, e.matricule, e.nom, e.prenom, e.date_naissance, 
           e.lieu_naissance, e.sexe, c.nom_classe, c.id as classe_id
    FROM eleves e
    JOIN classes c ON e.classe_id = c.id
    WHERE 1=1
";

$params = [];

if ($search) {
    $sql .= " AND (e.nom LIKE ? OR e.prenom LIKE ? OR e.matricule LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($classe_filter) {
    $sql .= " AND c.id = ?";
    $params[] = $classe_filter;
}

$sql .= " ORDER BY c.nom_classe, e.nom, e.prenom";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$eleves = $stmt->fetchAll();

// Récupérer les classes pour le filtre
$stmt_classes = $pdo->query("SELECT id, nom_classe FROM classes ORDER BY nom_classe");
$classes = $stmt_classes->fetchAll();

// Compter les élèves par classe
$stats = [];
foreach ($eleves as $e) {
    if (!isset($stats[$e['nom_classe']])) {
        $stats[$e['nom_classe']] = 0;
    }
    $stats[$e['nom_classe']]++;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des élèves | Gestion Scolaire</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
        min-height: 100vh;
        padding: 30px 20px;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    .header {
        background: linear-gradient(to right, #2b6cb0, #4299e1);
        color: white;
        padding: 35px 40px;
        position: relative;
        overflow: hidden;
    }

    .header::before {
        content: '👨‍🎓👩‍🎓';
        position: absolute;
        font-size: 150px;
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
        font-size: 42px;
    }

    .header p {
        font-size: 17px;
        opacity: 0.9;
        max-width: 600px;
    }

    .controls {
        padding: 30px 40px;
        background: #f7fafc;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: center;
    }

    .search-box {
        flex: 1;
        min-width: 300px;
        position: relative;
    }

    .search-box i {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #718096;
        font-size: 18px;
    }

    .search-box input {
        width: 100%;
        padding: 16px 20px 16px 50px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: white;
    }

    .search-box input:focus {
        outline: none;
        border-color: #4299e1;
        box-shadow: 0 0 0 4px rgba(66, 153, 225, 0.1);
    }

    .filter-box {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-box select {
        padding: 16px 20px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 16px;
        background: white;
        min-width: 200px;
        cursor: pointer;
    }

    .filter-box select:focus {
        outline: none;
        border-color: #4299e1;
    }

    .stats {
        display: flex;
        gap: 20px;
        padding: 20px 40px;
        background: linear-gradient(to right, #ebf8ff, #e6fffa);
        border-bottom: 2px solid #e2e8f0;
        flex-wrap: wrap;
    }

    .stat-card {
        flex: 1;
        min-width: 200px;
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white;
    }

    .stat-icon.total {
        background: linear-gradient(135deg, #4299e1, #3182ce);
    }

    .stat-icon.classes {
        background: linear-gradient(135deg, #38b2ac, #319795);
    }

    .stat-content h3 {
        font-size: 28px;
        color: #2d3748;
        margin-bottom: 5px;
    }

    .stat-content p {
        color: #718096;
        font-size: 15px;
    }

    .message {
        margin: 20px 40px;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        font-size: 16px;
        animation: slideIn 0.3s ease-out;
    }

    .message.success {
        background: linear-gradient(to right, #c6f6d5, #9ae6b4);
        color: #22543d;
        border-left: 5px solid #38a169;
    }

    .message.error {
        background: linear-gradient(to right, #fed7d7, #fc8181);
        color: #742a2a;
        border-left: 5px solid #e53e3e;
    }

    .table-container {
        padding: 0 40px 40px;
        overflow-x: auto;
    }

    .students-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        margin-top: 20px;
    }

    .students-table thead {
        background: linear-gradient(to right, #4a5568, #2d3748);
    }

    .students-table th {
        padding: 22px 20px;
        text-align: left;
        color: white;
        font-weight: 600;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        position: relative;
    }

    .students-table th:not(:last-child)::after {
        content: '';
        position: absolute;
        right: 0;
        top: 25%;
        height: 50%;
        width: 1px;
        background: rgba(255, 255, 255, 0.2);
    }

    .students-table tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid #e2e8f0;
    }

    .students-table tbody tr:hover {
        background: #f7fafc;
        transform: translateX(5px);
    }

    .students-table td {
        padding: 20px;
        color: #4a5568;
        font-size: 15px;
        vertical-align: middle;
    }

    .students-table .student-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .student-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        font-weight: 600;
    }

    .student-avatar.M {
        background: linear-gradient(135deg, #4299e1, #3182ce);
    }

    .student-avatar.F {
        background: linear-gradient(135deg, #ed64a6, #d53f8c);
    }

    .student-name {
        font-weight: 600;
        color: #2d3748;
    }

    .student-matricule {
        font-size: 13px;
        color: #718096;
        margin-top: 3px;
    }

    .class-badge {
        display: inline-block;
        padding: 8px 16px;
        background: #ebf8ff;
        color: #2b6cb0;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        border: 1px solid #bee3f8;
    }

    .sexe-badge {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 14px;
        font-weight: 500;
        display: inline-block;
    }

    .sexe-badge.M {
        background: #ebf8ff;
        color: #2b6cb0;
    }

    .sexe-badge.F {
        background: #fff5f7;
        color: #d53f8c;
    }

    .actions {
        display: flex;
        gap: 10px;
    }

    .btn-action {
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-view {
        background: #c6f6d5;
        color: #22543d;
    }

    .btn-view:hover {
        background: #9ae6b4;
        transform: translateY(-2px);
    }

    .btn-delete {
        background: #fed7d7;
        color: #742a2a;
    }

    .btn-delete:hover {
        background: #fc8181;
        transform: translateY(-2px);
    }

    .btn-add {
        padding: 16px 30px;
        background: linear-gradient(to right, #38a169, #2f855a);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        margin-left: auto;
    }

    .btn-add:hover {
        background: linear-gradient(to right, #2f855a, #276749);
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(56, 161, 105, 0.3);
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

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        padding: 30px;
        border-top: 2px solid #e2e8f0;
    }

    .page-btn {
        padding: 12px 20px;
        background: #edf2f7;
        color: #4a5568;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .page-btn:hover {
        background: #4299e1;
        color: white;
    }

    .page-btn.active {
        background: #4299e1;
        color: white;
    }

    .nav-links {
        display: flex;
        justify-content: center;
        gap: 25px;
        padding: 30px 40px;
        background: #f7fafc;
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
        background: #4299e1;
        color: white;
        border-color: #4299e1;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(66, 153, 225, 0.2);
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

    @media (max-width: 1024px) {

        .header,
        .controls,
        .stats,
        .table-container,
        .nav-links {
            padding: 25px;
        }

        .header h1 {
            font-size: 28px;
        }

        .students-table {
            display: block;
        }

        .students-table th,
        .students-table td {
            padding: 15px 12px;
        }
    }

    @media (max-width: 768px) {
        .controls {
            flex-direction: column;
        }

        .search-box {
            min-width: 100%;
        }

        .stat-card {
            min-width: 100%;
        }

        .actions {
            flex-direction: column;
        }
    }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Liste des élèves</h1>
            <p>Gérez et consultez la liste complète des élèves inscrits dans l'établissement</p>
        </div>

        <div class="controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="search" name="search"
                    placeholder="Rechercher un élève (nom, prénom, matricule)..."
                    value="<?php echo htmlspecialchars($search); ?>">
            </div>

            <div class="filter-box">
                <select id="classe_filter" name="classe">
                    <option value="">Toutes les classes</option>
                    <?php foreach ($classes as $classe): ?>
                    <option value="<?= $classe['id'] ?>"
                        <?php echo ($classe_filter == $classe['id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($classe['nom_classe']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <button class="btn-add" onclick="window.location.href='ajouter.php'">
                    <i class="fas fa-user-plus"></i> Nouvel élève
                </button>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($eleves); ?></h3>
                    <p>Élèves inscrits</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon classes">
                    <i class="fas fa-school"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($classes); ?></h3>
                    <p>Classes actives</p>
                </div>
            </div>

            <?php if (!empty($stats)): ?>
            <?php foreach ($stats as $classe_nom => $count): ?>
            <div class="stat-card">
                <div class="stat-icon"
                    style="background: linear-gradient(135deg, #<?php echo substr(md5($classe_nom), 0, 6); ?>, #<?php echo substr(md5($classe_nom), 6, 6); ?>);">
                    <i class="fas fa-chalkboard"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $count; ?></h3>
                    <p><?php echo htmlspecialchars($classe_nom); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <?php if (empty($eleves)): ?>
            <div class="empty-state">
                <i class="fas fa-user-slash"></i>
                <h3>Aucun élève trouvé</h3>
                <p><?php echo $search ? 'Aucun résultat pour "' . htmlspecialchars($search) . '"' : 'Aucun élève inscrit pour le moment'; ?>
                </p>
                <br>
                <a href="ajouter.php" class="btn-add">
                    <i class="fas fa-user-plus"></i> Ajouter le premier élève
                </a>
            </div>
            <?php else: ?>
            <table class="students-table">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Matricule</th>
                        <th>Date de naissance</th>
                        <th>Lieu de naissance</th>
                        <th>Sexe</th>
                        <th>Classe</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eleves as $e): 
                            $initials = strtoupper(substr($e['prenom'], 0, 1) . substr($e['nom'], 0, 1));
                            $age = date_diff(date_create($e['date_naissance']), date_create('today'))->y;
                        ?>
                    <tr>
                        <td>
                            <div class="student-info">
                                <div class="student-avatar <?php echo $e['sexe']; ?>">
                                    <?php echo $initials; ?>
                                </div>
                                <div>
                                    <div class="student-name">
                                        <?php echo htmlspecialchars($e['prenom'] . ' ' . $e['nom']); ?>
                                    </div>
                                    <div class="student-matricule">
                                        ID: <?php echo $e['id']; ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($e['matricule']); ?></strong>
                        </td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($e['date_naissance'])); ?>
                            <br>
                            <small style="color: #718096;">(<?php echo $age; ?> ans)</small>
                        </td>
                        <td><?php echo htmlspecialchars($e['lieu_naissance']); ?></td>
                        <td>
                            <span class="sexe-badge <?php echo $e['sexe']; ?>">
                                <?php echo $e['sexe'] == 'M' ? '👨 Masculin' : '👩 Féminin'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="class-badge">
                                <?php echo htmlspecialchars($e['nom_classe']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="details.php?id=<?php echo $e['id']; ?>" class="btn-action btn-view"
                                    title="Voir les détails">
                                    <i class="fas fa-eye"></i> Détails
                                </a>
                                <a href="?delete=<?php echo $e['id']; ?>" class="btn-action btn-delete"
                                    title="Supprimer"
                                    onclick="return confirmDelete('<?php echo addslashes($e['prenom'] . ' ' . $e['nom']); ?>')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </div>
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
                <i class="fas fa-user-plus"></i> Ajouter un élève
            </a>
            <a href="../classes/liste.php">
                <i class="fas fa-chalkboard"></i> Gérer les classes
            </a>
        </div>
    </div>

    <script>
    // Recherche en temps réel
    document.getElementById('search').addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            applyFilters();
        }
    });

    document.getElementById('classe_filter').addEventListener('change', applyFilters);

    function applyFilters() {
        const search = document.getElementById('search').value;
        const classe = document.getElementById('classe_filter').value;

        let url = '?';
        if (search) url += 'search=' + encodeURIComponent(search) + '&';
        if (classe) url += 'classe=' + classe;

        // Supprimer le dernier & si présent
        if (url.endsWith('&')) url = url.slice(0, -1);
        if (url === '?') url = '';

        window.location.href = url;
    }

    function confirmDelete(studentName) {
        return confirm(
            `⚠️ Êtes-vous sûr de vouloir supprimer l'élève "${studentName}" ?\n\nCette action est irréversible.`);
    }

    // Animation pour les lignes du tableau
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.students-table tbody tr');
        rows.forEach((row, index) => {
            row.style.animationDelay = (index * 0.05) + 's';
            row.style.animation = 'slideIn 0.5s ease-out forwards';
            row.style.opacity = '0';
        });
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
    </script>
</body>

</html>