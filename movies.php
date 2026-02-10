<?php
require_once 'config.php';
$pdo = getDB();

// Get sort and filter parameters
$sort = $_GET['sort'] ?? 'date_desc';
$rating = $_GET['rating'] ?? 'all';
$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? 'all';
$genre = $_GET['genre'] ?? 'all';

// Build query
$where = "site_id = 6"; // Letterboxd only

if ($rating !== 'all') {
    $where .= " AND title LIKE '%{$rating}%'"; // Stars are in title
}

if ($year !== 'all') {
    $where .= " AND YEAR(publish_date) = '{$year}'";
}

if ($genre !== 'all') {
    $where .= " AND genres LIKE :genre";
}

if ($search) {
    $where .= " AND (title LIKE :search OR director LIKE :search OR description LIKE :search)";
}

// Sort options
$orderBy = match($sort) {
    'date_desc' => 'publish_date DESC',
    'date_asc' => 'publish_date ASC',
    'title' => 'title ASC',
    'rating_desc' => 'title DESC',
    default => 'publish_date DESC'
};

$stmt = $pdo->prepare("
    SELECT *
    FROM posts 
    WHERE $where
    ORDER BY $orderBy
");

$params = [];
if ($search) $params['search'] = "%$search%";
if ($genre !== 'all') $params['genre'] = "%$genre%";

$stmt->execute($params);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($movies);

// Get available years
$yearsStmt = $pdo->query("
    SELECT DISTINCT YEAR(publish_date) as year 
    FROM posts 
    WHERE site_id = 6 
    ORDER BY year DESC
");
$years = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);

// Get available genres
$genresStmt = $pdo->query("
    SELECT DISTINCT genres 
    FROM posts 
    WHERE site_id = 6 AND genres IS NOT NULL AND genres != ''
");
$allGenres = [];
foreach ($genresStmt->fetchAll(PDO::FETCH_COLUMN) as $genreList) {
    $genreArray = array_map('trim', explode(',', $genreList));
    $allGenres = array_merge($allGenres, $genreArray);
}
$allGenres = array_unique(array_filter($allGenres));
sort($allGenres);

// Get stats
$statsStmt = $pdo->query("SELECT COUNT(*) as count FROM posts WHERE site_id = 6");
$totalAllMovies = $statsStmt->fetch()['count'];

$currentYear = date('Y');
$thisYearStmt = $pdo->query("
    SELECT COUNT(*) as count FROM posts 
    WHERE site_id = 6 AND YEAR(publish_date) = {$currentYear}
");
$moviesThisYear = $thisYearStmt->fetch()['count'];

// Helper functions
function getStars($title) {
    return substr_count($title, '★');
}

function cleanTitle($title) {
    $title = preg_replace('/, \d{4}.*$/', '', $title);
    return trim($title);
}

function getMovieSlug($url) {
    if (preg_match('/\/film\/([^\/]+)/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}

$pageTitle = "Movies";
$pageStyles = "
    /* Movies Page Styles */
    .page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        font-size: 3em;
        color: white;
        margin-bottom: 15px;
        text-shadow: 0 2px 20px rgba(0,0,0,0.3);
    }
    
    .page-header p {
        font-size: 1.2em;
        color: rgba(255,255,255,0.9);
    }
    
    .item-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-radius: 12px 12px 0 0;
    }
    
    .item-card {
        transition: all 0.3s ease;
    }
    
    .item-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.2);
    }
";
include 'includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>🎬 Movies Collection</h1>
        <p>Your viewing journey from Letterboxd</p>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalAllMovies; ?></div>
            <div class="stat-label">Total Movies</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $moviesThisYear; ?></div>
            <div class="stat-label"><?php echo $currentYear; ?> Movies</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total; ?></div>
            <div class="stat-label">Showing</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo count($years); ?></div>
            <div class="stat-label">Years</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card" style="margin-bottom: 30px;">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #666;">Search:</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Title or director..." 
                       style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #666;">Year:</label>
                <select name="year" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                    <option value="all">All Years</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #666;">Genre:</label>
                <select name="genre" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                    <option value="all">All Genres</option>
                    <?php foreach ($allGenres as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo $genre == $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #666;">Rating:</label>
                <select name="rating" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                    <option value="all">All Ratings</option>
                    <option value="★★★★★" <?php echo $rating == '★★★★★' ? 'selected' : ''; ?>>★★★★★ 5 stars</option>
                    <option value="★★★★" <?php echo $rating == '★★★★' ? 'selected' : ''; ?>>★★★★ 4 stars</option>
                    <option value="★★★" <?php echo $rating == '★★★' ? 'selected' : ''; ?>>★★★ 3 stars</option>
                    <option value="★★" <?php echo $rating == '★★' ? 'selected' : ''; ?>>★★ 2 stars</option>
                    <option value="★" <?php echo $rating == '★' ? 'selected' : ''; ?>>★ 1 star</option>
                </select>
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #666;">Sort:</label>
                <select name="sort" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                    <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="date_asc" <?php echo $sort == 'date_asc' ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="title" <?php echo $sort == 'title' ? 'selected' : ''; ?>>Title A-Z</option>
                    <option value="rating_desc" <?php echo $sort == 'rating_desc' ? 'selected' : ''; ?>>Highest Rated</option>
                </select>
            </div>
            
            <div style="display: flex; align-items: flex-end;">
                <button type="submit" style="width: 100%; padding: 10px 20px; background: linear-gradient(135deg, #c2185b, #e91e63); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
    
    <!-- Movies Grid -->
    <?php if (empty($movies)): ?>
        <div class="card" style="text-align: center; padding: 60px 30px;">
            <div style="font-size: 4em; margin-bottom: 20px;">🎬</div>
            <h2 style="color: #666; margin-bottom: 10px;">No movies found</h2>
            <p style="color: #999;">Try adjusting your filters or search terms</p>
        </div>
    <?php else: ?>
        <div class="item-grid">
            <?php foreach ($movies as $movie): 
                $slug = getMovieSlug($movie['url']);
                $title = cleanTitle($movie['title']);
                $stars = getStars($movie['title']);
                $director = $movie['director'] ?? 'Unknown';
                $date = date('M j, Y', strtotime($movie['publish_date']));
                $genres = $movie['genres'] ? explode(',', $movie['genres']) : [];
            ?>
                <a href="movie.php?slug=<?php echo $slug; ?>" class="item-card">
                    <?php if ($movie['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($movie['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($title); ?>" 
                             class="item-image"
                             onerror="this.src='https://via.placeholder.com/300x400/c2185b/ffffff?text=No+Poster'">
                    <?php else: ?>
                        <div class="item-image" style="background: linear-gradient(135deg, #c2185b, #e91e63); display: flex; align-items: center; justify-content: center; color: white; font-size: 3em;">
                            🎬
                        </div>
                    <?php endif; ?>
                    
                    <div class="item-content">
                        <h3 class="item-title"><?php echo htmlspecialchars($title); ?></h3>
                        <p class="item-meta">
                            <strong>Directed by <?php echo htmlspecialchars($director); ?></strong><br>
                            <?php if ($stars > 0): ?>
                                <span style="color: #d4af37; font-size: 1.1em;"><?php echo str_repeat('★', $stars); ?></span><br>
                            <?php endif; ?>
                            <span style="color: #999;">Watched: <?php echo $date; ?></span>
                        </p>
                        <?php if (!empty($genres)): ?>
                            <div style="margin-top: 10px; display: flex; flex-wrap: wrap; gap: 5px;">
                                <?php foreach (array_slice($genres, 0, 3) as $g): ?>
                                    <span class="badge" style="background: #e0e0e0; color: #666; font-size: 0.75em;">
                                        <?php echo trim($g); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
