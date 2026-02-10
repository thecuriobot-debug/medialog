<?php
require_once 'config.php';

echo "Letterboxd Full Review Import\n";
echo "==============================\n\n";

$pdo = getDB();
$username = 'thunt';

// Function to fetch and parse a Letterboxd page
function fetchLetterboxdPage($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36');
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

// Fetch diary pages to get all films
echo "Fetching your Letterboxd diary...\n";
$page = 1;
$allFilms = [];

while ($page <= 10) { // Limit to 10 pages for safety
    echo "  Page {$page}...\n";
    $url = "https://letterboxd.com/{$username}/films/diary/page/{$page}/";
    $html = fetchLetterboxdPage($url);
    
    if (!$html || strpos($html, 'film-poster') === false) {
        echo "  No more films found.\n";
        break;
    }
    
    // Parse film URLs from the page
    preg_match_all('#<a href="/film/([^"]+)/"#', $html, $matches);
    
    if (empty($matches[1])) {
        break;
    }
    
    foreach ($matches[1] as $filmSlug) {
        $allFilms[$filmSlug] = true;
    }
    
    $page++;
    sleep(1); // Be nice to Letterboxd servers
}

echo "\nFound " . count($allFilms) . " total films in your diary.\n\n";

// Now fetch details for each film
$imported = 0;
$updated = 0;
$skipped = 0;

foreach (array_keys($allFilms) as $filmSlug) {
    $filmUrl = "https://letterboxd.com/{$username}/film/{$filmSlug}/";
    
    // Check if we already have this film
    $stmt = $pdo->prepare("SELECT id, full_content FROM posts WHERE site_id = 6 AND url LIKE ?");
    $stmt->execute(["%{$filmSlug}%"]);
    $existing = $stmt->fetch();
    
    echo "Processing: {$filmSlug}... ";
    
    // Fetch the film page
    $html = fetchLetterboxdPage($filmUrl);
    
    if (!$html) {
        echo "FAILED (couldn't fetch)\n";
        $skipped++;
        continue;
    }
    
    // Extract review text
    $review = '';
    if (preg_match('#<div class="body-text -prose -hero -attached-above"[^>]*>(.*?)</div>\s*</div>#s', $html, $matches)) {
        $review = $matches[1];
        $review = strip_tags($review, '<p><br>');
        $review = html_entity_decode($review);
        $review = trim($review);
    }
    
    // Extract other details
    $title = '';
    if (preg_match('#<meta property="og:title" content="([^"]+)"#', $html, $matches)) {
        $title = html_entity_decode($matches[1]);
    }
    
    $image = '';
    if (preg_match('#<meta property="og:image" content="([^"]+)"#', $html, $matches)) {
        $image = $matches[1];
    }
    
    $description = '';
    if (preg_match('#<meta name="description" content="([^"]+)"#', $html, $matches)) {
        $description = html_entity_decode($matches[1]);
    }
    
    // Extract watch date
    $watchDate = '';
    if (preg_match('#<time[^>]+datetime="([^"]+)"#', $html, $matches)) {
        $watchDate = date('Y-m-d H:i:s', strtotime($matches[1]));
    }
    
    // Extract rating
    $rating = '';
    if (preg_match('#data-rating="(\d+)"#', $html, $matches)) {
        $stars = intval($matches[1]) / 2; // Convert to 1-5 scale
        $rating = str_repeat('★', $stars);
    }
    
    // Add rating to title if we found it
    if ($title && $rating) {
        // Check if rating already in title
        if (strpos($title, '★') === false) {
            $title .= ' - ' . $rating;
        }
    }
    
    if ($existing) {
        // Update existing entry
        $stmt = $pdo->prepare("
            UPDATE posts 
            SET full_content = ?,
                description = ?,
                image_url = ?,
                title = ?,
                publish_date = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $review ?: null,
            $description,
            $image,
            $title ?: $existing['title'],
            $watchDate ?: null,
            $existing['id']
        ]);
        
        if ($review) {
            echo "UPDATED (review found)\n";
            $updated++;
        } else {
            echo "UPDATED (no review)\n";
            $updated++;
        }
    } else {
        // Insert new entry
        $urlHash = hash('sha256', $filmUrl);
        
        $stmt = $pdo->prepare("
            INSERT INTO posts (site_id, title, url, url_hash, publish_date, description, image_url, full_content)
            VALUES (6, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $title,
            $filmUrl,
            $urlHash,
            $watchDate,
            $description,
            $image,
            $review ?: null
        ]);
        
        echo "IMPORTED\n";
        $imported++;
    }
    
    sleep(1); // Be nice to Letterboxd
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✓ Complete!\n\n";
echo "Imported: {$imported} new films\n";
echo "Updated:  {$updated} existing films\n";
echo "Skipped:  {$skipped} films\n";

// Show stats
$withReviews = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6 AND full_content IS NOT NULL AND full_content != ''")->fetchColumn();
$total = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6")->fetchColumn();

echo "\nTotal Letterboxd movies: {$total}\n";
echo "Movies with reviews: {$withReviews}\n";
echo "Movies without reviews: " . ($total - $withReviews) . "\n";
