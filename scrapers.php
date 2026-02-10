<?php
// Site-specific scrapers for Hunt HQ

class SiteScrapers {
    
    // Fetch URL with proper headers
    private static function fetchURL($url, $timeout = 30) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0
        ]);
        
        $content = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);
        
        return [
            'content' => $content,
            'code' => $http_code
        ];
    }
    
    // Parse YouTube Atom RSS feed
    private static function parseYouTubeRSS($rss_url) {
        $result = self::fetchURL($rss_url);
        
        if ($result['code'] != 200 || empty($result['content'])) {
            return [];
        }
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($result['content']);
        libxml_clear_errors();
        
        if (!$xml) {
            return [];
        }
        
        $posts = [];
        foreach ($xml->entry as $entry) {
            $posts[] = [
                'title' => (string)$entry->title,
                'url' => (string)$entry->link['href'],
                'date' => date('Y-m-d H:i:s', strtotime((string)$entry->published))
            ];
        }
        
        return $posts;
    }
    
    // Get YouTube channel ID from handle
    private static function getYouTubeChannelID($handle_url) {
        $result = self::fetchURL($handle_url);
        
        if ($result['code'] != 200) {
            return null;
        }
        
        // Try multiple patterns to find channel ID
        $patterns = [
            '/"channelId":"([^"]+)"/',
            '/"externalChannelId":"([^"]+)"/',
            '/channel_id=([a-zA-Z0-9_-]+)/',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $result['content'], $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
    
    // Scrape MadBitcoins (YouTube channel)
    public static function scrapeMadBitcoins() {
        $rss_url = 'https://www.youtube.com/feeds/videos.xml?channel_id=UCR9gdpWisRwnk_k23GsHfcA';
        return self::parseYouTubeRSS($rss_url);
    }
    
    // Scrape WorldCryptoNetwork (YouTube channel)
    public static function scrapeWorldCryptoNetwork() {
        // Try to get channel ID first
        $channel_id = self::getYouTubeChannelID('https://www.youtube.com/@WorldCryptoNetwork');
        
        if ($channel_id) {
            $rss_url = "https://www.youtube.com/feeds/videos.xml?channel_id=$channel_id";
            return self::parseYouTubeRSS($rss_url);
        }
        
        // Fallback: try known channel ID (if you provide it)
        $rss_url = 'https://www.youtube.com/feeds/videos.xml?channel_id=UCCj2FRhZwHjWKm5cFHprbEQ';
        return self::parseYouTubeRSS($rss_url);
    }
    
    // Scrape ThomasHunt.com
    public static function scrapeThomasHunt() {
        $result = self::fetchURL('https://thomashunt.com');
        
        if ($result['code'] != 200 || empty($result['content'])) {
            // Try HTTP instead of HTTPS
            $result = self::fetchURL('http://thomashunt.com');
            if ($result['code'] != 200) {
                return [];
            }
        }
        
        $posts = [];
        $dom = new DOMDocument();
        @$dom->loadHTML($result['content']);
        $xpath = new DOMXPath($dom);
        
        // Try common blog selectors
        $selectors = [
            "//article//h2//a",
            "//h2[@class='entry-title']//a",
            "//div[contains(@class,'post')]//h2//a"
        ];
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $url = $node->getAttribute('href');
                    $title = trim($node->textContent);
                    
                    if (!empty($url) && !empty($title)) {
                        // Make URL absolute if needed
                        if (strpos($url, 'http') !== 0) {
                            $url = 'https://thomashunt.com' . $url;
                        }
                        
                        $posts[] = [
                            'title' => $title,
                            'url' => $url,
                            'date' => date('Y-m-d H:i:s')
                        ];
                    }
                }
                break;
            }
        }
        
        return $posts;
    }
    
    // Scrape Thunt.net
    public static function scrapeThuntNet() {
        $result = self::fetchURL('https://thunt.net');
        
        if ($result['code'] != 200 || empty($result['content'])) {
            // Try HTTP
            $result = self::fetchURL('http://thunt.net');
            if ($result['code'] != 200) {
                return [];
            }
        }
        
        $posts = [];
        $dom = new DOMDocument();
        @$dom->loadHTML($result['content']);
        $xpath = new DOMXPath($dom);
        
        $selectors = [
            "//article//h2//a",
            "//h2[@class='entry-title']//a",
            "//div[contains(@class,'post')]//h2//a"
        ];
        
        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $url = $node->getAttribute('href');
                    $title = trim($node->textContent);
                    
                    if (!empty($url) && !empty($title)) {
                        if (strpos($url, 'http') !== 0) {
                            $url = 'https://thunt.net' . $url;
                        }
                        
                        $posts[] = [
                            'title' => $title,
                            'url' => $url,
                            'date' => date('Y-m-d H:i:s')
                        ];
                    }
                }
                break;
            }
        }
        
        return $posts;
    }
    
    // Route to appropriate scraper
    public static function scrape($site_name, $site_url) {
        switch ($site_name) {
            case 'MadBitcoins.com':
                return self::scrapeMadBitcoins();
            case 'WorldCryptoNetwork.com':
                return self::scrapeWorldCryptoNetwork();
            case 'ThomasHunt.com':
                return self::scrapeThomasHunt();
            case 'Thunt.net':
                return self::scrapeThuntNet();
            default:
                return [];
        }
    }
}
