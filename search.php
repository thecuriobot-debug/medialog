<?php
require_once 'config.php';
$pdo = getDB();

$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all'; // all, books, movies
$minRating = $_GET['min_rating'] ?? 0;

$results = [];
$bookCount = 0;
$movieCount = 0;

if ($query) {
    // Search books
    if ($type === 'all' || $type === 'books') {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                title,
                url,
                image_url,
                publish_date,
                description,
                full_content,
                'book' as media_type
            FROM posts 
            WHERE site_id = 7
            AND (
                title LIKE :search 
                OR description LIKE :search 
                OR full_content LIKE :search
            )
            ORDER BY publish_date DESC
            LIMIT 50
        ");
        $searchParam = "%{$query}%";
        $stmt->execute(['search' => $searchParam]);
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter by rating if specified
        if ($minRating > 0) {
            $books = array_filter($books, function($book) use ($minRating) {
                return substr_count($book['title'], '★') >= $minRating;
            });
        }
        
        $results = array_merge($results, $books);
        $bookCount = count($books);
    }
    
    // Search movies
    if ($type === 'all' || $type === 'movies') {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                title,
                url,
                image_url,
                publish_date,
                description,
                director,
                genres,
                'movie' as media_type
            FROM posts 
            WHERE site_id = 6
            AND (
                title LIKE :search 
                OR director LIKE :search 
                OR genres LIKE :search
                OR description LIKE :search
            )
            ORDER BY publish_date DESC
            LIMIT 50
        ");
        $searchParam = "%{$query}%";
        $stmt->execute(['search' => $searchParam]);
        $movies = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filter by rating if specified
        if ($minRating > 0) {
            $movies = array_filter($movies, function($movie) use ($minRating) {
                return substr_count($movie['title'], '★') >= $minRating;
            });
        }
        
        $results = array_merge($results, $movies);
        $movieCount = count($movies);
    }
    
    // Sort all results by date
    usort($results, function($a, $b) {
        return strtotime($b['publish_date']) - strtotime($a['publish_date']);
    });
}

// Helper functions
function cleanTitle($title) {
    $title = preg_replace('/ by .*$/', '', $title);
    $title = preg_replace('/, \d{4} - ★+$/', '', $title);
    $title = preg_replace('/ - ★+$/', '', $title);
    return trim($title);
}

function getStars($title) {
    return substr_count($title, '★');
}

function getItemLink($item) {
    if ($item['media_type'] === 'book') {
        if (preg_match('/id=(\d+)/', $item['url'], $matches)) {
            return "review.php?id={$matches[1]}";
        }
    } else {
        if (preg_match('/\/film\/([^\/]+)/', $item['url'], $matches)) {
            return "movie.php?slug={$matches[1]}";
        }
    }
    return '#';
}

$pageTitle = "Search";
include 'includes/header.php';
?>

<style>
    .search-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 60px 30px;
        text-align: center;
        color: white;
        margin-bottom: 40px;
    }
    
    .search-hero h1 {
        font-size: 3em;
        margin-bottom: 15px;
        text-shadow: 0 2px 20px rgba(0,0,0,0.3);
    }
    
    .search-box-large {
        max-width: 700px;
        margin: 30px auto 0;
        position: relative;
    }
    
    .search-box-large input {
        width: 100%;
        padding: 20px 60px 20px 25px;
        font-size: 1.2em;
        border: none;
        border-radius: 50px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        outline: none;
    }
    
    .search-box-large button {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        cursor: pointer;
        font-size: 1em;
    }
    
    .search-filters {
        max-width: 700px;
        margin: 20px auto;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .filter-btn {
        padding: 8px 20px;
        border-radius: 20px;
        border: 2px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.1);
        color: white;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .filter-btn:hover, .filter-btn.active {
        background: rgba(255,255,255,0.3);
        border-color: rgba(255,255,255,0.6);
    }
    
    .search-results {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 30px 40px;
    }
    
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .results-count {
        font-size: 1.5em;
        font-weight: 700;
        color: #1a1a1a;
    }
    
    .results-breakdown {
        display: flex;
        gap: 20px;
        font-size: 0.95em;
        color: #666;
    }
    
    .result-item {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        display: flex;
        gap: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
    }
    
    .result-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .result-cover {
        width: 100px;
        height: 140px;
        object-fit: cover;
        border-radius: 8px;
        flex-shrink: 0;
    }
    
    .result-content {
        flex: 1;
    }
    
    .result-title {
        font-size: 1.3em;
        font-weight: 700;
        margin-bottom: 8px;
        color: #1a1a1a;
    }
    
    .result-meta {
        display: flex;
        gap: 15px;
        margin-bottom: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    
    .result-stars {
        color: #d4af37;
        font-size: 1.1em;
    }
    
    .result-badge {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.85em;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-book {
        background: #e3f2fd;
        color: #1976d2;
    }
    
    .badge-movie {
        background: #fce4ec;
        color: #c2185b;
    }
    
    .result-excerpt {
        color: #666;
        line-height: 1.6;
        margin-top: 10px;
    }
    
    .no-results {
        text-align: center;
        padding: 80px 20px;
    }
    
    .no-results-icon {
        font-size: 5em;
        margin-bottom: 20px;
        opacity: 0.5;
    }
</style>

<div class="search-hero">
    <h1>🔍 Search MediaLog</h1>
    <p style="font-size: 1.2em; opacity: 0.95; margin-bottom: 10px;">Search across 782 books and 1,708 movies</p>
    
    <form method="GET" class="search-box-large">
        <input type="text" 
               name="q" 
               value="<?= htmlspecialchars($query) ?>" 
               placeholder="Search titles, authors, directors, genres..."
               autofocus
               required>
        <button type="submit">Search</button>
    </form>
    
    <div class="search-filters">
        <a href="?q=<?= urlencode($query) ?>&type=all" class="filter-btn <?= $type === 'all' ? 'active' : '' ?>">
            📚🎬 All Media
        </a>
        <a href="?q=<?= urlencode($query) ?>&type=books" class="filter-btn <?= $type === 'books' ? 'active' : '' ?>">
            📚 Books Only
        </a>
        <a href="?q=<?= urlencode($query) ?>&type=movies" class="filter-btn <?= $type === 'movies' ? 'active' : '' ?>">
            🎬 Movies Only
        </a>
        <a href="?q=<?= urlencode($query) ?>&type=<?= $type ?>&min_rating=4" class="filter-btn <?= $minRating == 4 ? 'active' : '' ?>">
            ⭐ 4+ Stars
        </a>
    </div>
</div>

<div class="search-results">
    <?php if ($query && count($results) > 0): ?>
        <div class="results-header">
            <div class="results-count">
                <?= count($results) ?> Results
            </div>
            <div class="results-breakdown">
                <?php if ($bookCount > 0): ?>
                    <span>📚 <?= $bookCount ?> Books</span>
                <?php endif; ?>
                <?php if ($movieCount > 0): ?>
                    <span>🎬 <?= $movieCount ?> Movies</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php foreach ($results as $item): 
            $title = cleanTitle($item['title']);
            $stars = getStars($item['title']);
            $link = getItemLink($item);
            $date = date('M j, Y', strtotime($item['publish_date']));
        ?>
            <a href="<?= $link ?>" class="result-item">
                <?php if ($item['image_url']): ?>
                    <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                         alt="<?= htmlspecialchars($title) ?>" 
                         class="result-cover"
                         onerror="this.style.display='none'">
                <?php endif; ?>
                
                <div class="result-content">
                    <div class="result-title"><?= htmlspecialchars($title) ?></div>
                    
                    <div class="result-meta">
                        <span class="result-badge badge-<?= $item['media_type'] ?>">
                            <?= $item['media_type'] ?>
                        </span>
                        
                        <?php if ($stars > 0): ?>
                            <span class="result-stars"><?= str_repeat('★', $stars) ?></span>
                        <?php endif; ?>
                        
                        <span style="color: #999;"><?= $date ?></span>
                        
                        <?php if ($item['media_type'] === 'movie' && !empty($item['director'])): ?>
                            <span style="color: #666;">Dir: <?= htmlspecialchars($item['director']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($item['description'])): ?>
                        <div class="result-excerpt">
                            <?= htmlspecialchars(mb_substr(strip_tags($item['description']), 0, 200)) ?>...
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($item['media_type'] === 'movie' && !empty($item['genres'])): ?>
                        <div style="margin-top: 10px;">
                            <?php foreach (array_slice(explode(',', $item['genres']), 0, 4) as $genre): ?>
                                <span style="display: inline-block; padding: 3px 10px; background: #f0f0f0; border-radius: 10px; font-size: 0.85em; margin-right: 5px; margin-top: 5px;">
                                    <?= trim($genre) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
        
    <?php elseif ($query): ?>
        <div class="no-results">
            <div class="no-results-icon">🔍</div>
            <h2 style="color: #666; margin-bottom: 10px;">No results found for "<?= htmlspecialchars($query) ?>"</h2>
            <p style="color: #999;">Try different keywords or browse all <a href="books.php" style="color: #1976d2;">Books</a> or <a href="movies.php" style="color: #c2185b;">Movies</a></p>
        </div>
    <?php else: ?>
        <div class="no-results">
            <div class="no-results-icon">📚🎬</div>
            <h2 style="color: #666; margin-bottom: 10px;">Start searching!</h2>
            <p style="color: #999;">Search across 2,490 media items</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
