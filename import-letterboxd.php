<?php
require_once 'config.php';

echo "Letterboxd Full Import\n";
echo "======================\n\n";

$pdo = getDB();

// Get Letterboxd site
$site = $pdo->query("SELECT * FROM sites WHERE id = 6")->fetch();
$username = 'thunt'; // From the URL

$rssUrl = "https://letterboxd.com/{$username}/rss/";
echo "Fetching RSS: {$rssUrl}\n\n";

// Fetch RSS
$xml = @simplexml_load_file($rssUrl);
if (!$xml) {
    die("Failed to load RSS feed\n");
}

$imported = 0;
$skipped = 0;

foreach ($xml->channel->item as $item) {
    $title = (string)$item->title;
    $link = (string)$item->link;
    $description = (string)$item->description;
    $pubDate = (string)$item->pubDate;
    
    // Extract rating from title (e.g., "Movie Title, 2024 - ★★★★")
    $stars = substr_count($title, '★');
    
    // Generate URL hash
    $urlHash = hash('sha256', $link);
    
    // Check if already exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE url_hash = ?");
    $stmt->execute([$urlHash]);
    
    if ($stmt->fetch()) {
        $skipped++;
        continue;
    }
    
    // Parse description for movie poster
    $imageUrl = '';
    if (preg_match('/<img[^>]+src="([^"]+)"/', $description, $matches)) {
        $imageUrl = $matches[1];
    }
    
    // Clean description
    $cleanDesc = strip_tags($description);
    $cleanDesc = substr($cleanDesc, 0, 300);
    
    // Convert date
    $date = date('Y-m-d H:i:s', strtotime($pubDate));
    
    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO posts (site_id, title, url, url_hash, publish_date, description, image_url, full_content)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        6, // Letterboxd site_id
        $title,
        $link,
        $urlHash,
        $date,
        $cleanDesc,
        $imageUrl,
        $description
    ]);
    
    $imported++;
    
    if ($imported % 10 == 0) {
        echo "Imported {$imported} movies...\n";
    }
}

echo "\n✓ Import complete!\n";
echo "Imported: {$imported} new movies\n";
echo "Skipped: {$skipped} existing movies\n";
echo "\nTotal Letterboxd movies in database: " . $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6")->fetchColumn() . "\n";
