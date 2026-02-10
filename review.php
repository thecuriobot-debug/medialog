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
