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
    
    try {
        $stmt = $pdo->prepare("INSERT INTO user_lists (user_id, name, description, list_type) VALUES (1, ?, ?, ?)");
        $stmt->execute([$listName, $description, $listType]);
        $message = 'List created successfully!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error creating list: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle list deletion
if (isset($_GET['delete_list'])) {
    $listId = (int)$_GET['delete_list'];
    try {
        $stmt = $pdo->prepare("DELETE FROM user_lists WHERE id = ? AND user_id = 1");
        $stmt->execute([$listId]);
        $message = 'List deleted successfully!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error deleting list: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all lists
$lists = $pdo->query("SELECT * FROM user_lists WHERE user_id = 1 ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get item counts for each list
foreach ($lists as &$list) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_list_items WHERE list_id = ?");
    $stmt->execute([$list['id']]);
    $list['item_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
}

$pageTitle = "My Lists";
$pageStyles = "
    .lists-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .lists-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    
    .list-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s;
        position: relative;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    .list-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .list-card h3 {
        color: #1a1a1a;
        margin-bottom: 10px;
        font-size: 1.5em;
    }
    
    .list-type-badge {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .type-books {
        background: linear-gradient(135deg, #d4af37, #f4d483);
        color: white;
    }
    
    .type-movies {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .type-mixed {
        background: linear-gradient(135deg, #a8a8a8, #d0d0d0);
        color: white;
    }
    
    .list-description {
        color: #666;
        font-size: 0.95em;
        margin: 10px 0;
        line-height: 1.5;
    }
    
    .list-count {
        display: inline-block;
        background: #f0f0f0;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
        color: #667eea;
        margin-top: 10px;
    }
    
    .list-actions {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        gap: 10px;
    }
    
    .btn-view {
        flex: 1;
        padding: 10px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        text-align: center;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-view:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-delete {
        padding: 10px 15px;
        background: #dc3545;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-delete:hover {
        background: #c82333;
    }
    
    .create-form {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .create-form h2 {
        color: #667eea;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 16px;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    }
    
    .message {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }
    
    .message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .empty-state h3 {
        color: #666;
        margin-bottom: 10px;
    }
    
    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .lists-grid {
            grid-template-columns: 1fr;
        }
    }
";

include 'includes/header.php';
?>

<div class="container lists-container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">📚 My Lists</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Organize your books and movies into custom collections</p>
    </div>
    
    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Create New List Form -->
    <div class="create-form">
        <h2>➕ Create New List</h2>
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="list_name">List Name *</label>
                    <input type="text" 
                           id="list_name" 
                           name="list_name" 
                           placeholder="e.g., Summer Reading, Classic Films, etc." 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="list_type">Type</label>
                    <select id="list_type" name="list_type" required>
                        <option value="mixed">Mixed (Books & Movies)</option>
                        <option value="books">Books Only</option>
                        <option value="movies">Movies Only</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description">Description (Optional)</label>
                <textarea id="description" 
                          name="description" 
                          placeholder="What's this list about?"></textarea>
            </div>
            
            <button type="submit" name="create_list" class="btn-primary">
                ✨ Create List
            </button>
        </form>
    </div>
    
    <!-- Display Lists -->
    <?php if (empty($lists)): ?>
        <div class="empty-state">
            <div style="font-size: 4em; margin-bottom: 15px;">📋</div>
            <h3>No lists yet</h3>
            <p style="color: #999;">Create your first list above to get started!</p>
        </div>
    <?php else: ?>
        <div class="lists-grid">
            <?php foreach ($lists as $list): ?>
                <div class="list-card">
                    <span class="list-type-badge type-<?php echo htmlspecialchars($list['list_type']); ?>">
                        <?php echo ucfirst($list['list_type']); ?>
                    </span>
                    
                    <h3><?php echo htmlspecialchars($list['name']); ?></h3>
                    
                    <?php if ($list['description']): ?>
                        <p class="list-description"><?php echo htmlspecialchars($list['description']); ?></p>
                    <?php endif; ?>
                    
                    <div class="list-count">
                        <?php echo $list['item_count']; ?> <?php echo $list['item_count'] == 1 ? 'item' : 'items'; ?>
                    </div>
                    
                    <div class="list-actions">
                        <a href="list-view.php?id=<?php echo $list['id']; ?>" class="btn-view">
                            View List
                        </a>
                        <a href="lists.php?delete_list=<?php echo $list['id']; ?>" 
                           class="btn-delete"
                           onclick="return confirm('Are you sure you want to delete this list? This cannot be undone.');">
                            🗑️
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
