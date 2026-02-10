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

// Build directors query
$directorWhere = "site_id = 6 AND director IS NOT NULL AND director != ''";
if ($search) {
    $directorWhere .= " AND director LIKE :search";
}

$directorQuery = "
    SELECT 
        director as name,
        COUNT(*) as count,
        'director' as type
    FROM posts 
    WHERE {$directorWhere}
    GROUP BY director
";

// Execute queries
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

if ($type === 'all' || $type === 'directors') {
    $stmt = $pdo->prepare($directorQuery);
    if ($search) {
        $searchParam = "%{$search}%";
        $stmt->execute(['search' => $searchParam]);
    } else {
        $stmt->execute();
    }
    $directors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $directors = [];
}

// Combine and sort
$creators = array_merge($authors, $directors);

// Sort based on parameter
switch ($sort) {
    case 'name_asc':
        usort($creators, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        break;
    case 'name_desc':
        usort($creators, fn($a, $b) => strcasecmp($b['name'], $a['name']));
        break;
    case 'count_asc':
        usort($creators, fn($a, $b) => $a['count'] - $b['count']);
        break;
    case 'count_desc':
    default:
        usort($creators, fn($a, $b) => $b['count'] - $a['count']);
        break;
}

$totalCreators = count($creators);
$totalAuthors = count($authors);
$totalDirectors = count($directors);

$pageTitle = "Creators";
include 'includes/header.php';
?>

<style>
    /* Creators Page Styles */
    .page-header {
        text-align: center;
        margin-bottom: 40px;
    }
    
    .page-header h1 {
        font-size: 3em;
        margin-bottom: 15px;
    }
    
    .creators-filters {
        background: white;
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 15px;
        align-items: end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .filter-group label {
        font-weight: 600;
        color: #666;
        font-size: 0.9em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .filter-input, .filter-select {
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s;
    }
    
    .filter-input:focus, .filter-select:focus {
        outline: none;
        border-color: #667eea;
    }
    
    .filter-button {
        padding: 12px 25px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .filter-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .type-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 30px;
        background: white;
        padding: 10px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .type-tab {
        flex: 1;
        padding: 12px 20px;
        border: none;
        background: transparent;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        color: #666;
        text-align: center;
    }
    
    .type-tab:hover {
        background: #f8f9fa;
    }
    
    .type-tab.active {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .creators-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
    }
    
    .creator-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .creator-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .creator-header {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .creator-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2em;
        flex-shrink: 0;
    }
    
    .creator-icon.author {
        background: linear-gradient(135deg, #1976d2, #2196f3);
    }
    
    .creator-icon.director {
        background: linear-gradient(135deg, #c2185b, #e91e63);
    }
    
    .creator-info {
        flex: 1;
        min-width: 0;
    }
    
    .creator-name {
        font-size: 1.2em;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 5px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .creator-type {
        font-size: 0.85em;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .creator-count {
        font-size: 2em;
        font-weight: 800;
        color: #667eea;
        text-align: center;
    }
    
    .creator-label {
        text-align: center;
        color: #999;
        font-size: 0.9em;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            grid-template-columns: 1fr;
        }
        
        .creators-grid {
            grid-template-columns: 1fr;
        }
        
        .type-tabs {
            flex-direction: column;
        }
    }
</style>

<div class="container">
    <div class="page-header">
        <h1>🎭 Creators</h1>
        <div class="subtitle">Authors & Directors</div>
    </div>
    
    <!-- Stats Bar -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= $totalCreators ?></div>
            <div class="stat-label">Total Creators</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $totalAuthors ?></div>
            <div class="stat-label">Authors</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $totalDirectors ?></div>
            <div class="stat-label">Directors</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= $totalAuthors + $totalDirectors ?></div>
            <div class="stat-label">Combined Works</div>
        </div>
    </div>
    
    <!-- Type Filter Tabs -->
    <div class="type-tabs">
        <a href="?type=all&sort=<?= $sort ?>&search=<?= urlencode($search) ?>" 
           class="type-tab <?= $type === 'all' ? 'active' : '' ?>">
            🎭 All Creators
        </a>
        <a href="?type=authors&sort=<?= $sort ?>&search=<?= urlencode($search) ?>" 
           class="type-tab <?= $type === 'authors' ? 'active' : '' ?>">
            📚 Authors Only
        </a>
        <a href="?type=directors&sort=<?= $sort ?>&search=<?= urlencode($search) ?>" 
           class="type-tab <?= $type === 'directors' ? 'active' : '' ?>">
            🎬 Directors Only
        </a>
    </div>
    
    <!-- Filters -->
    <div class="creators-filters">
        <form method="GET" class="filter-row">
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
            
            <div class="filter-group">
                <label>Search Creators</label>
                <input type="text" 
                       name="search" 
                       class="filter-input"
                       placeholder="Search by name..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-group">
                <label>Sort By</label>
                <select name="sort" class="filter-select">
                    <option value="count_desc" <?= $sort === 'count_desc' ? 'selected' : '' ?>>Most Works</option>
                    <option value="count_asc" <?= $sort === 'count_asc' ? 'selected' : '' ?>>Fewest Works</option>
                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>&nbsp;</label>
                <button type="submit" class="filter-button">Apply Filters</button>
            </div>
        </form>
    </div>
    
    <!-- Creators Grid -->
    <?php if (empty($creators)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <h3>No creators found</h3>
            <p>Try adjusting your search or filters</p>
        </div>
    <?php else: ?>
        <div class="creators-grid">
            <?php foreach ($creators as $creator): 
                $isAuthor = $creator['type'] === 'author';
                $icon = $isAuthor ? '✍️' : '🎬';
                $typeLabel = $isAuthor ? 'Author' : 'Director';
                $workLabel = $isAuthor ? 'Books' : 'Movies';
                $linkPage = $isAuthor ? 'authors.php' : 'directors.php';
            ?>
                <a href="<?= $linkPage ?>?name=<?= urlencode($creator['name']) ?>" class="creator-card">
                    <div class="creator-header">
                        <div class="creator-icon <?= $isAuthor ? 'author' : 'director' ?>">
                            <?= $icon ?>
                        </div>
                        <div class="creator-info">
                            <div class="creator-name" title="<?= htmlspecialchars($creator['name']) ?>">
                                <?= htmlspecialchars($creator['name']) ?>
                            </div>
                            <div class="creator-type"><?= $typeLabel ?></div>
                        </div>
                    </div>
                    
                    <div class="creator-count"><?= $creator['count'] ?></div>
                    <div class="creator-label"><?= $workLabel ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
