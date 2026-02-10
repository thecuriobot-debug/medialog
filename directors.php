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

$pageTitle = "Directors";
include 'includes/header.php';
?>

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
                            <?php foreach (array_slice($data['movies'], 0, 11) as $movie): ?>
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
