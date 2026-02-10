<?php
require_once 'config.php';

// Get movie ID from URL
$movieId = $_GET['id'] ?? null;

if (!$movieId) {
    header('Location: movies.php');
    exit;
}

// Fetch movie details
$pdo = getDB();
$stmt = $pdo->prepare("
    SELECT * FROM posts 
    WHERE site_id = 6 AND url LIKE ?
");
$stmt->execute(["%{$movieId}%"]);
$movie = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$movie) {
    header('Location: movies.php');
    exit;
}

// Extract year and rating from title
preg_match('/(.*?), (\d{4})(?: - (.+))?/', $movie['title'], $matches);
$movieTitle = $matches[1] ?? $movie['title'];
$year = $matches[2] ?? '';
$rating = $matches[3] ?? '';

$pageTitle = "Movie";
include 'includes/header.php';
?>

<div class="container">
        <a href="movies.php" class="back-link">← Back to Movies</a>
        
        <div class="movie-header">
            <?php if ($movie['image_url']): ?>
                <img src="<?= htmlspecialchars($movie['image_url']) ?>" 
                     alt="<?= htmlspecialchars($movieTitle) ?> poster" 
                     class="movie-poster">
            <?php endif; ?>
            
            <div class="movie-info">
                <h1><?= htmlspecialchars($movieTitle) ?></h1>
                
                <?php if ($year): ?>
                    <div class="movie-meta"><?= htmlspecialchars($year) ?></div>
                <?php endif; ?>
                
                <?php if ($rating): ?>
                    <div class="rating"><?= htmlspecialchars($rating) ?></div>
                <?php endif; ?>
                
                <div class="watch-date">
                    Watched <?= date('F j, Y', strtotime($movie['publish_date'])) ?>
                </div>
                
                <a href="<?= htmlspecialchars($movie['url']) ?>" 
                   target="_blank" 
                   class="external-link">
                    View on Letterboxd →
                </a>
            </div>
        </div>
        
        <?php 
        // Display full content or fallback to description
        $reviewText = '';
        if ($movie['full_content'] && strlen($movie['full_content']) > 10) {
            $reviewText = $movie['full_content'];
        } elseif ($movie['description'] && strlen($movie['description']) > 10) {
            $reviewText = $movie['description'];
        }
        
        if ($reviewText):
            // Clean up the review text
            // Remove "thunt's review published on Letterboxd:" and everything before it
            if (strpos($reviewText, 'Letterboxd:') !== false) {
                $reviewText = substr($reviewText, strpos($reviewText, 'Letterboxd:') + strlen('Letterboxd:'));
            }
            // Normalize whitespace
            $reviewText = preg_replace('/[\s\t\n\r]+/', ' ', $reviewText);
            $reviewText = trim($reviewText);
        ?>
            <div class="review-content">
                <?= nl2br(htmlspecialchars($reviewText)) ?>
            </div>
        <?php else: ?>
            <div class="no-review">
                No review written for this film.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
