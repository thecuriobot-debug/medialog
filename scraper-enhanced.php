<?php
// Enhanced scraper with progress counter and boxd.it support
require_once 'config.php';

$pdo = getDB();

// Only fetch movies without director data
$stmt = $pdo->query("
    SELECT id, url, title 
    FROM posts 
    WHERE site_id = 6 
    AND (director IS NULL OR director = '')
");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($movies);
echo "🎬 Fetching metadata for {$total} movies without director data...\n\n";

$updated = 0;
$failed = 0;
$skipped = 0;
$current = 0;

foreach ($movies as $movie) {
    $current++;
    
    echo "[$current/$total] Processing: {$movie['title']}\n";
    
    $url = $movie['url'];
    
    // Handle different URL formats
    if (strpos($url, 'boxd.it') !== false) {
        // boxd.it short URL - use curl to follow redirect
        echo "  Following redirect from: $url\n";
        
        $curlCmd = "curl -sL -o /dev/null -w '%{url_effective}' " . escapeshellarg($url);
        $canonicalUrl = trim(shell_exec($curlCmd));
        
        if (!$canonicalUrl || strpos($canonicalUrl, 'letterboxd.com') === false) {
            echo "  ✗ Could not resolve short URL\n\n";
            $failed++;
            sleep(1);
            continue;
        }
        
        // Convert to film URL (remove username if present)
        $canonicalUrl = preg_replace('/letterboxd\.com\/[^\/]+\/film/', 'letterboxd.com/film', $canonicalUrl);
        echo "  Resolved to: $canonicalUrl\n";
    } else {
        // Regular letterboxd.com URL
        // Convert user URL to canonical film URL
        $canonicalUrl = preg_replace('/letterboxd\.com\/[^\/]+\/film/', 'letterboxd.com/film', $url);
        
        // Remove trailing /1/ or /2/ etc (review numbers)
        $canonicalUrl = preg_replace('/\/\d+\/$/', '/', $canonicalUrl);
        
        echo "  Fetching: $canonicalUrl\n";
    }
    
    // Fetch the page
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
    
    // If no director, try alternate pattern
    if (!$director && preg_match('/<span class="directorlist".*?<a[^>]*>([^<]+)<\/a>/', $html, $matches)) {
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
    
    // Progress indicator every 50 movies
    if ($current % 50 == 0) {
        $percent = round(($current / $total) * 100, 1);
        echo "📊 Progress: {$percent}% ({$current}/{$total})\n";
        echo "   ✅ Updated: {$updated} | ❌ Failed: {$failed}\n\n";
    }
    
    sleep(2); // Be nice to Letterboxd
}

echo "\n========================================\n";
echo "✨ Scraping Complete!\n";
echo "========================================\n";
echo "📊 Final Stats:\n";
echo "  Total processed: {$current}/{$total}\n";
echo "  ✅ Updated: {$updated}\n";
echo "  ❌ Failed: {$failed}\n";
echo "  ⏭️  Skipped: {$skipped}\n";
echo "========================================\n";
