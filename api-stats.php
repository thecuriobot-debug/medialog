<?php
// API endpoint to get MediaLog statistics
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow cross-origin requests

require_once 'config.php';

$pdo = getDB();

// Get counts
$bookCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 7")->fetchColumn();
$movieCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6")->fetchColumn();
$reviewCount = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 7 AND full_content IS NOT NULL AND LENGTH(full_content) > 100")->fetchColumn();

// Calculate total pages (approximate based on unique pages)
$pageCount = 15; // Home, Books, Movies, Reviews, Creators, Insights, Visualizations, Lists, Goals, Settings, Export, Search, individual review pages

echo json_encode([
    'books' => $bookCount,
    'movies' => $movieCount,
    'reviews' => $reviewCount,
    'total_items' => $bookCount + $movieCount,
    'pages' => $pageCount,
    'timestamp' => date('Y-m-d H:i:s')
]);
