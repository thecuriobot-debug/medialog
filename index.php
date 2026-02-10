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

// Get reviews count (items with content/reviews)
$stmt = $pdo->query("
    SELECT COUNT(*) as count FROM posts 
    WHERE site_id = 7 AND (full_content IS NOT NULL AND full_content != '')
");
$booksWithReviews = $stmt->fetch()['count'];

$stmt = $pdo->query("
    SELECT COUNT(*) as count FROM posts 
    WHERE site_id = 6 AND (description IS NOT NULL AND description != '' AND LENGTH(description) > 100)
");
$moviesWithReviews = $stmt->fetch()['count'];

$totalReviews = $booksWithReviews + $moviesWithReviews;

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
<?php
$pageTitle = "Dashboard";
$pageStyles = "
    /* Page-specific styles */
    h1 {
        font-size: 3em;
        color: white;
        margin-bottom: 15px;
        text-shadow: 0 2px 20px rgba(0,0,0,0.3);
        text-align: center;
    }
    
    h2 {
        font-size: 1.8em;
        color: white;
        margin: 50px 0 25px 0;
        text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        text-align: center;
        padding: 20px;
        background: rgba(255,255,255,0.1);
        border-radius: 12px;
        backdrop-filter: blur(10px);
    }
    
    /* Hero Section - Index Page Only */
    .hero {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.2), rgba(118, 75, 162, 0.2));
        backdrop-filter: blur(20px);
        padding: 60px 30px;
        margin: 0 auto 40px;
        max-width: 1400px;
        border-radius: 20px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }
    
    .hero h1 {
        font-size: 3em;
        font-weight: 800;
        color: white;
        margin-bottom: 15px;
        text-shadow: 0 2px 20px rgba(0,0,0,0.3);
    }
    
    .hero p {
        font-size: 1.2em;
        color: rgba(255,255,255,0.95);
        margin-bottom: 30px;
    }
    
    .hero-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 20px;
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .hero-stat {
        background: rgba(255,255,255,0.15);
        padding: 25px;
        border-radius: 15px;
        backdrop-filter: blur(10px);
    }
    
    .hero-stat-number {
        font-size: 2.5em;
        font-weight: 800;
        color: #d4af37;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    
    .hero-stat-label {
        font-size: 0.9em;
        color: rgba(255,255,255,0.9);
        margin-top: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    /* Section boxes - white backgrounds */
    .section-box {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .section-box h3 {
        color: #1a1a1a;
        margin-bottom: 20px;
        font-size: 1.3em;
    }
    
    /* Index-specific styles */
    .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }
    
    .media-item {
        display: flex;
        gap: 15px;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 15px;
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
        height: 85px;
        object-fit: cover;
        border-radius: 6px;
        flex-shrink: 0;
    }
    
    .media-info {
        flex: 1;
    }
    
    .media-title {
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 5px;
        font-size: 0.95em;
    }
    
    .media-meta {
        font-size: 0.85em;
        color: #666;
        margin-bottom: 5px;
    }
    
    .media-stars {
        color: #d4af37;
        font-size: 0.85em;
    }
    
    @media (max-width: 768px) {
        h1 {
            font-size: 2em;
        }
        
        h2 {
            font-size: 1.5em;
            margin: 30px 0 20px 0;
        }
        
        .hero h1 {
            font-size: 2em;
        }
        
        .hero p {
            font-size: 1em;
        }
        
        .grid {
            grid-template-columns: 1fr;
        }
    }
";
include 'includes/header.php';
?>

<div class="hero">
        <h1>📚 Welcome to MediaLog</h1>
        <p>Your complete media tracking system • <?= $totalBooks ?> Books • <?= $totalMovies ?> Movies • <?= $totalReviews ?> Reviews</p>
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
                <div class="hero-stat-number"><?= $totalReviews ?></div>
                <div class="hero-stat-label">Reviews Written</div>
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
    
    <!-- What's New Banner -->
    <div class="container" style="margin-top: -30px; margin-bottom: 30px;">
        <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; max-width: 1400px; margin: 0 auto;">
            <div style="padding: 40px 30px;">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px;">
                    <div style="font-size: 3em;">✨</div>
                    <div>
                        <h2 style="color: white; margin: 0; font-size: 2em; font-weight: 800;">What's New in MediaLog</h2>
                        <p style="margin: 8px 0 0 0; opacity: 0.9; font-size: 1.1em;">Recent updates & improvements</p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 25px;">
                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px; border-radius: 10px;">
                        <div style="font-size: 2em; margin-bottom: 10px;">📝</div>
                        <div style="font-weight: 700; margin-bottom: 8px; font-size: 1.1em;">Book Reviews</div>
                        <div style="opacity: 0.9; font-size: 0.95em;">Review badges, snippets & filter - see which books you've reviewed</div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px; border-radius: 10px;">
                        <div style="font-size: 2em; margin-bottom: 10px;">🎬</div>
                        <div style="font-weight: 700; margin-bottom: 8px; font-size: 1.1em;">1,708 Movies</div>
                        <div style="opacity: 0.9; font-size: 0.95em;">Complete 14-year Letterboxd history imported (2011-2025)</div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px; border-radius: 10px;">
                        <div style="font-size: 2em; margin-bottom: 10px;">📊</div>
                        <div style="font-weight: 700; margin-bottom: 8px; font-size: 1.1em;">Combined Insights</div>
                        <div style="opacity: 0.9; font-size: 0.95em;">New analytics: days with both, genre/director rankings, decade analysis</div>
                    </div>
                    
                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px; border-radius: 10px;">
                        <div style="font-size: 2em; margin-bottom: 10px;">🎨</div>
                        <div style="font-weight: 700; margin-bottom: 8px; font-size: 1.1em;">Modern Design</div>
                        <div style="opacity: 0.9; font-size: 0.95em;">Purple gradients, gold accents, enhanced cards across all pages</div>
                    </div>
                </div>
                
                <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.2); display: flex; gap: 15px; flex-wrap: wrap;">
                    <a href="books.php" style="background: rgba(255,255,255,0.25); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: 1.05em;">
                        <span>📚</span> Browse Books
                    </a>
                    <a href="movies.php" style="background: rgba(255,255,255,0.25); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: 1.05em;">
                        <span>🎬</span> Browse Movies
                    </a>
                    <a href="reviews.php" style="background: rgba(255,255,255,0.25); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: 1.05em;">
                        <span>📝</span> Browse Reviews
                    </a>
                    <a href="authors.php" style="background: rgba(255,255,255,0.25); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: 1.05em;">
                        <span>✍️</span> Browse Authors
                    </a>
                    <a href="directors.php" style="background: rgba(255,255,255,0.25); color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; font-size: 1.05em;">
                        <span>🎥</span> Browse Directors
                    </a>
                </div>
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
    </div>
</body>
</html>
