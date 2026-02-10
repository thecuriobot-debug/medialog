<?php
require_once 'config.php';

echo "Starting Goodreads import...\n\n";

$rssUrl = "https://www.goodreads.com/review/list_rss/3484613";

echo "Fetching RSS feed from: $rssUrl\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $rssUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
    CURLOPT_SSL_VERIFYPEER => false
]);

$rssData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200 || !$rssData) {
    die("Failed to fetch RSS feed. HTTP Code: $httpCode\n");
}

echo "RSS feed fetched successfully!\n";
echo "Data size: " . strlen($rssData) . " bytes\n\n";

$xml = @simplexml_load_string($rssData);
if (!$xml || !isset($xml->channel->item)) {
    die("Failed to parse XML\n");
}

$totalItems = count($xml->channel->item);
echo "Found $totalItems reviews in RSS feed\n\n";

// Get Goodreads site ID
$pdo = getDB();
$stmt = $pdo->query("SELECT id FROM sites WHERE name = 'Goodreads'");
$site = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$site) {
    die("Goodreads site not found in database\n");
}

$siteId = $site['id'];

// Prepare insert statement
$stmt = $pdo->prepare(
    "INSERT IGNORE INTO posts (site_id, title, url, url_hash, publish_date, description, image_url, full_content) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

$imported = 0;
$skipped = 0;

foreach ($xml->channel->item as $item) {
    $title = (string)$item->title;
    $link = (string)$item->link;
    $pubDate = (string)$item->pubDate;
    $authorName = (string)$item->author_name;
    $userRating = (string)$item->user_rating;
    $bookDesc = (string)$item->book_description;
    $userReadAt = (string)$item->user_read_at;
    $userReview = (string)$item->user_review;
    $bookId = (string)$item->book_id;
    
    // Get book cover image
    $imageUrl = (string)$item->book_medium_image_url;
    if (!$imageUrl) {
        $imageUrl = (string)$item->book_image_url;
    }
    
    // Format title with author and rating
    $stars = str_repeat('★', (int)$userRating);
    if ($stars) {
        $fullTitle = "{$title} by {$authorName} - {$stars}";
    } else {
        $fullTitle = "{$title} by {$authorName}";
    }
    
    // Clean description
    $cleanDesc = strip_tags($bookDesc);
    $cleanDesc = html_entity_decode($cleanDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);
    $cleanDesc = trim(substr($cleanDesc, 0, 300));
    
    // Store full review
    $fullContent = '';
    if ($userReview) {
        $fullContent = $userReview;
    } else {
        $fullContent = $bookDesc;
    }
    
    // Parse date
    $date = null;
    if ($userReadAt) {
        $date = date('Y-m-d H:i:s', strtotime($userReadAt));
    }
    if (!$date && $pubDate) {
        $date = date('Y-m-d H:i:s', strtotime($pubDate));
    }
    if (!$date) {
        $date = date('Y-m-d H:i:s');
    }
    
    // Create local URL
    $localUrl = "review.php?id={$bookId}";
    $urlHash = hash('sha256', $localUrl);
    
    try {
        $stmt->execute([
            $siteId,
            $fullTitle,
            $localUrl,
            $urlHash,
            $date,
            $cleanDesc,
            $imageUrl,
            $fullContent
        ]);
        
        if ($stmt->rowCount() > 0) {
            $imported++;
            echo "✓ Imported: $fullTitle\n";
        } else {
            $skipped++;
            echo "- Skipped (duplicate): $fullTitle\n";
        }
    } catch (PDOException $e) {
        echo "✗ Error importing $title: " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "==========================================\n";
echo "Import Complete!\n";
echo "==========================================\n";
echo "Total reviews in feed: $totalItems\n";
echo "Imported: $imported\n";
echo "Skipped (duplicates): $skipped\n";
echo "\n";
echo "Note: Goodreads RSS feeds typically show the most recent ~30-50 reviews.\n";
echo "To get your full reading history, you may need to export from Goodreads settings.\n";
