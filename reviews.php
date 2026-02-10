<?php
require_once 'config.php';

// Fetch all Goodreads reviews
$pdo = getDB();
$stmt = $pdo->query("
    SELECT p.*
    FROM posts p
    JOIN sites s ON p.site_id = s.id
    WHERE s.name = 'Goodreads'
    ORDER BY p.publish_date DESC
");
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Reviews by Thomas Hunt - Hunt HQ</title>
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
        
        .nav-links a.active {
            background: #2a2a2a;
            border-bottom-color: #d4af37;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            margin-bottom: 20px;
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
        
        .page-header {
            border-bottom: 3px solid #1a1a1a;
            padding-bottom: 20px;
            margin-bottom: 40px;
        }
        
        h1 {
            font-size: 3em;
            margin-bottom: 10px;
        }
        
        .byline {
            font-size: 1.2em;
            color: #666;
            font-style: italic;
        }
        
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .review-card {
            border: 1px solid #ddd;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .review-card img {
            width: 100%;
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }
        
        .review-card h3 {
            font-size: 1.2em;
            margin-bottom: 8px;
            line-height: 1.3;
        }
        
        .review-card h3 a {
            color: #1a1a1a;
            text-decoration: none;
        }
        
        .review-card h3 a:hover {
            text-decoration: underline;
        }
        
        .review-date {
            color: #999;
            font-size: 0.85em;
            font-family: -apple-system, sans-serif;
        }
        
        .review-description {
            margin-top: 10px;
            font-size: 0.9em;
            line-height: 1.6;
            color: #333;
        }
        
        .count {
            margin-bottom: 30px;
            font-family: -apple-system, sans-serif;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">HUNT HQ</a>
            <ul class="nav-links">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="books.php">All Books</a></li>
                <li><a href="authors.php">Authors</a></li>
                <li><a href="stats.php">Statistics</a></li>
                <li><a href="insights.php">Insights</a></li>
                <li><a href="reviews.php" class="active">Reviews</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        
        <div class="page-header">
            <h1>Book Reviews</h1>
            <div class="byline">by Thomas Hunt</div>
        </div>
        
        <div class="count">
            <strong><?= count($reviews) ?></strong> reviews
        </div>
        
        <div class="reviews-grid">
            <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <?php if ($review['image_url']): ?>
                <a href="<?= htmlspecialchars($review['url']) ?>">
                    <img src="<?= htmlspecialchars($review['image_url']) ?>" alt="Book cover">
                </a>
                <?php endif; ?>
                
                <h3>
                    <a href="<?= htmlspecialchars($review['url']) ?>">
                        <?= htmlspecialchars($review['title']) ?>
                    </a>
                </h3>
                
                <div class="review-date">
                    <?= date('F j, Y', strtotime($review['publish_date'])) ?>
                </div>
                
                <?php if ($review['description']): ?>
                <div class="review-description">
                    <?= htmlspecialchars($review['description']) ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
