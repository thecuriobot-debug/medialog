<?php
require_once 'config.php';
$pdo = getDB();

// Handle export request
if (isset($_GET['export'])) {
    $exportType = $_GET['export']; // books, movies, all, lists
    $format = $_GET['format'] ?? 'csv'; // csv or json
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=medialog_' . $exportType . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    if ($exportType === 'books' || $exportType === 'all') {
        // Export books
        fputcsv($output, ['Type', 'Title', 'Author', 'Rating', 'Date Read', 'Review', 'URL']);
        
        $stmt = $pdo->query("SELECT * FROM posts WHERE site_id = 7 ORDER BY publish_date DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $title = preg_replace('/ by .*$/', '', $row['title']);
            $author = '';
            if (preg_match('/ by (.+?)(?:,|$)/', $row['title'], $matches)) {
                $author = $matches[1];
            }
            $rating = substr_count($row['title'], '★');
            $review = strip_tags($row['full_content'] ?? '');
            
            fputcsv($output, [
                'Book',
                $title,
                $author,
                $rating,
                $row['publish_date'],
                $review,
                $row['url']
            ]);
        }
    }
    
    if ($exportType === 'movies' || $exportType === 'all') {
        // Export movies
        if ($exportType === 'all') {
            fputcsv($output, []); // Empty line separator
        } else {
            fputcsv($output, ['Type', 'Title', 'Director', 'Rating', 'Date Watched', 'Review', 'Genres', 'URL']);
        }
        
        $stmt = $pdo->query("SELECT * FROM posts WHERE site_id = 6 ORDER BY publish_date DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $title = preg_replace('/, \d{4} - ★+$/', '', $row['title']);
            $title = preg_replace('/ - ★+$/', '', $title);
            $rating = substr_count($row['title'], '★');
            $review = strip_tags($row['description'] ?? '');
            
            fputcsv($output, [
                'Movie',
                $title,
                $row['director'] ?? '',
                $rating,
                $row['publish_date'],
                $review,
                $row['genres'] ?? '',
                $row['url']
            ]);
        }
    }
    
    if ($exportType === 'lists') {
        // Export lists
        fputcsv($output, ['List Name', 'List Type', 'Item Title', 'Item Type', 'Date Added', 'Notes']);
        
        $stmt = $pdo->query("
            SELECT 
                ul.name as list_name,
                ul.list_type,
                p.title,
                p.site_id,
                uli.added_at,
                uli.notes
            FROM user_lists ul
            JOIN user_list_items uli ON ul.id = uli.list_id
            JOIN posts p ON uli.post_id = p.id
            WHERE ul.user_id = 1
            ORDER BY ul.name, uli.added_at DESC
        ");
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $title = preg_replace('/ by .*$/', '', $row['title']);
            $title = preg_replace('/, \d{4} - ★+$/', '', $title);
            $title = preg_replace('/ - ★+$/', '', $title);
            
            fputcsv($output, [
                $row['list_name'],
                $row['list_type'],
                $title,
                $row['site_id'] == 7 ? 'Book' : 'Movie',
                $row['added_at'],
                $row['notes'] ?? ''
            ]);
        }
    }
    
    fclose($output);
    exit;
}

// Get statistics
$totalBooks = $pdo->query("SELECT COUNT(*) as c FROM posts WHERE site_id = 7")->fetch()['c'];
$totalMovies = $pdo->query("SELECT COUNT(*) as c FROM posts WHERE site_id = 6")->fetch()['c'];
$totalLists = $pdo->query("SELECT COUNT(*) as c FROM user_lists WHERE user_id = 1")->fetch()['c'];
$totalListItems = $pdo->query("SELECT COUNT(*) as c FROM user_list_items")->fetch()['c'];

$pageTitle = "Export Data";
$pageStyles = "
    .export-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .export-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-bottom: 25px;
    }
    
    .export-card h2 {
        color: #667eea;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .export-option {
        padding: 20px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        margin-bottom: 15px;
        transition: all 0.3s;
    }
    
    .export-option:hover {
        border-color: #667eea;
        background: #f8f9fa;
    }
    
    .export-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .export-title {
        font-size: 1.2em;
        font-weight: 700;
        color: #1a1a1a;
    }
    
    .export-count {
        background: #667eea;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9em;
    }
    
    .export-description {
        color: #666;
        font-size: 0.95em;
        margin-bottom: 15px;
        line-height: 1.6;
    }
    
    .export-buttons {
        display: flex;
        gap: 10px;
    }
    
    .btn-export {
        padding: 10px 25px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-block;
        text-align: center;
    }
    
    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .info-box {
        background: #f0f7ff;
        border-left: 4px solid #667eea;
        padding: 15px;
        border-radius: 8px;
        margin-top: 20px;
    }
    
    .info-box h4 {
        color: #667eea;
        margin: 0 0 10px 0;
    }
    
    .info-box ul {
        margin: 5px 0;
        padding-left: 20px;
        color: #666;
    }
    
    .info-box li {
        margin: 5px 0;
    }
";

include 'includes/header.php';
?>

<div class="container export-container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">📥 Export Data</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Download your reading and watching data</p>
    </div>
    
    <!-- Export Options -->
    <div class="export-card">
        <h2>📊 Choose What to Export</h2>
        
        <!-- Books Export -->
        <div class="export-option">
            <div class="export-header">
                <div class="export-title">📚 Books Data</div>
                <div class="export-count"><?php echo number_format($totalBooks); ?> books</div>
            </div>
            <p class="export-description">
                Export all your books including titles, authors, ratings, dates read, and reviews.
            </p>
            <div class="export-buttons">
                <a href="?export=books&format=csv" class="btn-export">
                    📄 Download CSV
                </a>
            </div>
        </div>
        
        <!-- Movies Export -->
        <div class="export-option">
            <div class="export-header">
                <div class="export-title">🎬 Movies Data</div>
                <div class="export-count"><?php echo number_format($totalMovies); ?> movies</div>
            </div>
            <p class="export-description">
                Export all your movies including titles, directors, ratings, genres, dates watched, and reviews.
            </p>
            <div class="export-buttons">
                <a href="?export=movies&format=csv" class="btn-export">
                    📄 Download CSV
                </a>
            </div>
        </div>
        
        <!-- All Data Export -->
        <div class="export-option">
            <div class="export-header">
                <div class="export-title">📦 Complete Collection</div>
                <div class="export-count"><?php echo number_format($totalBooks + $totalMovies); ?> items</div>
            </div>
            <p class="export-description">
                Export everything - all books and movies in a single file.
            </p>
            <div class="export-buttons">
                <a href="?export=all&format=csv" class="btn-export">
                    📄 Download CSV
                </a>
            </div>
        </div>
        
        <!-- Lists Export -->
        <?php if ($totalLists > 0): ?>
        <div class="export-option">
            <div class="export-header">
                <div class="export-title">📋 Custom Lists</div>
                <div class="export-count"><?php echo $totalLists; ?> lists, <?php echo $totalListItems; ?> items</div>
            </div>
            <p class="export-description">
                Export all your custom lists with their items and notes.
            </p>
            <div class="export-buttons">
                <a href="?export=lists&format=csv" class="btn-export">
                    📄 Download CSV
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Info Box -->
        <div class="info-box">
            <h4>ℹ️ About CSV Exports</h4>
            <ul>
                <li>CSV files can be opened in Excel, Google Sheets, or any spreadsheet software</li>
                <li>Perfect for backup, analysis, or importing into other services</li>
                <li>All data is exported exactly as stored in your MediaLog</li>
                <li>Files are generated on-demand and not stored on the server</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
