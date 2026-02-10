<?php
require_once 'config.php';

$pdo = getDB();

// Get today's date for "On This Day"
$today = date('m-d');
$currentYear = date('Y');

// Check if current year has any data, fallback to previous year if empty
$stmt = $pdo->query("
    SELECT COUNT(*) as total FROM posts 
    WHERE (site_id = 6 OR site_id = 7) 
    AND YEAR(publish_date) = {$currentYear}
");
$currentYearCount = $stmt->fetch()['total'];

if ($currentYearCount == 0) {
    $currentYear = $currentYear - 1;
}

// Get items from this day in previous years
$stmt = $pdo->query("
    SELECT *, 'book' as type FROM posts 
    WHERE site_id = 7 AND DATE_FORMAT(publish_date, '%m-%d') = '{$today}'
    ORDER BY publish_date DESC
    LIMIT 5
");
$todayBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT *, 'movie' as type FROM posts 
    WHERE site_id = 6 AND DATE_FORMAT(publish_date, '%m-%d') = '{$today}'
    ORDER BY publish_date DESC
    LIMIT 5
");
$todayMovies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$onThisDay = array_merge($todayBooks, $todayMovies);

// If nothing on this exact day, find the most recent "On This Day" match
if (empty($onThisDay)) {
    // Look backwards day by day until we find something (max 365 days)
    for ($daysBack = 1; $daysBack <= 365; $daysBack++) {
        $checkDate = date('m-d', strtotime("-{$daysBack} days"));
        
        $stmt = $pdo->query("
            SELECT *, 'book' as type FROM posts 
            WHERE site_id = 7 AND DATE_FORMAT(publish_date, '%m-%d') = '{$checkDate}'
            ORDER BY publish_date DESC
            LIMIT 3
        ");
        $fallbackBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("
            SELECT *, 'movie' as type FROM posts 
            WHERE site_id = 6 AND DATE_FORMAT(publish_date, '%m-%d') = '{$checkDate}'
            ORDER BY publish_date DESC
            LIMIT 3
        ");
        $fallbackMovies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $fallback = array_merge($fallbackBooks, $fallbackMovies);
        
        if (!empty($fallback)) {
            $onThisDay = $fallback;
            $onThisDayDate = date('F j', strtotime("-{$daysBack} days"));
            break;
        }
    }
} else {
    $onThisDayDate = date('F j');
}

// Get recent items
$stmt = $pdo->query("
    SELECT *, 'book' as type FROM posts 
    WHERE site_id = 7
    ORDER BY publish_date DESC
    LIMIT 6
");
$recentBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT *, 'movie' as type FROM posts 
    WHERE site_id = 6
    ORDER BY publish_date DESC
    LIMIT 6
");
$recentMovies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get random items
$stmt = $pdo->query("
    SELECT *, 'book' as type FROM posts 
    WHERE site_id = 7
    ORDER BY RAND()
    LIMIT 4
");
$randomBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT *, 'movie' as type FROM posts 
    WHERE site_id = 6
    ORDER BY RAND()
    LIMIT 4
");
$randomMovies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$randomItems = array_merge($randomBooks, $randomMovies);
shuffle($randomItems);
$randomItems = array_slice($randomItems, 0, 6);

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as count FROM posts WHERE site_id = 7");
$totalBooks = $stmt->fetch()['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM posts WHERE site_id = 6");
$totalMovies = $stmt->fetch()['count'];

$stmt = $pdo->query("
    SELECT COUNT(*) as count FROM posts 
    WHERE site_id = 7 AND YEAR(publish_date) = {$currentYear}
");
$booksThisYear = $stmt->fetch()['count'];

$stmt = $pdo->query("
    SELECT COUNT(*) as count FROM posts 
    WHERE site_id = 6 AND YEAR(publish_date) = {$currentYear}
");
$moviesThisYear = $stmt->fetch()['count'];

// Helper function to extract movie/book ID from URL
function getItemId($url, $type) {
    if ($type === 'book') {
        // URL is already in format: review.php?id=12345
        if (preg_match('/id=(\d+)/', $url, $matches)) {
            return $matches[1];
        }
    } else {
        // Movie URL: https://letterboxd.com/thunt/film/movie-slug/
        if (preg_match('/\/film\/([^\/]+)/', $url, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

// Helper to get star rating
function getStars($title) {
    return substr_count($title, '★');
}

// Helper to clean title
function cleanTitle($title, $type) {
    if ($type === 'movie') {
        // Remove year and rating: "Movie Title, 2024 - ★★★" -> "Movie Title"
        $title = preg_replace('/, \d{4}.*$/', '', $title);
    } else {
        // Remove " by Author" and rating
        $title = preg_replace('/ by .*$/', '', $title);
        $title = preg_replace('/ - ★+$/', '', $title);
    }
    return trim($title);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MediaLog - Books & Movies</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            overflow-x: hidden;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1a1a1a;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }
        
        .top-nav {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 30px rgba(0,0,0,0.3);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }
        
        .nav-brand {
            font-size: 28px;
            font-weight: 800;
            color: #d4af37;
            padding: 20px 0;
            text-decoration: none;
            letter-spacing: 2px;
            background: linear-gradient(135deg, #d4af37, #f4d483);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 5px;
        }
        
        .nav-links a {
            padding: 20px 18px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid transparent;
        }
        
        .nav-links a:hover, .nav-links a.active {
            color: #d4af37;
            border-bottom-color: #d4af37;
        }
        
        .hero {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            padding: 60px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .hero h1 {
            font-size: 4em;
            color: white;
            margin-bottom: 15px;
            font-weight: 900;
            text-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .hero p {
            font-size: 1.3em;
            color: rgba(255,255,255,0.9);
            margin-bottom: 30px;
        }
        
        .hero-stats {
            display: flex;
            gap: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .hero-stat {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 20px 35px;
            border-radius: 15px;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .hero-stat-number {
            font-size: 2.5em;
            font-weight: 800;
            color: white;
        }
        
        .hero-stat-label {
            color: rgba(255,255,255,0.8);
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 1200px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .hero h1 {
                font-size: 2.5em;
            }
            
            .nav-brand {
                font-size: 20px;
            }
            
            .nav-links a {
                font-size: 11px;
                padding: 15px 12px;
            }
            
            .hero-stats {
                gap: 15px;
            }
            
            .hero-stat {
                padding: 15px 20px;
            }
            
            .hero-stat-number {
                font-size: 2em;
            }
            
            .grid-gallery {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }
        
        @media (max-width: 480px) {
            .hero h1 {
                font-size: 2em;
            }
            
            .hero p {
                font-size: 1.1em;
            }
            
            .nav-links {
                flex-wrap: wrap;
                gap: 0;
            }
            
            .nav-links a {
                font-size: 10px;
                padding: 12px 8px;
            }
            
            .container {
                padding: 20px 15px;
            }
            
            .card {
                padding: 20px;
            }
            
            .grid {
                gap: 20px;
            }
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.3);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title {
            font-size: 1.5em;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .card-icon {
            font-size: 2em;
        }
        
        .media-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .media-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }
        
        .media-poster {
            width: 60px;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }
        
        .media-info {
            flex: 1;
            min-width: 0;
        }
        
        .media-title {
            font-weight: 600;
            font-size: 1em;
            margin-bottom: 5px;
            color: #1a1a1a;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .media-meta {
            font-size: 0.85em;
            color: #666;
        }
        
        .media-stars {
            color: #d4af37;
            font-size: 0.9em;
            margin-top: 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        
        .badge-book {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-movie {
            background: #fce4ec;
            color: #c2185b;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .empty-state-icon {
            font-size: 3em;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        .grid-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .gallery-item {
            position: relative;
            aspect-ratio: 2/3;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gallery-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            padding: 20px 15px 15px;
            color: white;
        }
        
        .gallery-title {
            font-weight: 600;
            font-size: 0.9em;
            margin-bottom: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .gallery-stars {
            color: #d4af37;
            font-size: 0.85em;
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">MEDIALOG</a>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="books.php">Books</a></li>
                <li><a href="movies.php">Movies</a></li>
                <li><a href="authors.php">Authors</a></li>
                <li><a href="directors.php">Directors</a></li>
                <li><a href="stats.php">Statistics</a></li>
                <li><a href="insights.php">Insights</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="hero">
        <h1>📚 Welcome Back</h1>
        <p>Your Letterboxd + Goodreads tracker</p>
        <div class="hero-stats">
            <div class="hero-stat">
                <div class="hero-stat-number"><?= $totalBooks ?></div>
                <div class="hero-stat-label">Total Books</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-number"><?= $totalMovies ?></div>
                <div class="hero-stat-label">Total Movies</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-number"><?= $booksThisYear ?></div>
                <div class="hero-stat-label">Books (<?= $currentYear ?>)</div>
            </div>
            <div class="hero-stat">
                <div class="hero-stat-number"><?= $moviesThisYear ?></div>
                <div class="hero-stat-label">Movies (<?= $currentYear ?>)</div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="grid">
            <!-- Column 1: On This Day -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">📅 On This Day</h2>
                    <span class="card-icon">🎯</span>
                </div>
                
                <?php if (isset($onThisDayDate) && $onThisDayDate !== date('F j')): ?>
                    <div style="background: #f0f8ff; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9em; color: #666;">
                        <strong>Most recent:</strong> <?= $onThisDayDate ?>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($onThisDay)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🗓️</div>
                        <p>Nothing consumed on this date in previous years</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($onThisDay as $item): 
                        $itemId = getItemId($item['url'], $item['type']);
                        $stars = getStars($item['title']);
                        $cleanedTitle = cleanTitle($item['title'], $item['type']);
                        $year = date('Y', strtotime($item['publish_date']));
                        $link = $item['type'] === 'book' ? "review.php?id={$itemId}" : "movie.php?id={$itemId}";
                    ?>
                        <a href="<?= $link ?>" class="media-item">
                            <?php if ($item['image_url']): ?>
                                <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                     alt="<?= htmlspecialchars($cleanedTitle) ?>" 
                                     class="media-poster">
                            <?php endif; ?>
                            <div class="media-info">
                                <div class="media-title"><?= htmlspecialchars($cleanedTitle) ?></div>
                                <div class="media-meta"><?= $year ?></div>
                                <?php if ($stars > 0): ?>
                                    <div class="media-stars"><?= str_repeat('★', $stars) ?></div>
                                <?php endif; ?>
                                <span class="badge badge-<?= $item['type'] ?>"><?= $item['type'] ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Column 2: Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">⚡ Recent Activity</h2>
                    <span class="card-icon">🔥</span>
                </div>
                
                <?php 
                $recent = array_merge(
                    array_slice($recentBooks, 0, 3),
                    array_slice($recentMovies, 0, 3)
                );
                usort($recent, function($a, $b) {
                    return strtotime($b['publish_date']) - strtotime($a['publish_date']);
                });
                $recent = array_slice($recent, 0, 6);
                
                foreach ($recent as $item): 
                    $itemId = getItemId($item['url'], $item['type']);
                    $stars = getStars($item['title']);
                    $cleanedTitle = cleanTitle($item['title'], $item['type']);
                    $timeAgo = date('M j', strtotime($item['publish_date']));
                    $link = $item['type'] === 'book' ? "review.php?id={$itemId}" : "movie.php?id={$itemId}";
                ?>
                    <a href="<?= $link ?>" class="media-item">
                        <?php if ($item['image_url']): ?>
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($cleanedTitle) ?>" 
                                 class="media-poster">
                        <?php endif; ?>
                        <div class="media-info">
                            <div class="media-title"><?= htmlspecialchars($cleanedTitle) ?></div>
                            <div class="media-meta"><?= $timeAgo ?></div>
                            <?php if ($stars > 0): ?>
                                <div class="media-stars"><?= str_repeat('★', $stars) ?></div>
                            <?php endif; ?>
                            <span class="badge badge-<?= $item['type'] ?>"><?= $item['type'] ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Column 3: Random Discoveries -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">🎲 Random Picks</h2>
                    <span class="card-icon">✨</span>
                </div>
                
                <?php foreach ($randomItems as $item): 
                    $itemId = getItemId($item['url'], $item['type']);
                    $stars = getStars($item['title']);
                    $cleanedTitle = cleanTitle($item['title'], $item['type']);
                    $year = date('Y', strtotime($item['publish_date']));
                    $link = $item['type'] === 'book' ? "review.php?id={$itemId}" : "movie.php?id={$itemId}";
                ?>
                    <a href="<?= $link ?>" class="media-item">
                        <?php if ($item['image_url']): ?>
                            <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                                 alt="<?= htmlspecialchars($cleanedTitle) ?>" 
                                 class="media-poster">
                        <?php endif; ?>
                        <div class="media-info">
                            <div class="media-title"><?= htmlspecialchars($cleanedTitle) ?></div>
                            <div class="media-meta"><?= $year ?></div>
                            <?php if ($stars > 0): ?>
                                <div class="media-stars"><?= str_repeat('★', $stars) ?></div>
                            <?php endif; ?>
                            <span class="badge badge-<?= $item['type'] ?>"><?= $item['type'] ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Visual Gallery Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🎨 Visual Gallery</h2>
                <span class="card-icon">🖼️</span>
            </div>
            
            <div class="grid-gallery">
                <?php 
                // Mix of recent books and movies for gallery
                $gallery = array_merge(
                    array_slice($recentBooks, 0, 4),
                    array_slice($recentMovies, 0, 4)
                );
                shuffle($gallery);
                $gallery = array_slice($gallery, 0, 8);
                
                foreach ($gallery as $item): 
                    if (!$item['image_url']) continue;
                    $itemId = getItemId($item['url'], $item['type']);
                    $stars = getStars($item['title']);
                    $cleanedTitle = cleanTitle($item['title'], $item['type']);
                    $link = $item['type'] === 'book' ? "review.php?id={$itemId}" : "movie.php?id={$itemId}";
                ?>
                    <a href="<?= $link ?>" class="gallery-item">
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                             alt="<?= htmlspecialchars($cleanedTitle) ?>">
                        <div class="gallery-overlay">
                            <div class="gallery-title"><?= htmlspecialchars($cleanedTitle) ?></div>
                            <?php if ($stars > 0): ?>
                                <div class="gallery-stars"><?= str_repeat('★', $stars) ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
