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
    .container .review-container {
        background: white !important;
        background-color: white !important;
        padding: 40px !important;
        border-radius: 15px !important;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
        border: 3px solid red !important; /* DEBUG: Make it visible */
    }
    
    .back-link {
        display: inline-block;
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 30px;
        padding: 10px 20px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: 2px solid #e0e0e0;
    }
    
    .back-link:hover {
        background: #667eea;
        color: white;
        border-color: #667eea;
        transform: translateX(-5px);
    }
    
    .review-header {
        padding-bottom: 30px;
        border-bottom: 2px solid #e0e0e0;
        margin-bottom: 30px;
    }
    
    .review-header h1 {
        font-size: 2.5em;
        color: #1a1a1a;
        margin-bottom: 15px;
        line-height: 1.3;
    }
    
    .meta {
        color: #666;
        font-size: 1.1em;
    }
    
    .meta a {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }
    
    .meta a:hover {
        text-decoration: underline;
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
    
    .review-content h2 {
        margin-top: 1.5em;
        margin-bottom: 0.8em;
        color: #1a1a1a;
    }
";
include 'includes/header.php';
?>

<div class="container">
    <style>
        .review-container {
            background: white !important;
            background-color: #ffffff !important;
            padding: 40px !important;
            border-radius: 15px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
        }
        .back-link {
            display: inline-block;
            color: #667eea !important;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
            padding: 10px 20px;
            background: #f8f9fa !important;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        .review-header {
            padding-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 30px;
        }
        .review-header h1 {
            font-size: 2.5em;
            color: #1a1a1a;
        }
    </style>
    <div class="review-container">
        <a href="index.php" class="back-link">← Back to MediaLog</a>
        
        <div class="review-header">
            <h1><?= htmlspecialchars($post['title']) ?></h1>
            <div class="meta">
                Review by <strong>Thomas Hunt</strong> · 
                <?= date('F j, Y', strtotime($post['publish_date'])) ?> · 
                <a href="reviews.php">More Reviews</a>
            </div>
        </div>
        
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
