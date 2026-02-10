<?php
require_once 'config.php';
$pdo = getDB();

// Get filter parameters
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

// Execute author query
$stmt = $pdo->prepare($authorQuery);
if ($search) {
    $searchParam = "%{$search}%";
    $stmt->execute(['search' => $searchParam]);
} else {
    $stmt->execute();
}
$creators = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Sort creators
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
$totalBooks = array_sum(array_column($creators, 'count'));

$pageTitle = "Creators";
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">✍️ Authors</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">
            Discover the authors behind <?= $totalBooks ?> books
        </p>
    </div>

    <!-- Search and Sort -->
    <div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
        <form method="GET" style="flex: 1; min-width: 250px;">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="search" 
                   name="search" 
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Search authors..." 
                   style="width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1em;">
        </form>
        
        <select onchange="window.location.href='?sort=' + this.value + '&search=<?= urlencode($search) ?>'" 
                style="padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1em; background: white;">
            <option value="count_desc" <?= $sort === 'count_desc' ? 'selected' : '' ?>>Most Books</option>
            <option value="count_asc" <?= $sort === 'count_asc' ? 'selected' : '' ?>>Fewest Books</option>
            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
            <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
        </select>
    </div>

    <!-- Stats -->
    <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 15px; margin-bottom: 30px; text-align: center; color: white;">
        <strong><?= $totalCreators ?></strong> authors • <strong><?= $totalBooks ?></strong> total books
    </div>

    <!-- Creators Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px;">
        <?php foreach ($creators as $creator): ?>
            <div style="background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 20px; transition: transform 0.2s; cursor: pointer;" 
                 onmouseover="this.style.transform='translateY(-5px)'" 
                 onmouseout="this.style.transform='translateY(0)'">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #d4af37, #f4d483); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.8em; flex-shrink: 0;">
                    ✍️
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; font-size: 1.2em; color: #333;">
                        <?= htmlspecialchars($creator['name']) ?>
                    </h3>
                    <p style="margin: 0; color: #666; font-size: 0.95em;">
                        <strong style="color: #d4af37; font-size: 1.3em;"><?= $creator['count'] ?></strong> 
                        <?= $creator['count'] == 1 ? 'book' : 'books' ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
