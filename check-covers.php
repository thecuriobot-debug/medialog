<?php
require_once 'config.php';

echo "📚 Quick Cover Source Check\n";
echo "===========================\n\n";

$pdo = getDB();

// Count by source
$stats = [
    'goodreads' => 0,
    'openlibrary' => 0,
    'none' => 0,
    'other' => 0
];

$stmt = $pdo->query("
    SELECT id, title, image_url 
    FROM posts 
    WHERE site_id = 7
");

$suspicious = [];

while ($book = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $title = preg_replace('/ by .*$/', '', $book['title']);
    $title = preg_replace('/ - ★+$/', '', $title);
    
    if (!$book['image_url']) {
        $stats['none']++;
    } elseif (strpos($book['image_url'], 'gr-assets.com') !== false) {
        $stats['goodreads']++;
    } elseif (strpos($book['image_url'], 'openlibrary.org') !== false) {
        $stats['openlibrary']++;
        $suspicious[] = [
            'id' => $book['id'],
            'title' => $title,
            'url' => $book['image_url']
        ];
    } else {
        $stats['other']++;
    }
}

echo "📊 Cover Sources:\n";
echo "=================\n";
echo "✅ Goodreads (i.gr-assets.com): {$stats['goodreads']}\n";
echo "⚠️  Open Library (openlibrary.org): {$stats['openlibrary']}\n";
echo "❌ No cover: {$stats['none']}\n";
echo "❓ Other: {$stats['other']}\n\n";

if (count($suspicious) > 0) {
    echo "⚠️  Books using Open Library (potentially wrong covers):\n";
    echo "========================================================\n\n";
    
    foreach (array_slice($suspicious, 0, 20) as $book) {
        echo "ID: {$book['id']}\n";
        echo "Title: {$book['title']}\n";
        echo "Cover: {$book['url']}\n";
        echo "Fix: php verify-covers.php\n\n";
    }
    
    if (count($suspicious) > 20) {
        echo "... and " . (count($suspicious) - 20) . " more\n\n";
    }
    
    echo "💡 To fix these covers, run:\n";
    echo "   php verify-covers.php\n";
}

echo "\n";
