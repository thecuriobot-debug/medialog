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
