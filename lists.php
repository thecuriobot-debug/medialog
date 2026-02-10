<?php
require_once 'config.php';
$pdo = getDB();

$message = '';
$messageType = '';

// Handle list creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_list'])) {
    $listName = trim($_POST['list_name']);
    $listType = $_POST['list_type'];
    $description = trim($_POST['description'] ?? '');
    
    $stmt = $pdo->prepare("INSERT INTO custom_lists (list_name, list_type, description) VALUES (?, ?, ?)");
    $stmt->execute([$listName, $listType, $description]);
    $message = 'List created successfully!';
    $messageType = 'success';
}

// Get all lists
$lists = $pdo->query("SELECT * FROM custom_lists ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get item counts for each list
foreach ($lists as &$list) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM list_items WHERE list_id = ?");
    $stmt->execute([$list['id']]);
    $list['item_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

$pageTitle = "My Lists";
$pageStyles = "
    .lists-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    .list-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    .list-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .list-card h3 {
        color: #1a1a1a;
        margin-bottom: 10px;
    }
    .list-type-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.8em;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .type-books {
        background: #fff3cd;
        color: #856404;
    }
    .type-movies {
        background: #d1ecf1;
        color: #0c5460;
    }
    .type-mixed {
        background: #e2e3e5;
        color: #383d41;
    }
    .create-form {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #333;
    }
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
    }
    .btn {
        padding: 10px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
";
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">📝 My Lists</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Organize your media collections</p>
    </div>
    
    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <div class="create-form">
        <h2 style="margin-bottom: 20px;">Create New List</h2>
        <form method="POST">
            <input type="hidden" name="create_list" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label>List Name</label>
                    <input type="text" name="list_name" required placeholder="e.g., To Read, Favorites">
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="list_type" required>
                        <option value="books">📚 Books</option>
                        <option value="movies">🎬 Movies</option>
                        <option value="mixed">📚🎬 Mixed</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Description (optional)</label>
                <textarea name="description" rows="2" placeholder="What's this list for?"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">➕ Create List</button>
        </form>
    </div>
    
    <div class="lists-grid">
        <?php foreach ($lists as $list): ?>
            <div class="list-card">
                <span class="list-type-badge type-<?= $list['list_type'] ?>">
                    <?= $list['list_type'] === 'books' ? '📚 Books' : ($list['list_type'] === 'movies' ? '🎬 Movies' : '📚🎬 Mixed') ?>
                </span>
                <h3><?= htmlspecialchars($list['list_name']) ?></h3>
                <?php if ($list['description']): ?>
                    <p style="color: #666; font-size: 0.9em; margin: 10px 0;">
                        <?= htmlspecialchars($list['description']) ?>
                    </p>
                <?php endif; ?>
                <p style="color: #999; font-size: 0.85em; margin-top: 15px;">
                    <?= $list['item_count'] ?> items
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
