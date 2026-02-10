<?php
require_once 'config.php';

echo "📚 Book Cover Verification & Update Tool\n";
echo "========================================\n\n";

$pdo = getDB();

// Get all books
$stmt = $pdo->query("
    SELECT id, title, url, image_url 
    FROM posts 
    WHERE site_id = 7 
    ORDER BY publish_date DESC
");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($books) . " books\n\n";

$updated = 0;
$verified = 0;
$failed = 0;
$openlibrary = 0;
$goodreads = 0;

foreach ($books as $book) {
    $title = preg_replace('/ by .*$/', '', $book['title']);
    $title = preg_replace('/ - ★+$/', '', $title);
    
    echo "Checking: " . substr($title, 0, 50) . "...\n";
    echo "  Current: " . ($book['image_url'] ?? 'none') . "\n";
    
    // Check if using Open Library
    if ($book['image_url'] && strpos($book['image_url'], 'openlibrary.org') !== false) {
        $openlibrary++;
        echo "  ⚠️  Using Open Library cover (potentially wrong)\n";
    } elseif ($book['image_url'] && strpos($book['image_url'], 'gr-assets.com') !== false) {
        $goodreads++;
        echo "  ✓ Using Goodreads cover (correct)\n";
        $verified++;
    }
    
    // Extract Goodreads ID from URL
    if (preg_match('/id=(\d+)/', $book['url'], $matches)) {
        $bookId = $matches[1];
        
        // Fetch Goodreads page
        $goodreadsUrl = "https://www.goodreads.com/book/show/{$bookId}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $goodreadsUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200 && $html) {
            // Try to extract cover image
            $newImageUrl = null;
            
            // Pattern 1: og:image meta tag
            if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $matches)) {
                $newImageUrl = $matches[1];
            }
            // Pattern 2: book cover img tag
            elseif (preg_match('/<img[^>]+class="[^"]*ResponsiveImage[^"]*"[^>]+src="([^"]+)"/', $html, $matches)) {
                $newImageUrl = $matches[1];
            }
            // Pattern 3: bookCoverImage
            elseif (preg_match('/<img[^>]+id="bookCoverImage"[^>]+src="([^"]+)"/', $html, $matches)) {
                $newImageUrl = $matches[1];
            }
            
            if ($newImageUrl) {
                // Check if it's different and better
                $isGoodreadsImage = strpos($newImageUrl, 'gr-assets.com') !== false;
                $currentIsOpenLibrary = strpos($book['image_url'] ?? '', 'openlibrary.org') !== false;
                
                if ($isGoodreadsImage && ($currentIsOpenLibrary || !$book['image_url'])) {
                    // Update to Goodreads image
                    $updateStmt = $pdo->prepare("UPDATE posts SET image_url = ? WHERE id = ?");
                    $updateStmt->execute([$newImageUrl, $book['id']]);
                    
                    echo "  ✅ Updated to: $newImageUrl\n";
                    $updated++;
                } else {
                    echo "  ℹ️  No better image found\n";
                }
            } else {
                echo "  ⚠️  Could not extract cover from page\n";
                $failed++;
            }
        } else {
            echo "  ✗ Failed to fetch page (HTTP $httpCode)\n";
            $failed++;
        }
    } else {
        echo "  ⚠️  No Goodreads ID found in URL\n";
    }
    
    echo "\n";
    sleep(1); // Be nice to Goodreads
}

echo "\n========================================\n";
echo "📊 Summary:\n";
echo "========================================\n";
echo "Total books: " . count($books) . "\n";
echo "✅ Updated: $updated\n";
echo "✓ Verified correct: $verified\n";
echo "❌ Failed: $failed\n";
echo "\n";
echo "Cover Sources:\n";
echo "  📗 Goodreads: $goodreads\n";
echo "  📕 Open Library: $openlibrary\n";
echo "========================================\n";
