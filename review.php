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

$pageTitle = "Book Review";
$pageStyles = "
    /* Review Page Styles */
    .review-header {
        background: white;
        padding: 40px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .review-header h1 {
        font-size: 2.5em;
        color: #1a1a1a;
        margin-bottom: 15px;
    }
    
    .meta {
        color: #666;
        font-size: 1.1em;
    }
    
    .book-info {
        background: white;
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .book-cover {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .book-cover img {
        max-width: 300px;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    }
    
    .review-content {
        font-size: 1.1em;
        line-height: 1.8;
        color: #333;
    }
    
    .review-content p {
        margin-bottom: 1.2em;
    }
    
    .back-link {
        display: inline-block;
        color: white;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 30px;
        padding: 10px 20px;
        background: rgba(255,255,255,0.2);
        border-radius: 8px;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }
    
    .back-link:hover {
        background: rgba(255,255,255,0.3);
        transform: translateX(-5px);
    }
";
include 'includes/header.php';
?>

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
