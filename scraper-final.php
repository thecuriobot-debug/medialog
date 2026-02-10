<?php
// FINAL FIXED scraper - removes username from URL
require_once 'config.php';

$pdo = getDB();

$stmt = $pdo->query("SELECT id, url, title FROM posts WHERE site_id = 6");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Fetching metadata for " . count($movies) . " movies...\n\n";

$updated = 0;
$failed = 0;

foreach ($movies as $movie) {
    echo "Processing: {$movie['title']}\n";
    
    // Convert user URL to canonical film URL
    // From: https://letterboxd.com/thunt/film/friendship-2024/
    // To:   https://letterboxd.com/film/friendship-2024/
    $canonicalUrl = preg_replace('/letterboxd\.com\/[^\/]+\/film/', 'letterboxd.com/film', $movie['url']);
    
    echo "  Fetching: $canonicalUrl\n";
    
    $html = @file_get_contents($canonicalUrl);
    
    if (!$html) {
        echo "  ✗ Failed to fetch\n\n";
        $failed++;
        sleep(1);
        continue;
    }
    
    // Extract director from meta tag
    $director = '';
    if (preg_match('/<meta name="twitter:data1" content="([^"]+)"/', $html, $matches)) {
        $director = trim($matches[1]);
    }
    
    // Extract genres
    $genres = [];
    if (preg_match_all('/<a href="\/films\/genre\/([^"\/]+)\/"/', $html, $matches)) {
        $genres = array_unique($matches[1]);
        $genres = array_map('ucfirst', $genres);
    }
    $genreStr = implode(', ', $genres);
    
    // Extract runtime
    $runtime = null;
    if (preg_match('/(\d+)(?:&nbsp;|\s)mins?/', $html, $matches)) {
        $runtime = (int)$matches[1];
    }
    
    // Update database
    $stmt = $pdo->prepare("UPDATE posts SET genres = ?, director = ?, runtime_minutes = ? WHERE id = ?");
    $stmt->execute([$genreStr, $director, $runtime, $movie['id']]);
    
    echo "  ✓ Director: " . ($director ?: '[none]') . "\n";
    echo "  ✓ Genres: " . ($genreStr ?: '[none]') . "\n";
    echo "  ✓ Runtime: " . ($runtime ?: '[none]') . " min\n\n";
    
    $updated++;
    sleep(2); // Be nice to Letterboxd
}

echo "\n========================================\n";
echo "Complete!\n";
echo "Updated: {$updated}\n";
echo "Failed: {$failed}\n";
echo "========================================\n";
