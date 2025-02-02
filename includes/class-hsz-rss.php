<?php
namespace HSZ;

class RSS {
    public function __construct() {
        add_action('init', [$this, 'detect_rss_feeds']);
    }

    public function detect_rss_feeds($html) {
        $pattern = '/<link[^>]+type="application\/rss\+xml"[^>]*>/i';
        preg_match_all($pattern, $html, $matches);
        $feeds = [];
        foreach ($matches[0] as $match) {
            if (preg_match('/href="([^"]+)"/i', $match, $href)) {
                $feeds[] = $href[1];
            }
        }
        return $feeds;
    }

    public function detect_feeds($html) {
        $pattern = '/<link[^>]+type="application\/(rss|atom)\+xml"[^>]*>/i';
        preg_match_all($pattern, $html, $matches);
        $feeds = [];
        foreach ($matches[0] as $match) {
            if (preg_match('/href="([^"]+)"/i', $match, $href)) {
                $feeds[] = $href[1];
            }
        }
        return $feeds;
    }

}
