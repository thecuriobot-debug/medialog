<?php
require_once 'config.php';

$pdo = getDB();

// Extract authors from titles (format: "Title by Author - Stars")
$stmt = $pdo->query("
    SELECT title, url, publish_date 
    FROM posts 
    WHERE site_id = 7
    ORDER BY title
");

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse authors from titles
$authors = [];
foreach ($books as $book) {
    // Extract author from "Title by Author - Stars" format
    if (preg_match('/by (.+?) -/', $book['title'], $matches)) {
        $author = trim($matches[1]);
        if (!isset($authors[$author])) {
            $authors[$author] = [];
        }
        $authors[$author][] = $book;
    } elseif (preg_match('/by (.+)$/', $book['title'], $matches)) {
        // No stars, just "Title by Author"
        $author = trim($matches[1]);
        if (!isset($authors[$author])) {
            $authors[$author] = [];
        }
        $authors[$author][] = $book;
    }
}

// Sort by book count
uasort($authors, function($a, $b) {
    return count($b) - count($a);
});

$totalAuthors = count($authors);
$totalBooks = array_sum(array_map('count', $authors));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authors - MediaLog</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Georgia', 'Times New Roman', serif;
            background: #f5f5f5;
            padding: 0;
            color: #1a1a1a;
        }
        
        .top-nav {
            background: #1a1a1a;
            border-bottom: 3px solid #d4af37;
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        
        .nav-brand {
            font-family: 'Georgia', serif;
            font-size: 24px;
            font-weight: bold;
            color: #d4af37;
            padding: 15px 0;
            text-decoration: none;
            letter-spacing: 1px;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 0;
        }
        
        .nav-links a {
            display: block;
            padding: 20px 20px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-links a:hover {
            background: #2a2a2a;
            border-bottom-color: #d4af37;
        }
        
        .nav-links a.active {
            background: #2a2a2a;
            border-bottom-color: #d4af37;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #1a1a1a;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1em;
        }
        
        .authors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .author-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        
        .author-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .author-name {
            font-size: 1.4em;
            margin-bottom: 8px;
            color: #1a1a1a;
            font-weight: bold;
        }
        
        .author-count {
            color: #d4af37;
            font-size: 0.9em;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .author-books {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .author-card.expanded .author-books {
            max-height: 1000px;
        }
        
        .book-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .book-item:last-child {
            border-bottom: none;
        }
        
        .book-item a {
            color: #333;
            text-decoration: none;
            display: block;
        }
        
        .book-item a:hover {
            color: #d4af37;
        }
        
        .book-date {
            font-size: 0.8em;
            color: #999;
            margin-left: 10px;
        }
        
        .expand-icon {
            float: right;
            color: #999;
            transition: transform 0.3s ease;
        }
        
        .author-card.expanded .expand-icon {
            transform: rotate(180deg);
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
                <li><a href="authors.php" class="active">Authors</a></li>
                <li><a href="stats.php">Statistics</a></li>
                <li><a href="insights.php">Insights</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <h1>✍️ Authors</h1>
        <div class="subtitle"><?= $totalAuthors ?> authors · <?= $totalBooks ?> books</div>
        
        <div class="authors-grid">
            <?php foreach ($authors as $author => $authorBooks): ?>
                <div class="author-card" onclick="this.classList.toggle('expanded')">
                    <div class="author-name">
                        <?= htmlspecialchars($author) ?>
                        <span class="expand-icon">▼</span>
                    </div>
                    <div class="author-count">
                        <?= count($authorBooks) ?> book<?= count($authorBooks) !== 1 ? 's' : '' ?>
                    </div>
                    <div class="author-books">
                        <?php foreach ($authorBooks as $book): ?>
                            <div class="book-item">
                                <a href="<?= htmlspecialchars($book['url']) ?>">
                                    <?= htmlspecialchars($book['title']) ?>
                                </a>
                                <span class="book-date">
                                    <?= date('M Y', strtotime($book['publish_date'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
