<?php
require_once 'config.php';

$pdo = getDB();

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
$totalMovies = count($movies);

// Get director with most movies
$topDirector = array_key_first($directors);
$topDirectorCount = $directors[$topDirector]['count'] ?? 0;

// Helper to extract movie ID from URL
function getMovieId($url) {
    if (preg_match('/\/film\/([^\/]+)/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediaLog - Directors</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1a1a1a;
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        h1 {
            font-size: 3em;
            color: white;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .subtitle {
            color: rgba(255,255,255,0.9);
            margin-bottom: 40px;
            font-size: 1.2em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.95);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: bold;
            color: #d4af37;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1em;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .directors-grid {
            display: grid;
            gap: 25px;
        }
        
        .director-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .director-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .director-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .director-name {
            font-size: 1.8em;
            font-weight: 700;
            color: #1a1a1a;
        }
        
        .director-count {
            font-size: 1.5em;
            font-weight: 700;
            color: #d4af37;
        }
        
        .director-meta {
            color: #666;
            margin-bottom: 20px;
            font-size: 0.95em;
        }
        
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
        }
        
        .movie-poster {
            position: relative;
            aspect-ratio: 2/3;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .movie-poster:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .movie-poster a {
            display: block;
            width: 100%;
            height: 100%;
            text-decoration: none;
        }
        
        .empty-state {
            background: rgba(255,255,255,0.95);
            padding: 60px;
            border-radius: 15px;
            text-align: center;
            color: #666;
        }
        
        .empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2em;
            }
            
            .director-name {
                font-size: 1.3em;
            }
            
            .movies-grid {
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
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
                <li><a href="directors.php" class="active">Directors</a></li>
                <li><a href="stats.php">Statistics</a></li>
                <li><a href="insights.php">Insights</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <h1>🎬 Directors</h1>
        <div class="subtitle">Your favorite filmmakers</div>
        
        <?php if (empty($directors)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎥</div>
                <h2>No Director Data Yet</h2>
                <p>Run the metadata scraper to populate director information</p>
                <p style="margin-top: 15px; font-family: monospace;">php fetch-movie-metadata.php</p>
            </div>
        <?php else: ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalDirectors ?></div>
                    <div class="stat-label">Directors</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $totalMovies ?></div>
                    <div class="stat-label">Movies</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($totalMovies / $totalDirectors, 1) ?></div>
                    <div class="stat-label">Avg per Director</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $topDirectorCount ?></div>
                    <div class="stat-label">Most Watched</div>
                </div>
            </div>
            
            <div class="directors-grid">
                <?php foreach ($directors as $directorName => $data): ?>
                    <div class="director-card">
                        <div class="director-header">
                            <div class="director-name"><?= htmlspecialchars($directorName) ?></div>
                            <div class="director-count"><?= $data['count'] ?> <?= $data['count'] == 1 ? 'film' : 'films' ?></div>
                        </div>
                        
                        <div class="director-meta">
                            Watched across <?= count($data['years']) ?> <?= count($data['years']) == 1 ? 'year' : 'years' ?>
                            (<?= implode(', ', $data['years']) ?>)
                        </div>
                        
                        <div class="movies-grid">
                            <?php foreach ($data['movies'] as $movie): 
                                $movieId = getMovieId($movie['url']);
                            ?>
                                <?php if ($movie['image_url']): ?>
                                    <div class="movie-poster">
                                        <a href="movie.php?id=<?= urlencode($movieId) ?>" 
                                           title="<?= htmlspecialchars($movie['movie_title']) ?>">
                                            <img src="<?= htmlspecialchars($movie['image_url']) ?>" 
                                                 alt="<?= htmlspecialchars($movie['movie_title']) ?>">
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
