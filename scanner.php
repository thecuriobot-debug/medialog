<?php
// Suppress all output except JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

class FeedScanner {
    private $pdo;
    
    // Map channel handles to their IDs
    private $channelMap = [
        'madbitcoins' => 'UCQgjyXLLMtG99Dkh8Yvw-3g',
        'worldcryptonetwork' => 'UCR9gdpWisRwnk_k23GsHfcA'
    ];
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    public function scanAll() {
        $results = [];
        $stmt = $this->pdo->query("SELECT * FROM sites WHERE status = 'active'");
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sites as $site) {
            $results[$site['id']] = $this->scanSite($site);
        }
        
        return $results;
    }
    
    private function scanSite($site) {
        $result = [
            'site' => $site['name'],
            'success' => false,
            'new_posts' => 0,
            'error' => null
        ];
        
        try {
            // Update last checked time
            $stmt = $this->pdo->prepare("UPDATE sites SET last_checked = NOW() WHERE id = ?");
            $stmt->execute([$site['id']]);
            
            $posts = [];
            
            // Detect site type and scrape accordingly
            if (strpos($site['url'], 'youtube.com') !== false) {
                $posts = $this->scrapeYouTube($site['url']);
            } elseif (strpos($site['url'], 'thomashunt.com') !== false) {
                $posts = $this->scrapeBlogger($site['url']);
            } elseif (strpos($site['url'], 'last.fm') !== false) {
                $posts = $this->scrapeLastFm($site['url']);
            } elseif (strpos($site['url'], 'letterboxd.com') !== false) {
                $posts = $this->scrapeLetterboxd($site['url']);
            } elseif (strpos($site['url'], 'goodreads.com') !== false) {
                $posts = $this->scrapeGoodreads($site['url']);
            }
            
            if ($posts && count($posts) > 0) {
                $result['new_posts'] = $this->savePosts($site['id'], $posts);
                $result['success'] = true;
            }
            
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        
        return $result;
    }
    
    private function scrapeYouTube($url) {
        // Extract channel handle from URL (e.g., youtube.com/madbitcoins)
        if (preg_match('/youtube\.com\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            $handle = strtolower($matches[1]);
            
            // Get channel ID from map
            if (isset($this->channelMap[$handle])) {
                $channelId = $this->channelMap[$handle];
                $rssUrl = "https://www.youtube.com/feeds/videos.xml?channel_id={$channelId}";
                return $this->parseYouTubeRSS($rssUrl);
            }
        }
        
        return false;
    }
    
    private function parseYouTubeRSS($rssUrl) {
        $posts = [];
        $rssData = $this->fetchURL($rssUrl);
        
        if (!$rssData) {
            return false;
        }
        
        $xml = @simplexml_load_string($rssData);
        if (!$xml || !isset($xml->entry)) {
            return false;
        }
        
        foreach ($xml->entry as $entry) {
            // Get namespaced children
            $yt = $entry->children('http://www.youtube.com/xml/schemas/2015');
            $media = $entry->children('http://search.yahoo.com/mrss/');
            
            $videoId = (string)$yt->videoId;
            $title = (string)$entry->title;
            $pubDate = (string)$entry->published;
            
            // Get description from media group
            $description = null;
            if (isset($media->group)) {
                $mediaGroup = $media->group->children('http://search.yahoo.com/mrss/');
                $description = (string)$mediaGroup->description;
            }
            
            // Get thumbnail
            $thumbnail = null;
            if (isset($media->group)) {
                $mediaGroup = $media->group->children('http://search.yahoo.com/mrss/');
                if (isset($mediaGroup->thumbnail)) {
                    $thumbnail = (string)$mediaGroup->thumbnail['url'];
                }
            }
            if (!$thumbnail && $videoId) {
                $thumbnail = "https://i.ytimg.com/vi/{$videoId}/mqdefault.jpg";
            }
            
            if ($videoId && $title) {
                $posts[] = [
                    'title' => $title,
                    'url' => "https://www.youtube.com/watch?v={$videoId}",
                    'date' => $this->parseDate($pubDate),
                    'description' => $description ? substr($description, 0, 300) : null,
                    'image_url' => $thumbnail
                ];
            }
            
            if (count($posts) >= 10) break;
        }
        
        return $posts;
    }
    
    private function scrapeBlogger($url) {
        $posts = [];
        
        // Blogger Atom feed
        $feedUrl = rtrim($url, '/') . '/feeds/posts/default';
        $rssData = $this->fetchURL($feedUrl);
        
        if (!$rssData || strpos($rssData, '<feed') === false) {
            return false;
        }
        
        $xml = @simplexml_load_string($rssData);
        if (!$xml) {
            return false;
        }
        
        // Register Atom namespace
        $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
        $entries = $xml->xpath('//atom:entry');
        
        if (!$entries) {
            return false;
        }
        
        foreach ($entries as $entry) {
            $entry->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
            
            $titleNodes = $entry->xpath('atom:title');
            $title = $titleNodes ? (string)$titleNodes[0] : '';
            
            // Find alternate link
            $linkNodes = $entry->xpath('atom:link[@rel="alternate"]');
            $link = '';
            if ($linkNodes && isset($linkNodes[0]['href'])) {
                $link = (string)$linkNodes[0]['href'];
            }
            
            $pubDateNodes = $entry->xpath('atom:published');
            $pubDate = $pubDateNodes ? (string)$pubDateNodes[0] : '';
            
            // Extract content/summary for description
            $summaryNodes = $entry->xpath('atom:summary');
            $contentNodes = $entry->xpath('atom:content');
            $description = '';
            if ($summaryNodes) {
                $description = strip_tags((string)$summaryNodes[0]);
            } elseif ($contentNodes) {
                $description = strip_tags((string)$contentNodes[0]);
            }
            $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $description = preg_replace('/\s+/', ' ', $description);
            $description = trim(substr($description, 0, 300));
            
            // Try to extract image from content
            $image_url = null;
            if ($contentNodes) {
                $content = (string)$contentNodes[0];
                if (preg_match('/<img[^>]+src=["\']([^"\'>]+)["\']/', $content, $matches)) {
                    $image_url = $matches[1];
                }
            }
            
            if (!empty($title) && !empty($link)) {
                $posts[] = [
                    'title' => $title,
                    'url' => $link,
                    'date' => $this->parseDate($pubDate),
                    'description' => $description,
                    'image_url' => $image_url
                ];
            }
            
            if (count($posts) >= 5) break;
        }
        
        return $posts;
    }
    
    private function scrapeLastFm($url) {
        $posts = [];
        $html = $this->fetchURL($url);
        
        if (!$html) {
            return false;
        }
        
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Find all track rows in the chart
        $trackLinks = $xpath->query("//td[contains(@class, 'chartlist-name')]//a[contains(@href, '/music/')]");
        
        $seenTracks = [];
        
        foreach ($trackLinks as $link) {
            $href = $link->getAttribute('href');
            
            // Only process track links (contain /_/ in the path)
            if (strpos($href, '/_/') === false) {
                continue;
            }
            
            // Avoid duplicates
            if (isset($seenTracks[$href])) {
                continue;
            }
            $seenTracks[$href] = true;
            
            $trackName = trim($link->textContent);
            $fullUrl = 'https://www.last.fm' . $href;
            
            // Parse artist and track from URL
            // Format: /music/Artist/_/Track+Name
            if (preg_match('#/music/([^/]+)/_/(.+)#', $href, $matches)) {
                $artist = urldecode(str_replace('+', ' ', $matches[1]));
                $track = urldecode(str_replace('+', ' ', $matches[2]));
                
                $title = "$artist - $track";
                
                // Try to find album art from the same row
                $imageUrl = null;
                $row = $link;
                while ($row && $row->nodeName !== 'tr') {
                    $row = $row->parentNode;
                }
                if ($row) {
                    $imgs = $xpath->query(".//img", $row);
                    if ($imgs->length > 0) {
                        $imageUrl = $imgs->item(0)->getAttribute('src');
                        // Use larger image if available
                        $imageUrl = str_replace('/64s/', '/300x300/', $imageUrl);
                    }
                }
                
                $posts[] = [
                    'title' => $title,
                    'url' => $fullUrl,
                    'date' => date('Y-m-d H:i:s'), // Last.fm doesn't show exact dates on this page
                    'description' => "Recently played on Last.fm",
                    'image_url' => $imageUrl
                ];
                
                if (count($posts) >= 10) break;
            }
        }
        
        return $posts;
    }
    
    private function scrapeLetterboxd($url) {
        $posts = [];
        
        // Letterboxd RSS feed URL
        $username = '';
        if (preg_match('#letterboxd\.com/([^/]+)#', $url, $matches)) {
            $username = $matches[1];
        }
        
        if (!$username) {
            return false;
        }
        
        $rssUrl = "https://letterboxd.com/{$username}/rss/";
        $rssData = $this->fetchURL($rssUrl);
        
        if (!$rssData) {
            return false;
        }
        
        $xml = @simplexml_load_string($rssData);
        if (!$xml || !isset($xml->channel->item)) {
            return false;
        }
        
        foreach ($xml->channel->item as $item) {
            $title = (string)$item->title;
            $link = (string)$item->link;
            $pubDate = (string)$item->pubDate;
            $description = (string)$item->description;
            
            // Extract image from description (it's in the CDATA)
            $imageUrl = null;
            if (preg_match('/<img src="([^"]+)"/', $description, $matches)) {
                $imageUrl = $matches[1];
            }
            
            // Clean description - strip HTML and take first 300 chars
            $cleanDesc = strip_tags($description);
            $cleanDesc = html_entity_decode($cleanDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);
            $cleanDesc = trim(substr($cleanDesc, 0, 300));
            
            if ($title && $link) {
                $posts[] = [
                    'title' => $title,
                    'url' => $link,
                    'date' => $this->parseDate($pubDate),
                    'description' => $cleanDesc,
                    'image_url' => $imageUrl
                ];
            }
            
            if (count($posts) >= 10) break;
        }
        
        return $posts;
    }
    
    private function scrapeGoodreads($url) {
        $posts = [];
        
        // URL is already the RSS feed
        $rssData = $this->fetchURL($url);
        
        if (!$rssData) {
            return false;
        }
        
        $xml = @simplexml_load_string($rssData);
        if (!$xml || !isset($xml->channel->item)) {
            return false;
        }
        
        foreach ($xml->channel->item as $item) {
            $title = (string)$item->title;
            $link = (string)$item->link;
            $pubDate = (string)$item->pubDate;
            $authorName = (string)$item->author_name;
            $userRating = (string)$item->user_rating;
            $bookDesc = (string)$item->book_description;
            $userReadAt = (string)$item->user_read_at;
            $userReview = (string)$item->user_review;
            $bookId = (string)$item->book_id;
            
            // Get book cover image (use large/high quality)
            $imageUrl = (string)$item->book_large_image_url;
            if (!$imageUrl) {
                $imageUrl = (string)$item->book_medium_image_url;
            }
            if (!$imageUrl) {
                $imageUrl = (string)$item->book_image_url;
            }
            
            // Format title with author and rating
            $stars = str_repeat('★', (int)$userRating);
            if ($stars) {
                $fullTitle = "{$title} by {$authorName} - {$stars}";
            } else {
                $fullTitle = "{$title} by {$authorName}";
            }
            
            // Use book description for preview
            $cleanDesc = strip_tags($bookDesc);
            $cleanDesc = html_entity_decode($cleanDesc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanDesc = preg_replace('/\s+/', ' ', $cleanDesc);
            $cleanDesc = trim(substr($cleanDesc, 0, 300));
            
            // Store full review for local viewing
            $fullContent = '';
            if ($userReview) {
                $fullContent = $userReview; // Keep HTML for formatting
            } else {
                $fullContent = $bookDesc; // Use book description if no review
            }
            
            if ($title && $link) {
                // Use user_read_at if available, otherwise use pubDate
                $date = null;
                if ($userReadAt) {
                    $date = $this->parseDate($userReadAt);
                }
                if (!$date) {
                    $date = $this->parseDate($pubDate);
                }
                
                // Create local review URL
                $localUrl = "review.php?id={$bookId}";
                
                $posts[] = [
                    'title' => $fullTitle,
                    'url' => $localUrl,  // Link to local review page
                    'date' => $date,
                    'description' => $cleanDesc,
                    'image_url' => $imageUrl,
                    'full_content' => $fullContent  // Store full review
                ];
            }
            
            if (count($posts) >= 10) break;
        }
        
        return $posts;
    }
    
    private function fetchURL($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code != 200 || !$data) {
            return false;
        }
        
        return $data;
    }
    
    private function parseDate($date_string) {
        if (empty($date_string)) {
            return null;
        }
        
        $date_string = trim($date_string);
        
        try {
            $timestamp = strtotime($date_string);
            if ($timestamp && $timestamp > 0) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        } catch (Exception $e) {
            // Continue
        }
        
        // Try relative time parsing (e.g., "2 days ago")
        if (preg_match('/(\d+)\s+(second|minute|hour|day|week|month|year)s?\s+ago/i', $date_string, $matches)) {
            $amount = (int)$matches[1];
            $unit = strtolower($matches[2]);
            $timestamp = strtotime("-$amount $unit");
            if ($timestamp) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        return null;
    }
    
    private function savePosts($site_id, $posts) {
        $new_count = 0;
        
        $stmt = $this->pdo->prepare(
            "INSERT IGNORE INTO posts (site_id, title, url, url_hash, publish_date, description, image_url, full_content) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        foreach ($posts as $post) {
            if (empty($post['url']) || empty($post['title'])) {
                continue;
            }
            
            $url_hash = hash('sha256', $post['url']);
            $description = $post['description'] ?? null;
            $image_url = $post['image_url'] ?? null;
            $full_content = $post['full_content'] ?? null;
            
            try {
                $stmt->execute([
                    $site_id,
                    $post['title'],
                    $post['url'],
                    $url_hash,
                    $post['date'] ?: date('Y-m-d H:i:s'),
                    $description,
                    $image_url,
                    $full_content
                ]);
                
                if ($stmt->rowCount() > 0) {
                    $new_count++;
                }
            } catch (PDOException $e) {
                // Ignore duplicate entries
                continue;
            }
        }
        
        return $new_count;
    }
}

// API endpoint for AJAX calls
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'scan') {
    header('Content-Type: application/json');
    try {
        $scanner = new FeedScanner();
        $results = $scanner->scanAll();
        echo json_encode([
            'success' => true,
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}
