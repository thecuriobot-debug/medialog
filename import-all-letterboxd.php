<?php
require_once 'config.php';

echo "Letterboxd Complete Import\n";
echo "===========================\n\n";

$pdo = getDB();
$username = 'thunt';

function fetchPage($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode != 200) {
        return false;
    }
    
    return $html;
}

// Step 1: Scrape the films page to get ALL movies
echo "Step 1: Finding all your watched films...\n";
echo str_repeat("-", 50) . "\n";

$allFilms = [];
$page = 1;
$maxPages = 50; // Safety limit

while ($page <= $maxPages) {
    $url = "https://letterboxd.com/{$username}/films/page/{$page}/";
    echo "  Scanning page {$page}... ";
    
    $html = fetchPage($url);
    
    if (!$html) {
        echo "ERROR\n";
        break;
    }
    
    // Extract film slugs and metadata from poster grid
    preg_match_all('#<li class="poster-container[^"]*".*?data-film-slug="([^"]+)".*?data-film-name="([^"]+)".*?(?:data-owner-rating="([^"]+)")?#s', $html, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) {
        echo "DONE (no more films)\n";
        break;
    }
    
    $foundOnPage = 0;
    foreach ($matches as $match) {
        $slug = $match[1];
        $name = html_entity_decode($match[2]);
        $rating = isset($match[3]) ? (int)$match[3] : 0;
        
        if (!isset($allFilms[$slug])) {
            $allFilms[$slug] = [
                'name' => $name,
                'rating' => $rating,
                'slug' => $slug
            ];
            $foundOnPage++;
        }
    }
    
    echo "FOUND {$foundOnPage} films\n";
    
    if ($foundOnPage == 0) {
        break;
    }
    
    $page++;
    sleep(1);
}

$totalFilms = count($allFilms);
echo "\n✓ Found {$totalFilms} total films!\n\n";

// Step 2: Get detailed information for each film
echo "Step 2: Fetching detailed information...\n";
echo str_repeat("-", 50) . "\n";

$imported = 0;
$updated = 0;
$skipped = 0;
$current = 0;

foreach ($allFilms as $filmData) {
    $current++;
    $slug = $filmData['slug'];
    $filmUrl = "https://letterboxd.com/{$username}/film/{$slug}/";
    
    echo sprintf("[%d/%d] %s... ", $current, $totalFilms, substr($filmData['name'], 0, 40));
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id, full_content FROM posts WHERE site_id = 6 AND url LIKE ?");
    $stmt->execute(["%{$slug}%"]);
    $existing = $stmt->fetch();
    
    // Fetch the film review page
    $html = fetchPage($filmUrl);
    
    if (!$html) {
        echo "FAILED\n";
        $skipped++;
        sleep(1);
        continue;
    }
    
    // Extract all data
    $title = $filmData['name'];
    $rating = $filmData['rating'];
    
    // Get year from page
    $year = '';
    if (preg_match('#<meta property="og:title" content="[^"]+ \((\d{4})\)"#', $html, $m)) {
        $year = $m[1];
    }
    
    // Build title with year and rating
    $fullTitle = $title;
    if ($year) {
        $fullTitle .= ", {$year}";
    }
    if ($rating > 0) {
        $stars = str_repeat('★', $rating / 2);
        $fullTitle .= " - {$stars}";
    }
    
    // Get poster image
    $image = '';
    if (preg_match('#<meta property="og:image" content="([^"]+)"#', $html, $m)) {
        $image = $m[1];
    }
    
    // Get watch date
    $watchDate = '';
    if (preg_match('#<time[^>]+datetime="([^"]+)"#', $html, $m)) {
        $watchDate = date('Y-m-d H:i:s', strtotime($m[1]));
    }
    
    // Get review text
    $review = '';
    if (preg_match('#<div class="body-text[^"]*-prose[^"]*"[^>]*>(.*?)</div>\s*</div>#s', $html, $m)) {
        $review = $m[1];
        $review = strip_tags($review, '<p><br>');
        $review = html_entity_decode($review);
        $review = trim($review);
    }
    
    // Get short description (for films without full review)
    $description = '';
    if (preg_match('#<meta name="description" content="([^"]+)"#', $html, $m)) {
        $description = html_entity_decode($m[1]);
    }
    
    // Get liked status
    $liked = strpos($html, 'data-liked="true"') !== false;
    
    // Get rewatch status
    $rewatch = strpos($html, 'icon-status-rewatch') !== false;
    
    if ($existing) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE posts 
            SET title = ?,
                image_url = ?,
                publish_date = ?,
                description = ?,
                full_content = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $fullTitle,
            $image,
            $watchDate ?: $existing['publish_date'],
            $description,
            $review ?: $existing['full_content'],
            $existing['id']
        ]);
        
        echo "UPDATED\n";
        $updated++;
    } else {
        // Insert
        $urlHash = hash('sha256', $filmUrl);
        $stmt = $pdo->prepare("
            INSERT INTO posts (site_id, title, url, url_hash, publish_date, description, image_url, full_content)
            VALUES (6, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $fullTitle,
            $filmUrl,
            $urlHash,
            $watchDate,
            $description,
            $image,
            $review
        ]);
        
        echo "IMPORTED\n";
        $imported++;
    }
    
    sleep(1.5); // Be respectful to Letterboxd servers
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✓ Import Complete!\n\n";
echo "Imported:  {$imported} new films\n";
echo "Updated:   {$updated} existing films\n";
echo "Skipped:   {$skipped} films (errors)\n";
echo "Total:     " . ($imported + $updated) . " films\n";

// Show final stats
$totalInDB = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6")->fetchColumn();
$withReviews = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6 AND full_content IS NOT NULL AND LENGTH(full_content) > 50")->fetchColumn();
$withRatings = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6 AND title LIKE '%★%'")->fetchColumn();

echo "\n" . str_repeat("=", 50) . "\n";
echo "DATABASE STATS:\n";
echo "Total films:       {$totalInDB}\n";
echo "With reviews:      {$withReviews}\n";
echo "With star ratings: {$withRatings}\n";
echo "Without ratings:   " . ($totalInDB - $withRatings) . "\n";
