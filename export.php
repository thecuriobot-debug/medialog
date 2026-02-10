<?php
require_once 'config.php';
$pdo = getDB();

$format = $_GET['format'] ?? 'csv';
$type = $_GET['type'] ?? 'all'; // all, books, movies

function cleanTitle($title) {
    return preg_replace('/★+/', '', trim($title));
}

function getStars($title) {
    return substr_count($title, '★');
}

if ($format === 'csv') {
    // Get data
    $data = [];
    
    if ($type === 'all' || $type === 'books') {
        $stmt = $pdo->query("SELECT * FROM posts WHERE site_id = 7 ORDER BY publish_date DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'Type' => 'Book',
                'Title' => cleanTitle($row['title']),
                'Rating' => getStars($row['title']),
                'Date' => date('Y-m-d', strtotime($row['publish_date'])),
                'Review' => strip_tags($row['full_content'] ?? ''),
                'URL' => $row['url']
            ];
        }
    }
    
    if ($type === 'all' || $type === 'movies') {
        $stmt = $pdo->query("SELECT * FROM posts WHERE site_id = 6 ORDER BY publish_date DESC");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = [
                'Type' => 'Movie',
                'Title' => cleanTitle($row['title']),
                'Rating' => getStars($row['title']),
                'Date' => date('Y-m-d', strtotime($row['publish_date'])),
                'Director' => $row['director'] ?? '',
                'Genres' => $row['genres'] ?? '',
                'Review' => strip_tags($row['description'] ?? ''),
                'URL' => $row['url']
            ];
        }
    }
    
    // Output CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="medialog-export-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

// If not exporting, show export page
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
        margin-bottom: 20px;
    }
    .export-options {
        display: grid;
        gap: 15px;
        margin: 20px 0;
    }
    .option-group {
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }
    .option-group h3 {
        margin: 0 0 15px 0;
        color: #1a1a1a;
    }
    .radio-group {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .radio-option {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .radio-option input {
        width: 18px;
        height: 18px;
    }
    .btn {
        padding: 12px 30px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    .info-box {
        background: #e3f2fd;
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid #2196f3;
        margin-top: 20px;
    }
";
include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="text-align: center; margin-bottom: 40px;">
        <h1 style="font-size: 3em; color: white; margin-bottom: 15px;">📤 Export Data</h1>
        <p style="font-size: 1.2em; color: rgba(255,255,255,0.9);">Download your collection</p>
    </div>
    
    <div class="export-container">
        <div class="export-card">
            <h2 style="margin-bottom: 20px;">Choose Export Options</h2>
            
            <form method="GET" action="export.php">
                <div class="export-options">
                    <div class="option-group">
                        <h3>Format</h3>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="format" value="csv" checked>
                                <span>CSV (Excel compatible)</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="option-group">
                        <h3>Content</h3>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="type" value="all" checked>
                                <span>📚🎬 All (Books + Movies)</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="type" value="books">
                                <span>📚 Books Only</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="type" value="movies">
                                <span>🎬 Movies Only</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">💾 Download Export</button>
                
                <div class="info-box">
                    <strong>ℹ️ What's included:</strong>
                    <ul style="margin: 10px 0 0 20px;">
                        <li>Titles and ratings</li>
                        <li>Dates watched/read</li>
                        <li>Your reviews and notes</li>
                        <li>Directors and genres (movies)</li>
                        <li>Original URLs</li>
                    </ul>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
</body>
</html>
