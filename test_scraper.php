<?php
// Test scraper to debug individual sites
error_reporting(E_ALL);
ini_set('display_errors', 1);

function fetchURL($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    unset($ch);
    
    return [
        'content' => $content,
        'code' => $http_code,
        'error' => $error
    ];
}

// Test YouTube RSS feed
function testYouTubeRSS($channel_url) {
    echo "\n=== Testing: $channel_url ===\n";
    
    // Try to get channel ID from page
    $result = fetchURL($channel_url);
    
    if ($result['code'] != 200) {
        echo "ERROR: HTTP {$result['code']} - {$result['error']}\n";
        return false;
    }
    
    // Extract channel ID from HTML
    if (preg_match('/"channelId":"([^"]+)"/', $result['content'], $matches)) {
        $channel_id = $matches[1];
        echo "Found Channel ID: $channel_id\n";
        
        // Fetch RSS feed
        $rss_url = "https://www.youtube.com/feeds/videos.xml?channel_id=$channel_id";
        echo "RSS URL: $rss_url\n";
        
        $rss_result = fetchURL($rss_url);
        
        if ($rss_result['code'] != 200) {
            echo "ERROR fetching RSS: HTTP {$rss_result['code']}\n";
            return false;
        }
        
        // Parse RSS
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rss_result['content']);
        libxml_clear_errors();
        
        if (!$xml) {
            echo "ERROR: Could not parse XML\n";
            return false;
        }
        
        // YouTube uses Atom format
        $namespaces = $xml->getNamespaces(true);
        $entries = $xml->entry;
        
        echo "Found " . count($entries) . " videos\n";
        
        if (count($entries) > 0) {
            $latest = $entries[0];
            $media = $latest->children('http://search.yahoo.com/mrss/');
            
            echo "\nLatest Video:\n";
            echo "  Title: " . (string)$latest->title . "\n";
            echo "  Link: " . (string)$latest->link['href'] . "\n";
            echo "  Published: " . (string)$latest->published . "\n";
            
            return [
                'channel_id' => $channel_id,
                'rss_url' => $rss_url,
                'latest' => [
                    'title' => (string)$latest->title,
                    'url' => (string)$latest->link['href'],
                    'date' => (string)$latest->published
                ]
            ];
        }
    } else {
        echo "ERROR: Could not find channel ID\n";
        return false;
    }
}

// Test blog scraping
function testBlogScrape($url) {
    echo "\n=== Testing: $url ===\n";
    
    $result = fetchURL($url);
    
    if ($result['code'] != 200) {
        echo "ERROR: HTTP {$result['code']} - {$result['error']}\n";
        return false;
    }
    
    echo "Page loaded successfully\n";
    echo "Content length: " . strlen($result['content']) . " bytes\n";
    
    // Try to find article links
    $dom = new DOMDocument();
    @$dom->loadHTML($result['content']);
    
    $xpath = new DOMXPath($dom);
    
    // Common blog post selectors
    $selectors = [
        "//article//h2//a",
        "//h2[@class='entry-title']//a",
        "//h2[@class='post-title']//a",
        "//article//a",
        "//div[contains(@class,'post')]//h2//a"
    ];
    
    foreach ($selectors as $selector) {
        $nodes = $xpath->query($selector);
        if ($nodes->length > 0) {
            echo "\nFound posts using: $selector\n";
            echo "Total: " . $nodes->length . " posts\n";
            
            for ($i = 0; $i < min(3, $nodes->length); $i++) {
                $node = $nodes->item($i);
                echo "\nPost " . ($i + 1) . ":\n";
                echo "  Title: " . trim($node->textContent) . "\n";
                echo "  URL: " . $node->getAttribute('href') . "\n";
            }
            
            return true;
        }
    }
    
    echo "No posts found with standard selectors\n";
    return false;
}

// Run tests
echo "=== HUNT HQ SCRAPER TEST ===\n";
echo date('Y-m-d H:i:s') . "\n";

// Test YouTube channels
$youtube1 = testYouTubeRSS('https://www.youtube.com/@MadBitcoins');
$youtube2 = testYouTubeRSS('https://www.youtube.com/@WorldCryptoNetwork');

// Test blogs
$blog1 = testBlogScrape('https://thomashunt.com');
$blog2 = testBlogScrape('https://thunt.net');

echo "\n=== TEST COMPLETE ===\n";
