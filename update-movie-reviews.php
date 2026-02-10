<?php
require_once 'config.php';

echo "Letterboxd Full Review Updater\n";
echo "================================\n\n";

$pdo = getDB();

// Get all existing Letterboxd movies
$stmt = $pdo->query("SELECT id, title, url FROM posts WHERE site_id = 6 ORDER BY publish_date DESC");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($movies) . " movies in database.\n";
echo "Fetching full reviews from Letterboxd...\n\n";

function fetchPage($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode != 200) {
        return false;
    }
    
    return $html;
}

$updated = 0;
$withReview = 0;
$noReview = 0;
$failed = 0;

foreach ($movies as $movie) {
    echo "Processing: " . substr($movie['title'], 0, 50) . "... ";
    
    $html = fetchPage($movie['url']);
    
    if (!$html) {
        echo "FAILED (couldn't fetch)\n";
        $failed++;
        continue;
    }
    
    // Extract review text - multiple possible locations
    $review = '';
    
    // Try method 1: prose hero section
    if (preg_match('#<div class="body-text[^"]*-prose[^"]*"[^>]*>(.*?)</div>\s*</div>#s', $html, $matches)) {
        $review = $matches[1];
    }
    // Try method 2: review body
    elseif (preg_match('#<div[^>]*class="[^"]*review[^"]*body[^"]*"[^>]*>(.*?)</div>#s', $html, $matches)) {
        $review = $matches[1];
    }
    // Try method 3: collapsed text
    elseif (preg_match('#<div[^>]*class="[^"]*collapsed-text[^"]*"[^>]*>(.*?)</div>#s', $html, $matches)) {
        $review = $matches[1];
    }
    
    if ($review) {
        // Clean up the review
        $review = preg_replace('#<span class="has-spoilers">.*?</span>#s', '[spoilers removed]', $review);
        $review = strip_tags($review, '<p><br><em><strong>');
        $review = html_entity_decode($review);
        $review = trim($review);
        
        // Update database
        $stmt = $pdo->prepare("UPDATE posts SET full_content = ? WHERE id = ?");
        $stmt->execute([$review, $movie['id']]);
        
        echo "UPDATED (review: " . strlen($review) . " chars)\n";
        $updated++;
        $withReview++;
    } else {
        echo "NO REVIEW\n";
        $noReview++;
    }
    
    sleep(1); // Be nice to servers
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✓ Complete!\n\n";
echo "Updated:         {$updated} films with reviews\n";
echo "No review found: {$noReview} films\n";
echo "Failed:          {$failed} films\n";

// Show final stats
$totalReviews = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6 AND full_content IS NOT NULL AND LENGTH(full_content) > 50")->fetchColumn();
$total = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6")->fetchColumn();

echo "\nFinal Stats:\n";
echo "Total movies: {$total}\n";
echo "Movies with reviews: {$totalReviews}\n";
echo "Movies without reviews: " . ($total - $totalReviews) . "\n";
