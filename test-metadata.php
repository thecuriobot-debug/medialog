<?php
// Scrape genres and directors from Letterboxd - TEST VERSION (5 movies)
require_once 'config.php';

$pdo = getDB();

// Get just 5 movies for testing
$stmt = $pdo->query("SELECT id, url, title FROM posts WHERE site_id = 6 LIMIT 5");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Fetching metadata for " . count($movies) . " movies (test)...\n\n";

$updated = 0;
$failed = 0;

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
    if (preg_match('/(\d+)\s*mins?/', $html, $matches)) {
        $runtime = (int)$matches[1];
    }
    
    // Update database
    $stmt = $pdo->prepare("
        UPDATE posts 
        SET genres = ?, director = ?, runtime_minutes = ?
        WHERE id = ?
    ");
    $stmt->execute([$genreStr, $director, $runtime, $movie['id']]);
    
    echo "  ✓ Genres: " . ($genreStr ?: 'none') . "\n";
    echo "  ✓ Director: " . ($director ?: 'none') . "\n";
    echo "  ✓ Runtime: " . ($runtime ?: 'none') . " min\n\n";
    
    $updated++;
    
    // Be nice to Letterboxd servers
    sleep(2);
}

echo "\n========================================\n";
echo "Complete!\n";
echo "Updated: {$updated}\n";
echo "Failed: {$failed}\n";
echo "========================================\n";
