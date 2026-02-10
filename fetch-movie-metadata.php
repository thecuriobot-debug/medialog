<?php
// Scrape genres and directors from Letterboxd film pages
require_once 'config.php';

$pdo = getDB();

// Get all movies
$stmt = $pdo->query("SELECT id, url, title FROM posts WHERE site_id = 6");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Fetching genres and directors for " . count($movies) . " movies...\n\n";

$updated = 0;
$failed = 0;

// Add columns if they don't exist
try {
    $pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS genres TEXT");
    $pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS director TEXT");
    $pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS runtime_minutes INT");
    echo "✓ Database columns ready\n\n";
} catch (Exception $e) {
    // Columns might already exist
}

foreach ($movies as $movie) {
    echo "Processing: {$movie['title']}\n";
    
    // Fetch the Letterboxd page
    $html = @file_get_contents($movie['url']);
    
    if (!$html) {
        echo "  ✗ Failed to fetch\n";
        $failed++;
        sleep(1);
        continue;
    }
    
    // Extract genres
    $genres = [];
    if (preg_match_all('/<a href="\/films\/genre\/([^\/]+)\/"[^>]*>([^<]+)<\/a>/', $html, $matches)) {
        $genres = $matches[2];
    }
    $genreStr = implode(', ', array_unique($genres));
    
    // Extract director
    $director = '';
    if (preg_match('/<a href="\/director\/[^"]+\/"[^>]*>([^<]+)<\/a>/', $html, $matches)) {
        $director = $matches[1];
    }
    
    // Extract runtime
    $runtime = null;
    if (preg_match('/(\d+)\s*min/', $html, $matches)) {
        $runtime = (int)$matches[1];
    }
    
    // Update database
    $stmt = $pdo->prepare("
        UPDATE posts 
        SET genres = ?, director = ?, runtime_minutes = ?
        WHERE id = ?
    ");
    $stmt->execute([$genreStr, $director, $runtime, $movie['id']]);
    
    echo "  ✓ Genres: {$genreStr}\n";
    echo "  ✓ Director: {$director}\n";
    echo "  ✓ Runtime: {$runtime} min\n\n";
    
    $updated++;
    
    // Be nice to Letterboxd servers
    sleep(1.5);
}

echo "\n========================================\n";
echo "Complete!\n";
echo "Updated: {$updated}\n";
echo "Failed: {$failed}\n";
echo "========================================\n";
