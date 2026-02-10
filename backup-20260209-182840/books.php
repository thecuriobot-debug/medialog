<?php
require_once 'config.php';

$pdo = getDB();

// Get sort and filter parameters
$sort = $_GET['sort'] ?? 'date_desc';
$rating = $_GET['rating'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where = "site_id = 7"; // Goodreads only

if ($rating !== 'all') {
    $where .= " AND title LIKE '%{$rating}%'"; // Stars are in title
}

if ($search) {
    $where .= " AND (title LIKE :search OR full_content LIKE :search)";
}

// Sort options
$orderBy = match($sort) {
    'date_desc' => 'publish_date DESC',
    'date_asc' => 'publish_date ASC',
    'title' => 'title ASC',
    'rating_desc' => 'title DESC', // Stars are at end of title
    default => 'publish_date DESC'
};

$stmt = $pdo->prepare("
    SELECT title, url, publish_date, description, full_content
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Books - MediaLog</title>
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
        
        /* Top Navigation Menu */
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
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-group label {
            font-weight: bold;
            font-size: 0.9em;
        }
        
        select, input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }
        
        button {
            background: #1a1a1a;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-family: inherit;
        }
        
        button:hover {
            background: #333;
        }
        
        .books-grid {
            display: grid;
            gap: 20px;
        }
        
        .book-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .book-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .book-title {
            font-size: 1.3em;
            margin-bottom: 8px;
            color: #1a1a1a;
        }
        
        .book-title a {
            color: inherit;
            text-decoration: none;
        }
        
        .book-title a:hover {
            color: #d4af37;
        }
        
        .book-meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .book-description {
            color: #444;
            line-height: 1.6;
            margin-top: 10px;
        }
        
        .book-excerpt {
            color: #666;
            font-style: italic;
            line-height: 1.6;
            margin-top: 10px;
            padding-left: 15px;
            border-left: 3px solid #d4af37;
        }
    </style>
</head>
<body>
    <nav class="top-nav">
        <div class="nav-container">
            <a href="index.php" class="nav-brand">MEDIALOG</a>
            <ul class="nav-links">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="books.php" class="active">Books</a></li>
                <li><a href="movies.php">Movies</a></li>
                <li><a href="authors.php">Authors</a></li>
                <li><a href="directors.php">Directors</a></li>
                <li><a href="stats.php">Statistics</a></li>
                <li><a href="insights.php">Insights</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container">
        <h1>📚 All Books</h1>
        <div class="subtitle"><?= $total ?> books in your library</div>
        
        <form method="get" class="filters">
            <div class="filter-group">
                <label>Sort:</label>
                <select name="sort" onchange="this.form.submit()">
                    <option value="date_desc" <?= $sort === 'date_desc' ? 'selected' : '' ?>>Most Recent</option>
                    <option value="date_asc" <?= $sort === 'date_asc' ? 'selected' : '' ?>>Oldest First</option>
                    <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Title A-Z</option>
                    <option value="rating_desc" <?= $sort === 'rating_desc' ? 'selected' : '' ?>>Highest Rated</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Rating:</label>
                <select name="rating" onchange="this.form.submit()">
                    <option value="all" <?= $rating === 'all' ? 'selected' : '' ?>>All Ratings</option>
                    <option value="★★★★★" <?= $rating === '★★★★★' ? 'selected' : '' ?>>★★★★★ (5 stars)</option>
                    <option value="★★★★" <?= $rating === '★★★★' ? 'selected' : '' ?>>★★★★ (4 stars)</option>
                    <option value="★★★" <?= $rating === '★★★' ? 'selected' : '' ?>>★★★ (3 stars)</option>
                </select>
            </div>
            
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search books..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit">Search</button>
            </div>
        </form>
        
        <div class="books-grid">
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <div class="book-title">
                        <a href="<?= htmlspecialchars($book['url']) ?>">
                            <?= htmlspecialchars($book['title']) ?>
                        </a>
                    </div>
                    
                    <div class="book-meta">
                        <?= date('F j, Y', strtotime($book['publish_date'])) ?>
                        <?php if ($book['description']): ?>
                            · <?= htmlspecialchars($book['description']) ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($book['full_content'] && strlen($book['full_content']) > 50): ?>
                        <div class="book-excerpt">
                            <?= htmlspecialchars(substr(strip_tags($book['full_content']), 0, 200)) ?>...
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
