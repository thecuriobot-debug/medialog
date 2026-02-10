<?php
require_once 'config.php';
$pdo = getDB();

$listId = $_GET['id'] ?? 0;

// Get list details
$stmt = $pdo->prepare("SELECT * FROM user_lists WHERE id = ? AND user_id = 1");
$stmt->execute([$listId]);
$list = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$list) {
    header('Location: lists.php');
    exit;
}

// Handle adding item to list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $postId = (int)$_POST['post_id'];
    $notes = trim($_POST['notes'] ?? '');
    
    try {
        $stmt = $pdo->prepare("INSERT INTO user_list_items (list_id, post_id, notes) VALUES (?, ?, ?)
                               ON DUPLICATE KEY UPDATE notes = ?");
        $stmt->execute([$listId, $postId, $notes, $notes]);
        $message = 'Item added to list!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle removing item
if (isset($_GET['remove_item'])) {
    $itemId = (int)$_GET['remove_item'];
    $stmt = $pdo->prepare("DELETE FROM user_list_items WHERE id = ? AND list_id = ?");
    $stmt->execute([$itemId, $listId]);
}

// Get list items with full post details
$stmt = $pdo->prepare("
    SELECT 
        uli.id as list_item_id,
        uli.notes,
        uli.added_at,
        p.id as post_id,
        p.title,
        p.url,
        p.image_url,
        p.publish_date,
        p.description,
        p.site_id
    FROM user_list_items uli
    JOIN posts p ON uli.post_id = p.id
    WHERE uli.list_id = ?
    ORDER BY uli.sort_order, uli.added_at DESC
");
$stmt->execute([$listId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Search for items to add
$searchResults = [];
if (isset($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $siteFilter = '';
    
    if ($list['list_type'] === 'books') {
        $siteFilter = " AND site_id = 7";
    } elseif ($list['list_type'] === 'movies') {
        $siteFilter = " AND site_id = 6";
    }
    
    $stmt = $pdo->prepare("
        SELECT id, title, image_url, site_id
        FROM posts
        WHERE (title LIKE ? OR description LIKE ?)
        $siteFilter
        AND id NOT IN (SELECT post_id FROM user_list_items WHERE list_id = ?)
        LIMIT 20
    ");
    $stmt->execute([$search, $search, $listId]);
    $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function cleanTitle($title) {
    $title = preg_replace('/ by .*$/', '', $title);
    $title = preg_replace('/, \d{4} - ★+$/', '', $title);
    $title = preg_replace('/ - ★+$/', '', $title);
    return trim($title);
}

function getStars($title) {
    return substr_count($title, '★');
}

$pageTitle = htmlspecialchars($list['name']);
$pageStyles = "
    .list-view-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .list-header-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .list-type-badge {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: 600;
        margin-bottom: 15px;
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
    
    .add-item-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .search-box {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .search-box input {
        flex: 1;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .search-box button {
        padding: 12px 25px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .search-results {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .search-result-item {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .search-result-item:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    
    .search-result-item img {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 4px;
        margin-bottom: 10px;
    }
    
    .search-result-item h4 {
        font-size: 0.9em;
        margin: 0;
        color: #333;
    }
    
    .add-btn {
        margin-top: 10px;
        padding: 6px 12px;
        background: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85em;
        font-weight: 600;
    }
    
    .items-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }
    
    .list-item-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s;
    }
    
    .list-item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .item-image {
        width: 100%;
        height: 280px;
        object-fit: contain;
        background: #f5f5f5;
    }
    
    .item-content {
        padding: 20px;
    }
    
    .item-title {
        font-size: 1.2em;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 10px;
    }
    
    .item-meta {
        color: #666;
        font-size: 0.9em;
        margin-bottom: 10px;
    }
    
    .item-notes {
        background: #f8f9fa;
        padding: 12px;
        border-radius: 8px;
        border-left: 3px solid #667eea;
        margin-top: 10px;
        font-size: 0.9em;
        color: #495057;
        line-height: 1.5;
    }
    
    .item-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
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
    
    .btn-remove {
        padding: 10px 15px;
        background: #dc3545;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 30px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
";

include 'includes/header.php';
?>

<div class="container list-view-container">
    <!-- List Header -->
    <div class="list-header-card">
        <span class="list-type-badge type-<?php echo htmlspecialchars($list['list_type']); ?>">
            <?php echo ucfirst($list['list_type']); ?>
        </span>
        <h1 style="font-size: 2.5em; color: #1a1a1a; margin-bottom: 10px;">
            <?php echo htmlspecialchars($list['name']); ?>
        </h1>
        <?php if ($list['description']): ?>
            <p style="color: #666; font-size: 1.1em; margin-bottom: 15px;">
                <?php echo htmlspecialchars($list['description']); ?>
            </p>
        <?php endif; ?>
        <p style="color: #999; font-size: 0.9em;">
            <?php echo count($items); ?> <?php echo count($items) == 1 ? 'item' : 'items'; ?> in this list
        </p>
        <a href="lists.php" style="display: inline-block; margin-top: 15px; color: #667eea; text-decoration: none; font-weight: 600;">
            ← Back to All Lists
        </a>
    </div>
    
    <!-- Add Items -->
    <div class="add-item-card">
        <h2 style="color: #667eea; margin-bottom: 20px;">➕ Add Items to List</h2>
        <form method="GET" class="search-box">
            <input type="hidden" name="id" value="<?php echo $listId; ?>">
            <input type="text" 
                   name="search" 
                   placeholder="Search for books or movies to add..."
                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            <button type="submit">🔍 Search</button>
        </form>
        
        <?php if (!empty($searchResults)): ?>
            <div class="search-results">
                <?php foreach ($searchResults as $result): ?>
                    <div class="search-result-item">
                        <?php if ($result['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($result['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars(cleanTitle($result['title'])); ?>">
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars(mb_substr(cleanTitle($result['title']), 0, 40)); ?></h4>
                        <form method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="post_id" value="<?php echo $result['id']; ?>">
                            <button type="submit" name="add_item" class="add-btn">+ Add</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- List Items -->
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <div style="font-size: 4em; margin-bottom: 15px;">📋</div>
            <h3>No items yet</h3>
            <p style="color: #999;">Search and add items above to get started!</p>
        </div>
    <?php else: ?>
        <div class="items-grid">
            <?php foreach ($items as $item): 
                $title = cleanTitle($item['title']);
                $stars = getStars($item['title']);
                $itemLink = $item['site_id'] == 7 ? "review.php?id={$item['post_id']}" : "movie.php?slug=" . basename(dirname($item['url']));
            ?>
                <div class="list-item-card">
                    <?php if ($item['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($title); ?>"
                             class="item-image">
                    <?php endif; ?>
                    
                    <div class="item-content">
                        <h3 class="item-title"><?php echo htmlspecialchars($title); ?></h3>
                        
                        <p class="item-meta">
                            <?php if ($stars > 0): ?>
                                <span style="color: #d4af37;"><?php echo str_repeat('★', $stars); ?></span><br>
                            <?php endif; ?>
                            <span>Added: <?php echo date('M j, Y', strtotime($item['added_at'])); ?></span>
                        </p>
                        
                        <?php if ($item['notes']): ?>
                            <div class="item-notes">
                                <strong>Notes:</strong> <?php echo htmlspecialchars($item['notes']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="item-actions">
                            <a href="<?php echo $itemLink; ?>" class="btn-view">View Details</a>
                            <a href="?id=<?php echo $listId; ?>&remove_item=<?php echo $item['list_item_id']; ?>" 
                               class="btn-remove"
                               onclick="return confirm('Remove this item from the list?');">
                                🗑️
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
