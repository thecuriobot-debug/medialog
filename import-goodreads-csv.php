<?php
require_once 'config.php';

if ($argc < 2) {
    die("Usage: php import-goodreads-csv.php <path-to-goodreads-export.csv>\n");
}

$csvFile = $argv[1];

if (!file_exists($csvFile)) {
    die("Error: File not found: $csvFile\n");
}

echo "Starting Goodreads CSV import...\n";
echo "Reading file: $csvFile\n\n";

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
$errors = 0;

// Open CSV file
$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Error: Could not open CSV file\n");
}

// Read header row
$header = fgetcsv($handle);
if (!$header) {
    die("Error: Empty CSV file\n");
}

// Map header columns
$columnMap = array_flip($header);

echo "CSV columns found: " . count($header) . "\n";
echo "Processing rows...\n\n";

$rowNum = 0;

while (($row = fgetcsv($handle)) !== false) {
    $rowNum++;
    
    // Extract fields from CSV
    $bookId = $row[$columnMap['Book Id']] ?? '';
    $title = $row[$columnMap['Title']] ?? '';
    $author = $row[$columnMap['Author']] ?? '';
    $authorLF = $row[$columnMap['Author l-f']] ?? '';
    $myRating = $row[$columnMap['My Rating']] ?? '0';
    $dateRead = $row[$columnMap['Date Read']] ?? '';
    $dateAdded = $row[$columnMap['Date Added']] ?? '';
    $myReview = $row[$columnMap['My Review']] ?? '';
    $isbn = $row[$columnMap['ISBN']] ?? '';
    $isbn13 = $row[$columnMap['ISBN13']] ?? '';
    $publisher = $row[$columnMap['Publisher']] ?? '';
    $yearPublished = $row[$columnMap['Year Published']] ?? '';
    $numPages = $row[$columnMap['Number of Pages']] ?? '';
    
    // Skip if no title
    if (empty($title)) {
        continue;
    }
    
    // Format title with author and rating
    $stars = str_repeat('★', (int)$myRating);
    if ($stars) {
        $fullTitle = "{$title} by {$author} - {$stars}";
    } else {
        $fullTitle = "{$title} by {$author}";
    }
    
    // Create description from metadata
    $descParts = [];
    if ($yearPublished) {
        $descParts[] = "Published: {$yearPublished}";
    }
    if ($publisher) {
        $descParts[] = "Publisher: {$publisher}";
    }
    if ($numPages) {
        $descParts[] = "{$numPages} pages";
    }
    $description = implode(' · ', $descParts);
    if (strlen($description) > 300) {
        $description = substr($description, 0, 297) . '...';
    }
    
    // Full content (review or description)
    $fullContent = '';
    if ($myReview) {
        $fullContent = nl2br(htmlspecialchars($myReview));
    } else {
        $fullContent = "<p>Read " . ($dateRead ?: $dateAdded) . "</p>";
        if ($description) {
            $fullContent .= "<p>{$description}</p>";
        }
    }
    
    // Get book cover from Goodreads
    $imageUrl = '';
    if ($bookId) {
        // Goodreads image URLs follow a pattern
        $imageUrl = "https://covers.openlibrary.org/b/id/{$bookId}-M.jpg";
    }
    
    // Parse date
    $date = null;
    if ($dateRead) {
        $date = date('Y-m-d H:i:s', strtotime($dateRead));
    } elseif ($dateAdded) {
        $date = date('Y-m-d H:i:s', strtotime($dateAdded));
    } else {
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
            $description,
            $imageUrl,
            $fullContent
        ]);
        
        if ($stmt->rowCount() > 0) {
            $imported++;
            if ($imported % 10 == 0) {
                echo "Imported {$imported} reviews...\n";
            }
        } else {
            $skipped++;
        }
    } catch (PDOException $e) {
        $errors++;
        echo "✗ Error importing row {$rowNum} ({$title}): " . $e->getMessage() . "\n";
    }
}

fclose($handle);

echo "\n";
echo "==========================================\n";
echo "Import Complete!\n";
echo "==========================================\n";
echo "Total rows processed: {$rowNum}\n";
echo "Imported: {$imported}\n";
echo "Skipped (duplicates): {$skipped}\n";
echo "Errors: {$errors}\n";
echo "\n";
echo "Visit http://localhost:8000/hunt-hq/reviews.php to see all your reviews!\n";
