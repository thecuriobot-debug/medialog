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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movieTitle) ?> - MediaLog</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f5f5f5;
            padding: 0;
            color: #1a1a1a;
        }
        
        .top-nav {
            background: #1a1a1a;
            border-bottom: 3px solid #d4af37;
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        
        .nav-brand {
            font-family: 'Georgia', serif;
            font-size: 24px;
            font-weight: bold;
            color: #d4af37;
            padding: 15px 0;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 0;
        }
        
        .nav-links a {
            display: block;
            padding: 20px 20px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-links a:hover {
            background: #2a2a2a;
            border-bottom-color: #d4af37;
        }
        
        .container {
            max-width: 900px;
            margin: 40px auto;
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
        }
        
        .back-link:hover {
            color: #d4af37;
        }
        
        .movie-header {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .movie-poster {
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .movie-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            line-height: 1.2;
        }
        
        .movie-meta {
            font-size: 1.1em;
            color: #666;
            margin-bottom: 15px;
        }
        
        .rating {
            font-size: 1.5em;
            color: #d4af37;
            margin-bottom: 15px;
        }
        
        .watch-date {
            color: #999;
            font-size: 0.9em;
            margin-bottom: 20px;
        }
        
        .external-link {
            display: inline-block;
            background: #1a1a1a;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9em;
            transition: background 0.3s;
        }
        
        .external-link:hover {
            background: #333;
        }
        
        .review-content {
            line-height: 1.8;
            font-size: 1.1em;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }
        
        .no-review {
            color: #999;
            font-style: italic;
            padding: 40px;
            text-align: center;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        @media (max-width: 768px) {
            .movie-header {
                grid-template-columns: 1fr;
            }
            
            .movie-poster {
                max-width: 200px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">MEDIALOG</a>
            <ul class="nav-links">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="movies.php">Movies</a></li>
                <li><a href="authors.php">Authors</a></li>
                <li><a href="directors.php">Directors</a></li>
                <li><a href="stats.php">Statistics</a></li>
                <li><a href="insights.php">Insights</a></li>
            </ul>
        </div>
    </nav>
    
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
