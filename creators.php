<?php
require_once 'config.php';
$pdo = getDB();

// Get filter parameters
$type = $_GET['type'] ?? 'all'; // all, authors, directors
$sort = $_GET['sort'] ?? 'count_desc';
$search = $_GET['search'] ?? '';

// Build authors query
$authorWhere = "site_id = 7";
if ($search) {
    $authorWhere .= " AND title LIKE :search";
}

$authorQuery = "
    SELECT 
        TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(title, ' by ', -1), ' -', 1)) as name,
        COUNT(*) as count,
        'author' as type
    FROM posts 
    WHERE {$authorWhere}
    GROUP BY name
    HAVING name != '' AND name NOT LIKE '%★%'
";

// Execute queries based on type filter
if ($type === 'all' || $type === 'authors') {
    $stmt = $pdo->prepare($authorQuery);
    if ($search) {
        $searchParam = "%{$search}%";
        $stmt->execute(['search' => $searchParam]);
    } else {
        $stmt->execute();
    }
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $authors = [];
}

// Directors - not available (no director column in database)
if ($type === 'all' || $type === 'directors') {
    $directors = []; // Empty - director column doesn't exist
} else {
    $directors = [];
}

// Combine and sort
$creators = array_merge($authors, $directors);

usort($creators, function($a, $b) use ($sort) {
    switch($sort) {
        case 'name_asc':
            return strcasecmp($a['name'], $b['name']);
        case 'name_desc':
            return strcasecmp($b['name'], $a['name']);
        case 'count_asc':
            return $a['count'] - $b['count'];
        case 'count_desc':
        default:
            return $b['count'] - $a['count'];
    }
});

$totalCreators = count($creators);
$totalAuthors = count($authors);
$totalDirectors = count($directors);

$pageTitle = "Creators";
$pageStyles = "
    .creator-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    
    .creator-card {
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.2s;
        cursor: pointer;
    }
    
    .creator-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 25px rgba(0,0,0,0.15);
    }
    
    .creator-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.8em;
        flex-shrink: 0;
    }
    
    .creator-icon.author {
        background: linear-gradient(135deg, #d4af37, #f4d483);
    }
    
    .creator-icon.director {
        background: linear-gradient(135deg, #667eea, #764ba2);
    }
    
    .creator-info {
        flex: 1;
    }
    
    .creator-name {
        margin: 0 0 8px 0;
        font-size: 1.2em;
        color: #333;
        font-weight: 600;
    }
    
    .creator-count {
        margin: 0;
        color: #666;
        font-size: 0.95em;
    }
    
    .creator-count strong {
        font-size: 1.3em;
    }
    
    .type-tabs {
        display: flex;
        gap: 15px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }
    
    .type-tab {
        padding: 12px 30px;
        background: rgba(255,255,255,0.2);
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 25px;
        color: white;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
    }
    
    .type-tab:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-2px);
    }
    
    .type-tab.active {
        background: white;
        color: #667eea;
        border-color: white;
    }
    
    .search-sort {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .search-box {
        flex: 1;
        min-width: 250px;
    }
    
    .search-box input {
        width: 100%;
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 1em;
    }
    
    .sort-select {
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 1em;
        background: white;
        cursor: pointer;
    }
    
    .stats-bar {
        background: rgba(255,255,255,0.1);
        padding: 20px;
        border-radius: 15px;
        margin-bottom: 30px;
        text-align: center;
        color: white;
        font-size: 1.1em;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 30px;
        background: white;
        border-radius: 15px;
        margin-top: 30px;
    }
    
    .empty-state-icon {
        font-size: 4em;
        margin-bottom: 20px;
    }
";
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">✍️ Creators</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">
            Discover the authors and directors behind your collection
        </p>
    </div>

    <!-- Type Tabs -->
    <div class="type-tabs">
        <a href="?type=all&sort=<?= $sort ?>&search=<?= urlencode($search) ?>" 
           class="type-tab <?= $type === 'all' ? 'active' : '' ?>">
            All Creators
        </a>
        <a href="?type=authors&sort=<?= $sort ?>&search=<?= urlencode($search) ?>" 
           class="type-tab <?= $type === 'authors' ? 'active' : '' ?>">
            Authors (<?= $totalAuthors ?>)
        </a>
        <a href="?type=directors&sort=<?= $sort ?>&search=<?= urlencode($search) ?>" 
           class="type-tab <?= $type === 'directors' ? 'active' : '' ?>">
            Directors (<?= $totalDirectors ?>)
        </a>
    </div>

    <!-- Search and Sort -->
    <div class="search-sort">
        <form method="GET" class="search-box">
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="search" 
                   name="search" 
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search creators..." 
                   autocomplete="off">
        </form>
        
        <select class="sort-select" onchange="window.location.href='?type=<?= $type ?>&sort=' + this.value + '&search=<?= urlencode($search) ?>'">
            <option value="count_desc" <?= $sort === 'count_desc' ? 'selected' : '' ?>>Most Items</option>
            <option value="count_asc" <?= $sort === 'count_asc' ? 'selected' : '' ?>>Fewest Items</option>
            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
        </select>
    </div>

    <!-- Stats -->
    <div class="stats-bar">
        <strong><?= $totalCreators ?></strong> creators
        <?php if ($type === 'all'): ?>
            • <strong><?= $totalAuthors ?></strong> authors
            • <strong><?= $totalDirectors ?></strong> directors
        <?php endif; ?>
    </div>

    <!-- Creators Grid -->
    <?php if (empty($creators)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <?= $type === 'directors' ? '🎬' : '📚' ?>
            </div>
            <h2 style="color: #666; margin-bottom: 10px;">
                <?php if ($type === 'directors'): ?>
                    No Directors Available
                <?php elseif ($search): ?>
                    No creators found
                <?php else: ?>
                    No creators yet
                <?php endif; ?>
            </h2>
            <p style="color: #999;">
                <?php if ($type === 'directors'): ?>
                    Director information is not currently available in the database.
                <?php elseif ($search): ?>
                    Try adjusting your search terms
                <?php else: ?>
                    Start adding books and movies to see creators here
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="creator-grid">
            <?php foreach ($creators as $creator): 
                $isAuthor = $creator['type'] === 'author';
            ?>
                <div class="creator-card">
                    <div class="creator-icon <?= $isAuthor ? 'author' : 'director' ?>">
                        <?= $isAuthor ? '✍️' : '🎬' ?>
                    </div>
                    <div class="creator-info">
                        <h3 class="creator-name"><?= htmlspecialchars($creator['name']) ?></h3>
                        <p class="creator-count">
                            <strong style="color: <?= $isAuthor ? '#d4af37' : '#667eea' ?>;">
                                <?= $creator['count'] ?>
                            </strong> 
                            <?= $isAuthor ? 'book' : 'movie' ?><?= $creator['count'] == 1 ? '' : 's' ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
