<?php
require_once 'config.php';
$pdo = getDB();

function getStars($title) {
    return substr_count($title, '★');
}

function cleanTitle($title) {
    return preg_replace('/★+/', '', trim($title));
}

// Get user's top-rated items
$topBooks = $pdo->query("
    SELECT * FROM posts 
    WHERE site_id = 7 
    ORDER BY publish_date DESC 
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

$topMovies = $pdo->query("
    SELECT * FROM posts 
    WHERE site_id = 6 
    ORDER BY publish_date DESC 
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

// Filter for high ratings
$highRatedBooks = array_filter($topBooks, function($b) {
    return getStars($b['title']) >= 4;
});

$highRatedMovies = array_filter($topMovies, function($m) {
    return getStars($m['title']) >= 4;
});

// Get genre preferences from movies
$genreFrequency = [];
foreach ($highRatedMovies as $movie) {
    if ($movie['genres']) {
        $genres = explode(',', $movie['genres']);
        foreach ($genres as $genre) {
            $genre = trim($genre);
            $genreFrequency[$genre] = ($genreFrequency[$genre] ?? 0) + 1;
        }
    }
}
arsort($genreFrequency);
$topGenres = array_slice(array_keys($genreFrequency), 0, 5);

// Get director preferences
$directorFrequency = [];
foreach ($highRatedMovies as $movie) {
    if ($movie['director']) {
        $director = trim($movie['director']);
        $directorFrequency[$director] = ($directorFrequency[$director] ?? 0) + 1;
    }
}
arsort($directorFrequency);
$topDirectors = array_slice(array_keys($directorFrequency), 0, 5);

$pageTitle = "Recommendations";
$pageStyles = "
    .rec-section {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    .rec-section h2 {
        color: #1a1a1a;
        margin-bottom: 20px;
    }
    .tag-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .tag {
        padding: 8px 16px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9em;
    }
    .insights-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .insight-card {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        text-align: center;
    }
    .insight-number {
        font-size: 2.5em;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 5px;
    }
    .insight-label {
        color: #666;
        font-size: 0.9em;
    }
    .rec-list {
        margin-top: 15px;
    }
    .rec-item {
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-bottom: 10px;
    }
";
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">🎯 Recommendations</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Based on your taste</p>
    </div>
    
    <div class="rec-section">
        <h2>📊 Your Taste Profile</h2>
        <div class="insights-grid">
            <div class="insight-card">
                <div class="insight-number"><?= count($highRatedBooks) ?></div>
                <div class="insight-label">High-Rated Books</div>
            </div>
            <div class="insight-card">
                <div class="insight-number"><?= count($highRatedMovies) ?></div>
                <div class="insight-label">High-Rated Movies</div>
            </div>
            <div class="insight-card">
                <div class="insight-number"><?= count($topGenres) ?></div>
                <div class="insight-label">Favorite Genres</div>
            </div>
            <div class="insight-card">
                <div class="insight-number"><?= count($topDirectors) ?></div>
                <div class="insight-label">Favorite Directors</div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($topGenres)): ?>
    <div class="rec-section">
        <h2>🎬 Your Favorite Genres</h2>
        <div class="tag-cloud">
            <?php foreach ($topGenres as $genre): ?>
                <span class="tag"><?= htmlspecialchars($genre) ?></span>
            <?php endforeach; ?>
        </div>
        <p style="margin-top: 15px; color: #666;">Based on your highest-rated movies</p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($topDirectors)): ?>
    <div class="rec-section">
        <h2>🎥 Directors You Love</h2>
        <div class="rec-list">
            <?php foreach ($topDirectors as $director): ?>
                <div class="rec-item">
                    <strong><?= htmlspecialchars($director) ?></strong>
                    <span style="color: #666; margin-left: 10px;">
                        (<?= $directorFrequency[$director] ?> highly rated)
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="rec-section">
        <h2>💡 Recommendation Tips</h2>
        <div style="padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
            <p style="margin: 5px 0; color: #666;">
                <strong>Based on your preferences:</strong>
            </p>
            <ul style="margin: 10px 0 0 20px; color: #666;">
                <?php if (!empty($topGenres)): ?>
                    <li>Look for more <?= implode(', ', array_slice($topGenres, 0, 3)) ?> content</li>
                <?php endif; ?>
                <?php if (!empty($topDirectors)): ?>
                    <li>Explore more films by <?= implode(', ', array_slice($topDirectors, 0, 2)) ?></li>
                <?php endif; ?>
                <li>Your average high rating is 4+ stars - you have great taste!</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
