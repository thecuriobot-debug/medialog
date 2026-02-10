<?php
require_once 'config.php';

echo "📥 Letterboxd CSV Import\n";
echo "========================\n\n";

// Check for CSV file
$csvFile = __DIR__ . '/letterboxd-data.csv';
if (!file_exists($csvFile)) {
    echo "❌ Error: letterboxd-data.csv not found!\n\n";
    echo "Please:\n";
    echo "1. Go to https://letterboxd.com/settings/data/\n";
    echo "2. Click 'Export Your Data'\n";
    echo "3. Download the ZIP file from your email\n";
    echo "4. Extract 'watched.csv' or 'diary.csv'\n";
    echo "5. Rename it to 'letterboxd-data.csv'\n";
    echo "6. Place it in: " . __DIR__ . "/\n";
    exit(1);
}

$pdo = getDB();

echo "📂 Reading CSV file...\n";
$file = fopen($csvFile, 'r');
$header = fgetcsv($file); // Read header row

echo "📋 CSV Columns: " . implode(', ', $header) . "\n\n";

// Detect which CSV type this is
$isWatched = in_array('Name', $header);
$isDiary = in_array('Date', $header);

if (!$isWatched && !$isDiary) {
    echo "❌ Error: Unrecognized CSV format\n";
    echo "Expected columns: Name, Year, Letterboxd URI, Rating\n";
    echo "OR: Date, Name, Year, Letterboxd URI, Rating\n";
    exit(1);
}

echo "✅ Detected: " . ($isDiary ? "Diary CSV" : "Watched CSV") . "\n\n";

$imported = 0;
$updated = 0;
$skipped = 0;

while (($row = fgetcsv($file)) !== false) {
    $data = array_combine($header, $row);
    
    // Extract data
    $name = $data['Name'];
    $year = $data['Year'];
    $letterboxdUri = $data['Letterboxd URI'];
    $rating = isset($data['Rating']) ? floatval($data['Rating']) : 0;
    
    // Get date
    if ($isDiary && isset($data['Date'])) {
        $watchDate = $data['Date']; // Format: YYYY-MM-DD
    } else {
        // For watched.csv without dates, use current date
        $watchDate = date('Y-m-d');
    }
    
    // Get rewatch status
    $isRewatch = isset($data['Rewatch']) && strtolower($data['Rewatch']) === 'yes';
    
    // Get review
    $review = isset($data['Review']) ? $data['Review'] : '';
    $tags = isset($data['Tags']) ? $data['Tags'] : '';
    
    // Build full URL
    $url = 'https://letterboxd.com' . $letterboxdUri;
    $urlHash = hash('sha256', $url);
    
    // Build title with stars
    $stars = '';
    if ($rating > 0) {
        $starCount = round($rating);
        $stars = ' - ' . str_repeat('★', $starCount);
    }
    $title = "{$name}, {$year}{$stars}";
    
    // Build description
    $description = "Watched on {$watchDate}";
    if ($rating > 0) {
        $description .= " - Rating: {$rating}/5";
    }
    if ($isRewatch) {
        $description .= " (Rewatch)";
    }
    if ($tags) {
        $description .= " - Tags: {$tags}";
    }
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM posts WHERE url_hash = ?");
    $stmt->execute([$urlHash]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing entry
        $stmt = $pdo->prepare("
            UPDATE posts SET
                title = ?,
                description = ?,
                full_content = ?,
                publish_date = ?
            WHERE url_hash = ?
        ");
        $stmt->execute([
            $title,
            $description,
            $review,
            $watchDate . ' 00:00:00',
            $urlHash
        ]);
        $updated++;
        echo "📝 Updated: {$name} ({$year})\n";
    } else {
        // Insert new entry
        $stmt = $pdo->prepare("
            INSERT INTO posts (
                site_id, title, url, url_hash, description, 
                full_content, publish_date, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            6, // Letterboxd site_id
            $title,
            $url,
            $urlHash,
            $description,
            $review,
            $watchDate . ' 00:00:00'
        ]);
        $imported++;
        echo "✅ Imported: {$name} ({$year}) - {$watchDate}\n";
    }
}

fclose($file);

echo "\n";
echo "========================\n";
echo "📊 Import Summary:\n";
echo "========================\n";
echo "✅ Imported: {$imported} new movies\n";
echo "📝 Updated:  {$updated} existing movies\n";
echo "⏭️  Skipped:  {$skipped} duplicates\n";
echo "\n";
echo "🎬 Total processed: " . ($imported + $updated + $skipped) . "\n";
echo "\n";
echo "✨ Import complete!\n";
