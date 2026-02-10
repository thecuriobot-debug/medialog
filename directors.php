<?php
require_once 'config.php';

$pdo = getDB();

// Get current year with fallback logic
$currentYear = date('Y');
$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE site_id = 6 AND YEAR(publish_date) = {$currentYear}");
$currentYearCount = $stmt->fetch()['total'];
if ($currentYearCount == 0) {
    $currentYear = $currentYear - 1;
}

// Get total counts for footer
$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE site_id = 7");
$totalBooks = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE site_id = 6");
$totalMovies = $stmt->fetch()['total'];

// Get all movies with directors
$stmt = $pdo->query("
    SELECT director, title, publish_date, image_url, url, 
           SUBSTRING_INDEX(title, ',', 1) as movie_title
    FROM posts 
    WHERE site_id = 6 AND director IS NOT NULL AND director != ''
    ORDER BY publish_date DESC
");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by director - split multi-director films
$directors = [];
foreach ($movies as $movie) {
    $directorStr = trim($movie['director']);
    
    // Split by comma, but handle "et al" case
    $directorStr = preg_replace('/\s*et al\.?\s*$/i', '', $directorStr);
    $directorList = array_map('trim', explode(',', $directorStr));
    
    // Add movie to each director
    foreach ($directorList as $director) {
        if (empty($director)) continue;
        
        if (!isset($directors[$director])) {
            $directors[$director] = [
                'count' => 0,
                'movies' => [],
                'years' => []
            ];
        }
        $directors[$director]['count']++;
        $directors[$director]['movies'][] = $movie;
        
        $year = date('Y', strtotime($movie['publish_date']));
        if (!in_array($year, $directors[$director]['years'])) {
            $directors[$director]['years'][] = $year;
        }
    }
}

// Sort by count
uasort($directors, function($a, $b) {
    return $b['count'] - $a['count'];
});

$totalDirectors = count($directors);
$avgMoviesPerDirector = $totalDirectors > 0 ? round(array_sum(array_map(function($d) { return $d['count']; }, $directors)) / $totalDirectors, 1) : 0;
$mostWatchedDirector = !empty($directors) ? array_key_first($directors) : null;
$mostWatchedCount = $mostWatchedDirector ? $directors[$mostWatchedDirector]['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directors - MediaLog</title>
    <meta name="description" content="Director analytics: <?= $totalDirectors ?> directors tracked with viewing statistics and filmographies">
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1a1a1a;
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }
        
        /* Navigation */
        .top-nav {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(212, 175, 55, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
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
        
        .nav-links a:hover,
        .nav-links a.active {
            color: #d4af37;
            border-bottom-color: #d4af37;
        }
        
        /* Container */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Header */
        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .page-header h1 {
            font-size: 3em;
            color: white;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .page-header p {
            font-size: 1.2em;
            color: rgba(255,255,255,0.9);
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 50px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 800;
            color: #c2185b;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        /* Directors Grid */
        .directors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .director-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .director-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }
        
        .director-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .director-name {
            font-size: 1.5em;
            color: #c2185b;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .director-meta {
            display: flex;
            gap: 20px;
            font-size: 0.9em;
            color: #666;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .meta-item strong {
            color: #c2185b;
        }
        
        /* Movie Grid */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
        }
        
        .movie-poster {
            aspect-ratio: 2/3;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .movie-poster:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .empty-state h2 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 20px;
        }
        
        .empty-state p {
            color: #666;
            font-size: 1.1em;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .empty-state code {
            background: #f0f0f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-links a {
                font-size: 11px;
                padding: 15px 12px;
            }
            
            .page-header h1 {
                font-size: 2em;
            }
            
            .directors-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">MEDIALOG</a>
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="books.php">Books</a>
                <a href="movies.php">Movies</a>
                <a href="authors.php">Authors</a>
                <a href="directors.php" class="active">Directors</a>
                <a href="stats.php">Statistics</a>
                <a href="insights.php">Insights</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <h1>🎬 Directors</h1>
            <p>Exploring filmmakers and their works</p>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalDirectors ?></div>
                <div class="stat-label">Total Directors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalMovies ?></div>
                <div class="stat-label">Total Movies</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $avgMoviesPerDirector ?></div>
                <div class="stat-label">Avg per Director</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $mostWatchedCount ?></div>
                <div class="stat-label">Most Watched</div>
            </div>
        </div>
        
        <?php if (empty($directors)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <h2>No Directors Yet</h2>
                <p>Director data is extracted from Letterboxd metadata.</p>
                <p>To populate director information, run the metadata scraper:</p>
                <p style="margin-top: 20px;"><code>php scraper-final.php</code></p>
            </div>
        <?php else: ?>
            <!-- Directors Grid -->
            <div class="directors-grid">
                <?php foreach ($directors as $directorName => $data): ?>
                    <div class="director-card">
                        <div class="director-header">
                            <div class="director-name"><?= htmlspecialchars($directorName) ?></div>
                            <div class="director-meta">
                                <div class="meta-item">
                                    <span>🎬</span>
                                    <strong><?= $data['count'] ?></strong>
                                    <span><?= $data['count'] == 1 ? 'film' : 'films' ?></span>
                                </div>
                                <div class="meta-item">
                                    <span>📅</span>
                                    <span><?= implode(', ', array_unique($data['years'])) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="movies-grid">
                            <?php foreach (array_slice($data['movies'], 0, 12) as $movie): ?>
                                <a href="<?= htmlspecialchars($movie['url']) ?>" class="movie-poster" target="_blank" title="<?= htmlspecialchars($movie['movie_title']) ?>">
                                    <?php if (!empty($movie['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($movie['image_url']) ?>" alt="<?= htmlspecialchars($movie['movie_title']) ?>">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 2em;">🎬</div>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
