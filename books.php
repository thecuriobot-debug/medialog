<?php
require_once 'config.php';
$pdo = getDB();

// Get sort and filter parameters
$sort = $_GET['sort'] ?? 'date_desc';
$rating = $_GET['rating'] ?? 'all';
$search = $_GET['search'] ?? '';
$year = $_GET['year'] ?? 'all';
$hasReview = $_GET['has_review'] ?? 'all';

// Build query
$where = "site_id = 7"; // Goodreads only

if ($rating !== 'all') {
    $where .= " AND title LIKE '%{$rating}%'"; // Stars are in title
}

if ($year !== 'all') {
    $where .= " AND YEAR(publish_date) = '{$year}'";
}

if ($hasReview === 'yes') {
    $where .= " AND full_content IS NOT NULL AND LENGTH(full_content) > 100";
} elseif ($hasReview === 'no') {
    $where .= " AND (full_content IS NULL OR LENGTH(full_content) <= 100)";
}

if ($search) {
    $where .= " AND (title LIKE :search OR author LIKE :search OR full_content LIKE :search)";
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
    SELECT *, 
           SUBSTRING_INDEX(SUBSTRING_INDEX(title, ' by ', -1), ' - ', 1) as extracted_author
    FROM posts 
    WHERE $where
    ORDER BY $orderBy
");

if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($books);

// Get available years
$yearsStmt = $pdo->query("
    SELECT DISTINCT YEAR(publish_date) as year 
    FROM posts 
    WHERE site_id = 7 
    ORDER BY year DESC
");
$years = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);

// Get stats
$statsStmt = $pdo->query("SELECT COUNT(*) as count FROM posts WHERE site_id = 7");
$totalAllBooks = $statsStmt->fetch()['count'];

// Count books with reviews
$reviewStmt = $pdo->query("
    SELECT COUNT(*) as count FROM posts 
    WHERE site_id = 7 AND full_content IS NOT NULL AND LENGTH(full_content) > 100
");
$booksWithReviews = $reviewStmt->fetch()['count'];

$currentYear = date('Y');
$thisYearStmt = $pdo->query("
    SELECT COUNT(*) as count FROM posts 
    WHERE site_id = 7 AND YEAR(publish_date) = {$currentYear}
");
$booksThisYear = $thisYearStmt->fetch()['count'];

// Helper functions
function getStars($title) {
    return substr_count($title, '★');
}

function cleanTitle($title) {
    $title = preg_replace('/ by .*$/', '', $title);
    $title = preg_replace('/ - ★+$/', '', $title);
    return trim($title);
}

function getItemId($url) {
    if (preg_match('/id=(\d+)/', $url, $matches)) {
        return $matches[1];
    }
    return null;
}

$pageTitle = "Books";
include 'includes/header.php';
?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>📚 Books Collection</h1>
        <p>Your reading journey from Goodreads</p>
    </div>
    
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $totalAllBooks; ?></div>
            <div class="stat-label">Total Books</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total; ?></div>
            <div class="stat-label">Showing</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $booksWithReviews; ?></div>
            <div class="stat-label">With Reviews</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $booksThisYear; ?></div>
            <div class="stat-label"><?php echo $currentYear; ?> Books</div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card" style="margin-bottom: 30px;">
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #666;">Search:</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Title or author..." 
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
                <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #666;">Reviews:</label>
                <select name="has_review" style="width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px;">
                    <option value="all" <?php echo $hasReview == 'all' ? 'selected' : ''; ?>>All Books</option>
                    <option value="yes" <?php echo $hasReview == 'yes' ? 'selected' : ''; ?>>📝 With Reviews</option>
                    <option value="no" <?php echo $hasReview == 'no' ? 'selected' : ''; ?>>No Reviews</option>
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
                <button type="submit" style="width: 100%; padding: 10px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 14px;">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
    
    <!-- Books Grid -->
    <?php if (empty($books)): ?>
        <div class="card" style="text-align: center; padding: 60px 30px;">
            <div style="font-size: 4em; margin-bottom: 20px;">📚</div>
            <h2 style="color: #666; margin-bottom: 10px;">No books found</h2>
            <p style="color: #999;">Try adjusting your filters or search terms</p>
        </div>
    <?php else: ?>
        <div class="item-grid">
            <?php foreach ($books as $book): 
                $itemId = getItemId($book['url']);
                $title = cleanTitle($book['title']);
                $stars = getStars($book['title']);
                $author = $book['extracted_author'] ?? 'Unknown';
                $date = date('M j, Y', strtotime($book['publish_date']));
                $hasReviewContent = !empty($book['full_content']) && strlen($book['full_content']) > 100;
            ?>
                <a href="review.php?id=<?php echo $itemId; ?>" class="item-card">
                    <?php if ($book['image_url']): ?>
                        <div style="position: relative;">
                            <img src="<?php echo htmlspecialchars($book['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($title); ?>" 
                                 class="item-image"
                                 onerror="this.src='https://via.placeholder.com/300x400/667eea/ffffff?text=No+Cover'">
                            <?php if ($hasReviewContent): ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(25, 135, 84, 0.95); color: white; padding: 8px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                                    📝 Review
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="item-image" style="background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 3em; position: relative;">
                            📚
                            <?php if ($hasReviewContent): ?>
                                <div style="position: absolute; top: 10px; right: 10px; background: rgba(25, 135, 84, 0.95); color: white; padding: 8px 12px; border-radius: 20px; font-size: 0.35em; font-weight: 600;">
                                    📝 Review
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="item-content">
                        <h3 class="item-title"><?php echo htmlspecialchars($title); ?></h3>
                        <p class="item-meta">
                            <strong>by <?php echo htmlspecialchars($author); ?></strong><br>
                            <?php if ($stars > 0): ?>
                                <span style="color: #d4af37; font-size: 1.1em;"><?php echo str_repeat('★', $stars); ?></span><br>
                            <?php endif; ?>
                            <span style="color: #999;">Read: <?php echo $date; ?></span>
                        </p>
                        
                        <?php if ($hasReviewContent): ?>
                            <div style="margin-top: 15px; padding: 12px; background: #f8f9fa; border-left: 3px solid #198754; border-radius: 4px;">
                                <div style="font-size: 0.75em; color: #198754; font-weight: 600; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px;">
                                    📝 My Review
                                </div>
                                <p style="font-size: 0.9em; color: #495057; line-height: 1.6; margin: 0;">
                                    <?php 
                                    $reviewSnippet = strip_tags($book['full_content']);
                                    $reviewSnippet = preg_replace('/\s+/', ' ', $reviewSnippet);
                                    echo htmlspecialchars(mb_substr($reviewSnippet, 0, 150)); 
                                    ?>...
                                </p>
                            </div>
                        <?php elseif ($book['description']): ?>
                            <p style="font-size: 0.9em; color: #666; margin-top: 10px; line-height: 1.5;">
                                <?php echo mb_substr(strip_tags($book['description']), 0, 120); ?>...
                            </p>
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
