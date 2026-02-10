<?php
require_once 'config.php';

echo "Letterboxd Review Cleaner\n";
echo "==========================\n\n";

$pdo = getDB();

// Get all movies with reviews
$stmt = $pdo->query("SELECT id, title, full_content FROM posts WHERE site_id = 6 AND full_content IS NOT NULL");
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($movies) . " movies with content.\n";
echo "Cleaning reviews...\n\n";

$cleaned = 0;

foreach ($movies as $movie) {
    $content = $movie['full_content'];
    
    // Remove "thunt's review published on Letterboxd:" prefix
    $content = preg_replace('#^.*?thunt\'s review published on Letterboxd:\s*#s', '', $content);
    
    // Remove all <p> tags but keep their content and add line breaks
    $content = preg_replace('#<p>(.*?)</p>#s', "$1\n\n", $content);
    
    // Remove any remaining HTML tags
    $content = strip_tags($content);
    
    // Decode HTML entities
    $content = html_entity_decode($content);
    
    // Clean up whitespace
    $content = preg_replace('/\n{3,}/', "\n\n", $content);
    $content = trim($content);
    
    // Only update if we actually cleaned something
    if ($content !== $movie['full_content']) {
        $stmt = $pdo->prepare("UPDATE posts SET full_content = ? WHERE id = ?");
        $stmt->execute([$content, $movie['id']]);
        
        $oldLen = strlen($movie['full_content']);
        $newLen = strlen($content);
        
        echo substr($movie['title'], 0, 50) . "... ";
        echo "CLEANED ({$oldLen} → {$newLen} chars)\n";
        $cleaned++;
    }
}

echo "\n✓ Cleaned {$cleaned} movie reviews\n";

// Show sample
$sample = $pdo->query("SELECT title, full_content FROM posts WHERE site_id = 6 AND LENGTH(full_content) > 1000 ORDER BY publish_date DESC LIMIT 1")->fetch();
echo "\nSample cleaned review:\n";
echo str_repeat("=", 50) . "\n";
echo $sample['title'] . "\n";
echo str_repeat("=", 50) . "\n";
echo substr($sample['full_content'], 0, 500) . "...\n";
