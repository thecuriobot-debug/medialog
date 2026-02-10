<?php
require_once 'config.php';

// Get book ID from URL
$bookId = $_GET['id'] ?? null;

if (!$bookId) {
    die('No book ID provided');
}

// Fetch the review from database
$pdo = getDB();
$stmt = $pdo->prepare("
    SELECT p.*, s.name as site_name
    FROM posts p
    JOIN sites s ON p.site_id = s.id
    WHERE p.url LIKE ?
    ORDER BY p.publish_date DESC
    LIMIT 1
");
$stmt->execute(["%id={$bookId}%"]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die('Review not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - MediaLog</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f5f5f5;
            padding: 20px;
            color: #1a1a1a;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #666;
            text-decoration: none;
            font-size: 0.9em;
            font-family: -apple-system, sans-serif;
        }
        
        .back-link:hover {
            color: #1a1a1a;
        }
        
        .review-header {
            border-bottom: 3px solid #1a1a1a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            line-height: 1.2;
        }
        
        .meta {
            color: #666;
            font-size: 0.9em;
            font-family: -apple-system, sans-serif;
        }
        
        .book-info {
            display: flex;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .book-cover {
            flex-shrink: 0;
        }
        
        .book-cover img {
            max-width: 200px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .review-content {
            font-size: 1.1em;
            line-height: 1.8;
        }
        
        .review-content p {
            margin-bottom: 1em;
        }
        
        .review-content a {
            color: #1a1a1a;
            text-decoration: underline;
        }
        
        .review-content br {
            margin-bottom: 0.5em;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Back to MediaLog</a>
        
        <div class="review-header">
            <h1><?= htmlspecialchars($post['title']) ?></h1>
            <div class="meta">
                Review by <strong>Thomas Hunt</strong> · 
                <?= date('F j, Y', strtotime($post['publish_date'])) ?> · 
                <a href="reviews.php" style="color: #666; text-decoration: underline;">More Reviews</a>
            </div>
        </div>
        
        <div class="book-info">
            <?php if ($post['image_url']): ?>
            <div class="book-cover">
                <img src="<?= htmlspecialchars($post['image_url']) ?>" alt="Book cover">
            </div>
            <?php endif; ?>
            
            <div class="review-content">
                <?= $post['full_content'] ?>
            </div>
        </div>
    </div>
</body>
</html>
