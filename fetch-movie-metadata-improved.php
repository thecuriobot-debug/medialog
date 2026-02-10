<?php
// Improved scraper - Uses meta tags and JSON-LD data
require_once 'config.php';

$pdo = getDB();

// Get all movies
$stmt = $pdo->query("SELECT id, url, title FROM posts WHERE site_id = 6");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Fetching metadata for " . count($movies) . " movies...\n\n";

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
    
    // Extract director from meta tag (most reliable)
    $director = '';
    if (preg_match('/<meta name="twitter:data1" content="([^"]+)"/', $html, $matches)) {
        $director = $matches[1];
    }
    
    // Extract genres from the genres page link
    $genres = [];
    if (preg_match_all('/<a href="\/films\/genre\/([^"\/]+)\/"/', $html, $matches)) {
        $genres = array_unique($matches[1]);
        $genres = array_map('ucfirst', $genres); // Capitalize first letter
    }
    $genreStr = implode(', ', $genres);
    
    // Extract runtime - look for "XX mins" or "XX min"
    $runtime = null;
    if (preg_match('/(\d+)\s*mins?\s/', $html, $matches)) {
        $runtime = (int)$matches[1];
    }
    
    // Update database
    $stmt = $pdo->prepare("
        UPDATE posts 
        SET genres = ?, director = ?, runtime_minutes = ?
        WHERE id = ?
    ");
    $stmt->execute([$genreStr, $director, $runtime, $movie['id']]);
    
    echo "  ✓ Director: " . ($director ?: 'none') . "\n";
    echo "  ✓ Genres: " . ($genreStr ?: 'none') . "\n";
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
