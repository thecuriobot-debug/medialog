<?php
require_once 'config.php';

if ($argc < 2) {
    die("Usage: php import-letterboxd-csv.php <path-to-diary.csv>\n");
}

$csvFile = $argv[1];

if (!file_exists($csvFile)) {
    die("Error: File not found: {$csvFile}\n");
}

echo "Letterboxd CSV Import\n";
echo "=====================\n\n";
echo "File: {$csvFile}\n\n";

$pdo = getDB();

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("Error: Could not open CSV file\n");
}

// Read header
$headers = fgetcsv($handle);
echo "CSV Headers: " . implode(', ', $headers) . "\n\n";

// Find column indices
$colMap = array_flip($headers);

$imported = 0;
$updated = 0;
$skipped = 0;
$row = 0;

while (($data = fgetcsv($handle)) !== false) {
    $row++;
    
    // Extract data from CSV
    $filmName = $data[$colMap['Name']] ?? '';
    $year = $data[$colMap['Year']] ?? '';
    $letterboxdURI = $data[$colMap['Letterboxd URI']] ?? '';
    $rating = $data[$colMap['Rating']] ?? '';
    $watchedDate = $data[$colMap['Watched Date']] ?? '';
    $review = $data[$colMap['Review']] ?? '';
    $rewatch = isset($colMap['Rewatch']) ? ($data[$colMap['Rewatch']] ?? '') : '';
    
    if (!$filmName || !$letterboxdURI) {
        $skipped++;
        continue;
    }
    
    echo sprintf("[%d] %s (%s)... ", $row, substr($filmName, 0, 40), $year);
    
    // Build title with year and rating
    $fullTitle = $filmName;
    if ($year) {
        $fullTitle .= ", {$year}";
    }
    if ($rating) {
        $stars = str_repeat('★', (int)$rating);
        $fullTitle .= " - {$stars}";
    }
    if ($rewatch === 'Yes') {
        $fullTitle .= " 🔁";
    }
    
    // Check if exists
    $urlHash = hash('sha256', $letterboxdURI);
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE url_hash = ?");
    $stmt->execute([$urlHash]);
    $existing = $stmt->fetch();
    
    // Format watch date
    $publishDate = $watchedDate ? date('Y-m-d H:i:s', strtotime($watchedDate)) : date('Y-m-d H:i:s');
    
    // Build description
    $description = '';
    if ($rating) {
        $description = "Rated: " . str_repeat('★', (int)$rating);
    }
    if ($rewatch === 'Yes') {
        $description .= " (Rewatch)";
    }
    
    if ($existing) {
        // Update if we have a review and don't have one yet
        $stmt = $pdo->prepare("
            UPDATE posts 
            SET title = ?,
                full_content = COALESCE(NULLIF(full_content, ''), ?),
                publish_date = ?,
                description = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $fullTitle,
            $review ?: null,
            $publishDate,
            $description,
            $existing['id']
        ]);
        echo "UPDATED\n";
        $updated++;
    } else {
        // Insert new
        $stmt = $pdo->prepare("
            INSERT INTO posts (site_id, title, url, url_hash, publish_date, description, full_content)
            VALUES (6, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $fullTitle,
            $letterboxdURI,
            $urlHash,
            $publishDate,
            $description,
            $review ?: null
        ]);
        echo "IMPORTED\n";
        $imported++;
    }
}

fclose($handle);

echo "\n" . str_repeat("=", 50) . "\n";
echo "✓ Import Complete!\n\n";
echo "Imported: {$imported} new films\n";
echo "Updated:  {$updated} existing films\n";
echo "Skipped:  {$skipped} rows\n";
echo "Total:    {$row} rows processed\n";

// Show stats
$total = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6")->fetchColumn();
$withReviews = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6 AND full_content IS NOT NULL AND LENGTH(full_content) > 10")->fetchColumn();
$withRatings = $pdo->query("SELECT COUNT(*) FROM posts WHERE site_id = 6 AND title LIKE '%★%'")->fetchColumn();

echo "\n" . str_repeat("=", 50) . "\n";
echo "DATABASE STATS:\n";
echo "Total films:       {$total}\n";
echo "With reviews:      {$withReviews}\n";
echo "With star ratings: {$withRatings}\n";
