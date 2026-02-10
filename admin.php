<?php
require_once 'config.php';

$pdo = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['sites'] as $id => $data) {
        $stmt = $pdo->prepare("UPDATE sites SET name = ?, url = ?, rss_url = ? WHERE id = ?");
        $stmt->execute([
            $data['name'],
            $data['url'],
            $data['rss_url'],
            $id
        ]);
    }
    $message = "Sites updated successfully!";
}

// Get all sites
$stmt = $pdo->query("SELECT * FROM sites ORDER BY name");
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hunt HQ - Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #667eea;
        }
        .message {
            background: #28a745;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .site-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-right: 10px;
        }
        .button:hover {
            background: #5568d3;
        }
        .button-secondary {
            background: #6c757d;
        }
        .button-secondary:hover {
            background: #5a6268;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <h1>🎯 Hunt HQ - Admin Panel</h1>
    
    <?php if (isset($message)): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <?php foreach ($sites as $site): ?>
            <div class="site-form">
                <h3><?= htmlspecialchars($site['name']) ?></h3>
                
                <label>Site Name</label>
                <input type="text" name="sites[<?= $site['id'] ?>][name]" 
                       value="<?= htmlspecialchars($site['name']) ?>" required>
                
                <label>Site URL</label>
                <input type="text" name="sites[<?= $site['id'] ?>][url]" 
                       value="<?= htmlspecialchars($site['url']) ?>" required>
                
                <label>RSS Feed URL (optional)</label>
                <input type="text" name="sites[<?= $site['id'] ?>][rss_url]" 
                       value="<?= htmlspecialchars($site['rss_url'] ?? '') ?>" 
                       placeholder="Leave empty to disable RSS">
                
                <small style="color: #666;">Last checked: <?= $site['last_checked'] ?? 'Never' ?></small>
            </div>
        <?php endforeach; ?>
        
        <button type="submit" name="update" class="button">💾 Save Changes</button>
        <a href="index.php" class="button button-secondary">← Back to Dashboard</a>
    </form>
    
    <div class="info">
        <strong>💡 RSS Feed Tips:</strong>
        <ul>
            <li>WordPress sites usually have feeds at: <code>/feed/</code></li>
            <li>YouTube channels: Get the channel ID from the URL and use format: 
                <code>https://www.youtube.com/feeds/videos.xml?channel_id=CHANNEL_ID</code></li>
            <li>Test RSS feeds first to make sure they work</li>
        </ul>
    </div>
</body>
</html>
