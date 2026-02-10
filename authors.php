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

$pageTitle = "Authors";
$pageStyles = "
    /* Authors Page Styles */
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
    
    .authors-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    
    .author-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .author-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    }
    
    .author-header {
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .author-name {
        font-size: 1.3em;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 10px;
    }
    
    .author-meta {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        color: #666;
        font-size: 0.9em;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .author-books {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .book-item {
        font-size: 0.95em;
        color: #666;
        padding: 8px;
        background: #f8f9fa;
        border-radius: 6px;
        transition: background 0.3s;
    }
    
    .book-item:hover {
        background: #e9ecef;
    }
    
    .books-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
        gap: 10px;
        margin-top: 15px;
    }
    
    .book-cover {
        display: block;
        width: 100%;
        aspect-ratio: 2/3;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
    }
    
    .book-cover:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }
    
    .book-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    @media (max-width: 768px) {
        .page-header h1 {
            font-size: 2em;
        }
        
        .authors-grid {
            grid-template-columns: 1fr;
        }
        
        .books-grid {
            grid-template-columns: repeat(auto-fill, minmax(50px, 1fr));
        }
    }
";
include 'includes/header.php';
?>

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
                            <?php foreach (array_slice($data['books'], 0, 11) as $book): ?>
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
