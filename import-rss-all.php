<?php
require_once 'config.php';

echo "Letterboxd RSS Complete Import\n";
echo "================================\n\n";

$pdo = getDB();
$username = 'thunt';

// Letterboxd RSS can show more with different parameters
$rssUrl = "https://letterboxd.com/{$username}/rss/";

echo "Fetching RSS feed: {$rssUrl}\n";

$xml = @simplexml_load_file($rssUrl);
if (!$xml) {
    die("Failed to load RSS feed\n");
}

echo "Found " . count($xml->channel->item) . " items in RSS\n\n";

$imported = 0;
$updated = 0;

foreach ($xml->channel->item as $item) {
    $title = (string)$item->title;
    $link = (string)$item->link;
    $description = (string)$item->description;
    $pubDate = (string)$item->pubDate;
    
    echo substr($title, 0, 50) . "... ";
    
    // Check if exists
    $urlHash = hash('sha256', $link);
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE url_hash = ?");
    $stmt->execute([$urlHash]);
    
    if ($stmt->fetch()) {
        echo "EXISTS\n";
        continue;
    }
    
    // Extract image
    $imageUrl = '';
    if (preg_match('/<img[^>]+src="([^"]+)"/', $description, $matches)) {
        $imageUrl = $matches[1];
    }
    
    // Clean description
    $cleanDesc = strip_tags($description);
    $cleanDesc = substr($cleanDesc, 0, 300);
    
    $date = date('Y-m-d H:i:s', strtotime($pubDate));
    
    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO posts (site_id, title, url, url_hash, publish_date, description, image_url, full_content)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        6,
        $title,
        $link,
        $urlHash,
        $date,
        $cleanDesc,
        $imageUrl,
        $description
    ]);
    
    echo "IMPORTED\n";
    $imported++;
}

echo "\n✓ Imported {$imported} new movies from RSS\n";

$total = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6")->fetchColumn();
echo "Total Letterboxd movies: {$total}\n";
