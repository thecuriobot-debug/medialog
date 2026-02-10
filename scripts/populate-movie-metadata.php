#!/usr/bin/env php
<?php
/**
 * Populate movie metadata from Letterboxd
 * Fetches director, genres, and runtime for all movies
 */

require_once __DIR__ . '/../config.php';

echo "🎬 Starting movie metadata population...\n\n";

$pdo = getDB();

// Get all movies without director info
$stmt = $pdo->query("
    SELECT id, title, url 
    FROM posts 
    WHERE site_id = 6 
    AND (director IS NULL OR director = '')
    ORDER BY publish_date DESC
");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($movies) . " movies to update\n\n";

$updated = 0;
$failed = 0;

foreach ($movies as $movie) {
    echo "Processing: {$movie['title']}\n";
    echo "URL: {$movie['url']}\n";
    
    // Extract slug from URL
    if (!preg_match('/letterboxd\.com\/[^\/]+\/film\/([^\/]+)/', $movie['url'], $matches)) {
        echo "❌ Could not extract slug from URL\n\n";
        $failed++;
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
        echo "❌ Failed to fetch page\n\n";
        $failed++;
        sleep(2);
        continue;
    }
    
    // Extract director
    $director = null;
    if (preg_match('/<span itemprop="director"[^>]*>.*?<span itemprop="name">([^<]+)<\/span>/s', $html, $dirMatch)) {
        $director = trim($dirMatch[1]);
    } elseif (preg_match('/Directed by.*?<a[^>]*>([^<]+)<\/a>/is', $html, $dirMatch)) {
        $director = trim($dirMatch[1]);
    }
    
    // Extract genres
    $genres = [];
    if (preg_match_all('/<a[^>]*href="\/films\/genre\/[^"]*"[^>]*>([^<]+)<\/a>/', $html, $genreMatches)) {
        $genres = array_unique($genreMatches[1]);
    }
    $genresStr = !empty($genres) ? implode(', ', $genres) : null;
    
    // Extract runtime
    $runtime = null;
    if (preg_match('/<p class="text-link text-footer">.*?(\d+)\s*mins?/is', $html, $runtimeMatch)) {
        $runtime = (int)$runtimeMatch[1];
    } elseif (preg_match('/(\d+)\s*mins?/', $html, $runtimeMatch)) {
        $runtime = (int)$runtimeMatch[1];
    }
    
    // Update database
    $updateStmt = $pdo->prepare("
        UPDATE posts 
        SET director = :director,
            genres = :genres,
            runtime_minutes = :runtime
        WHERE id = :id
    ");
    
    $result = $updateStmt->execute([
        'director' => $director,
        'genres' => $genresStr,
        'runtime' => $runtime,
        'id' => $movie['id']
    ]);
    
    if ($result) {
        echo "✅ Updated: ";
        if ($director) echo "Director: {$director} | ";
        if ($genresStr) echo "Genres: {$genresStr} | ";
        if ($runtime) echo "Runtime: {$runtime}min";
        echo "\n\n";
        $updated++;
    } else {
        echo "❌ Failed to update database\n\n";
        $failed++;
    }
    
    // Rate limiting - be nice to Letterboxd
    sleep(2);
}

echo "\n";
echo "==========================================\n";
echo "✅ Updated: {$updated} movies\n";
echo "❌ Failed: {$failed} movies\n";
echo "==========================================\n";
