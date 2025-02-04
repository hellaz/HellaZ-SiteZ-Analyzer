<?php
namespace HSZ;

class RSS {
    public function detect_rss_feeds($html) {
        // Cache key for storing RSS feeds
        $cache_key = 'hsz_rss_feeds_' . md5($html);
        $cached_feeds = get_transient($cache_key);
    
        if ($cached_feeds) {
            return $cached_feeds; // Return cached results if available
        }
    
        $feeds = [];
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);
    
        // Extract RSS feeds from <link> tags
        $links = $dom->getElementsByTagName('link');
        foreach ($links as $link) {
            $type = $link->getAttribute('type');
            if (in_array($type, ['application/rss+xml', 'application/atom+xml'])) {
                $href = $link->getAttribute('href');
                if (filter_var($href, FILTER_VALIDATE_URL)) {
                    $feeds[] = $href;
                }
            }
        }
    
        // Cache the results for 24 hours
        set_transient($cache_key, $feeds, DAY_IN_SECONDS);
    
        return $feeds;
    }
}
