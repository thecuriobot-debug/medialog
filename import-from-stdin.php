<?php
// Import Goodreads CSV - reads from stdin piped data
require_once 'config.php';

echo "Goodreads CSV Importer\n";
echo "======================\n\n";

// Read CSV from stdin
$handle = STDIN;
$header = fgetcsv($handle);

if (!$header) {
    die("Error: No CSV data received\n");
}

$columnMap = array_flip($header);
$pdo = getDB();

// Clear existing Goodreads posts
echo "Clearing existing Goodreads posts...\n";
$pdo->exec("DELETE FROM posts WHERE site_id = 6");
echo "✓ Cleared\n\n";

// Prepare insert statement
$stmt = $pdo->prepare(
    "INSERT INTO posts (site_id, title, url, url_hash, publish_date, description, image_url, full_content) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

$imported = 0;

while (($row = fgetcsv($handle)) !== false) {
    $bookId = $row[$columnMap['Book Id']] ?? '';
    $title = $row[$columnMap['Title']] ?? '';
    $author = $row[$columnMap['Author']] ?? '';
    $myRating = $row[$columnMap['My Rating']] ?? '0';
    $dateRead = $row[$columnMap['Date Read']] ?? '';
    $dateAdded = $row[$columnMap['Date Added']] ?? '';
    $myReview = $row[$columnMap['My Review']] ?? '';
    $publisher = $row[$columnMap['Publisher']] ?? '';
    $yearPublished = $row[$columnMap['Year Published']] ?? '';
    $numPages = $row[$columnMap['Number of Pages']] ?? '';
    
    if (empty($title)) continue;
    
    // Format title with rating
    $stars = str_repeat('★', (int)$myRating);
    $fullTitle = $stars ? "{$title} by {$author} - {$stars}" : "{$title} by {$author}";
    
    // Description
    $descParts = [];
    if ($yearPublished) $descParts[] = "Published: {$yearPublished}";
    if ($publisher) $descParts[] = "Publisher: {$publisher}";
    if ($numPages) $descParts[] = "{$numPages} pages";
    $description = implode(' · ', $descParts);
    if (strlen($description) > 300) {
        $description = substr($description, 0, 297) . '...';
    }
    
    // Full content
    $fullContent = $myReview ?: "Read " . ($dateRead ?: $dateAdded);
    
    // Parse date
    $date = $dateRead ?: $dateAdded ?: date('Y-m-d H:i:s');
    if (strtotime($date)) {
        $date = date('Y-m-d H:i:s', strtotime($date));
    } else {
        $date = date('Y-m-d H:i:s');
    }
    
    // URL
    $localUrl = "review.php?id={$bookId}";
    $urlHash = hash('sha256', $localUrl);
    
    try {
        $stmt->execute([
            6, // site_id for Goodreads
            $fullTitle,
            $localUrl,
            $urlHash,
            $date,
            $description,
            '', // image_url (will be fetched by scanner)
            $fullContent
        ]);
        
        $imported++;
        if ($imported % 100 == 0) {
            echo "Imported {$imported} books...\n";
        }
    } catch (PDOException $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n✓ Import complete!\n";
echo "Total books imported: {$imported}\n";
echo "\nVisit http://localhost:8000/hunt-hq/reviews.php to see all your reviews!\n";
