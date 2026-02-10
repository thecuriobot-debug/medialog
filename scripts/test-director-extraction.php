#!/usr/bin/env php
<?php
/**
 * Fix director extraction - current data is wrong
 * Re-fetch with better regex patterns
 */

require_once __DIR__ . '/../config.php';

echo "🎬 Fixing director extraction...\n\n";

$pdo = getDB();

// Get all movies
$stmt = $pdo->query("
    SELECT id, title, url 
    FROM posts 
    WHERE site_id = 6 
    ORDER BY publish_date DESC
    LIMIT 5
");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Testing with " . count($movies) . " movies\n\n";

foreach ($movies as $movie) {
    echo "Processing: {$movie['title']}\n";
    
    // Extract slug from URL
    if (!preg_match('/letterboxd\.com\/[^\/]+\/film\/([^\/]+)/', $movie['url'], $matches)) {
        continue;
    }
    
    $slug = $matches[1];
    $filmUrl = "https://letterboxd.com/film/{$slug}/";
    
    echo "Fetching: {$filmUrl}\n";
    
    // Fetch the page
    $html = @file_get_contents($filmUrl, false, stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
        ]
    ]));
    
    if (!$html) {
        continue;
    }
    
    // Try multiple patterns for director
    $director = null;
    
    // Pattern 1: Look for "Directed by" text
    if (preg_match('/Directed by<\/a>\s*<div[^>]*>\s*<p>\s*<a[^>]*>([^<]+)<\/a>/is', $html, $match)) {
        $director = trim($match[1]);
        echo "Pattern 1 found: {$director}\n";
    }
    
    // Pattern 2: Microdata format
    if (!$director && preg_match('/<a[^>]*itemprop="url"[^>]*>\s*<span[^>]*itemprop="name">([^<]+)<\/span>/is', $html, $match)) {
        $director = trim($match[1]);
        echo "Pattern 2 found: {$director}\n";
    }
    
    // Pattern 3: Crew section
    if (!$director && preg_match('/<h3>Director<\/h3>.*?<a[^>]*>([^<]+)<\/a>/is', $html, $match)) {
        $director = trim($match[1]);
        echo "Pattern 3 found: {$director}\n";
    }
    
    echo "Final director: " . ($director ?: "NOT FOUND") . "\n\n";
    
    sleep(2);
}
