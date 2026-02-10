<?php
require_once 'config.php';

$pdo = getDB();

// Get current year with fallback logic
$currentYear = date('Y');
$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE site_id = 7 AND YEAR(publish_date) = {$currentYear}");
$currentYearCount = $stmt->fetch()['total'];
if ($currentYearCount == 0) {
    $currentYear = $currentYear - 1;
}

// Get total books for footer
$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE site_id = 7");
$totalBooks = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM posts WHERE site_id = 6");
$totalMovies = $stmt->fetch()['total'];

// Extract authors from titles (format: "Title by Author - Stars")
$stmt = $pdo->query("
    SELECT title, url, publish_date, image_url
    FROM posts 
    WHERE site_id = 7
    ORDER BY publish_date DESC
");

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse authors from titles
$authors = [];
foreach ($books as $book) {
    // Extract author from "Title by Author - Stars" format
    if (preg_match('/by (.+?) -/', $book['title'], $matches)) {
        $author = trim($matches[1]);
        if (!isset($authors[$author])) {
            $authors[$author] = [
                'books' => [],
                'years' => []
            ];
        }
        $authors[$author]['books'][] = $book;
        $year = date('Y', strtotime($book['publish_date']));
        if (!in_array($year, $authors[$author]['years'])) {
            $authors[$author]['years'][] = $year;
        }
    } elseif (preg_match('/by (.+)$/', $book['title'], $matches)) {
        $author = trim($matches[1]);
        if (!isset($authors[$author])) {
            $authors[$author] = [
                'books' => [],
                'years' => []
            ];
        }
        $authors[$author]['books'][] = $book;
        $year = date('Y', strtotime($book['publish_date']));
        if (!in_array($year, $authors[$author]['years'])) {
            $authors[$author]['years'][] = $year;
        }
    }
}

// Sort by book count
uasort($authors, function($a, $b) {
    return count($b['books']) - count($a['books']);
});

$totalAuthors = count($authors);
$avgBooksPerAuthor = $totalAuthors > 0 ? round(array_sum(array_map(function($a) { return count($a['books']); }, $authors)) / $totalAuthors, 1) : 0;
$mostReadAuthor = array_key_first($authors);
$mostReadCount = count($authors[$mostReadAuthor]['books']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors - MediaLog</title>
    <meta name="description" content="Author analytics: <?= $totalAuthors ?> authors tracked with reading statistics and book collections">
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
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        /* Authors Grid */
        .authors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .author-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .author-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.3);
        }
        
        .author-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .author-name {
            font-size: 1.5em;
            color: #1976d2;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .author-meta {
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
            color: #1976d2;
        }
        
        /* Book Grid */
        .books-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 10px;
        }
        
        .book-cover {
            aspect-ratio: 2/3;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .book-cover:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        
        .book-cover img {
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
            
            .authors-grid {
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
                <a href="authors.php" class="active">Authors</a>
                <a href="directors.php">Directors</a>
                <a href="stats.php">Statistics</a>
                <a href="insights.php">Insights</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <h1>📚 Authors</h1>
            <p>Exploring your favorite writers and their works</p>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalAuthors ?></div>
                <div class="stat-label">Total Authors</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalBooks ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $avgBooksPerAuthor ?></div>
                <div class="stat-label">Avg per Author</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $mostReadCount ?></div>
                <div class="stat-label">Most Read</div>
            </div>
        </div>
        
        <?php if (empty($authors)): ?>
            <!-- Empty State -->
            <div class="empty-state">
                <h2>No Authors Yet</h2>
                <p>Start tracking your reading by importing books from Goodreads!</p>
            </div>
        <?php else: ?>
            <!-- Authors Grid -->
            <div class="authors-grid">
                <?php foreach ($authors as $authorName => $data): ?>
                    <div class="author-card">
                        <div class="author-header">
                            <div class="author-name"><?= htmlspecialchars($authorName) ?></div>
                            <div class="author-meta">
                                <div class="meta-item">
                                    <span>📚</span>
                                    <strong><?= count($data['books']) ?></strong>
                                    <span><?= count($data['books']) == 1 ? 'book' : 'books' ?></span>
                                </div>
                                <div class="meta-item">
                                    <span>📅</span>
                                    <span><?= implode(', ', array_unique($data['years'])) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="books-grid">
                            <?php foreach (array_slice($data['books'], 0, 12) as $book): ?>
                                <a href="<?= htmlspecialchars($book['url']) ?>" class="book-cover" target="_blank" title="<?= htmlspecialchars($book['title']) ?>">
                                    <?php if (!empty($book['image_url'])): ?>
                                        <img src="<?= htmlspecialchars($book['image_url']) ?>" alt="<?= htmlspecialchars($book['title']) ?>">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-size: 2em;">📚</div>
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
