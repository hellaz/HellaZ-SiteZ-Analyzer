<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * SocialMedia class – extracts social, creative, commerce, ranking, directory URLs and usernames from a given HTML page.
 * Maintains all platforms and patterns from current/previous plugin versions, adds new platforms, and preserves all public plugin dependencies.
 *
 * Usage:
 *   $extractor = new SocialMedia();
 *   $results = $extractor->extract_social_profiles($html, $base_url);
 */
class SocialMedia {
    private $platform_patterns = [
        // Social & Creative
        'facebook'    => ['~https?://(?:www\.)?facebook\.com/([a-zA-Z0-9\.]+)~i', 'https://facebook.com/'],
        'twitter'     => ['~https?://(?:www\.)?(?:twitter|x)\.com/([a-zA-Z0-9_]+)~i', 'https://twitter.com/'],
        'instagram'   => ['~https?://(?:www\.)?instagram\.com/([a-zA-Z0-9_.]+)~i', 'https://instagram.com/'],
        'linkedin'    => ['~https?://(?:www\.)?linkedin\.com/(?:in|company)/([a-zA-Z0-9\-_%]+)~i', 'https://linkedin.com/in/'],
        'youtube'     => ['~https?://(?:www\.)?youtube\.com/(?:channel|user|c)/([a-zA-Z0-9_\-]+)~i', 'https://youtube.com/channel/'],
        'tiktok'      => ['~https?://(?:www\.)?tiktok\.com/@([a-zA-Z0-9._]+)~i', 'https://tiktok.com/@'],
        'threads'     => ['~https?://(?:www\.)?threads\.net/@([a-zA-Z0-9_.]+)~i', 'https://threads.net/@'],
        'mastodon'    => ['~https?://(?:[a-z0-9\-]+\.)*mastodon\.[a-z]+/@?([a-zA-Z0-9_\-]+)~i', ''],
        'reddit'      => ['~https?://(?:www\.)?reddit\.com/user/([a-zA-Z0-9_\-]+)~i', 'https://reddit.com/user/'],
        'telegram'    => ['~https?://(?:t\.me|telegram\.me)/([a-zA-Z0-9_]+)~i', 'https://t.me/'],
        'snapchat'    => ['~https?://(?:www\.)?snapchat\.com/add/([a-zA-Z0-9._]+)~i', 'https://www.snapchat.com/add/'],
        'whatsapp'    => ['~https?://(?:wa\.me|api\.whatsapp\.com)/([0-9]+)~i', 'https://wa.me/'],
        'vimeo'       => ['~https?://(?:www\.)?vimeo\.com/([a-zA-Z0-9_\-]+)~i', 'https://vimeo.com/'],
        'soundcloud'  => ['~https?://(?:www\.)?soundcloud\.com/([a-zA-Z0-9_\-]+)~i', 'https://soundcloud.com/'],
        'discord'     => ['~https?://(?:www\.)?discord(?:app)?\.com/invite/([a-zA-Z0-9]+)~i', 'https://discord.com/invite/'],
        'twitch'      => ['~https?://(?:www\.)?twitch\.tv/([a-zA-Z0-9_]+)~i', 'https://twitch.tv/'],
        'tumblr'      => ['~https?://([a-zA-Z0-9\-]+)\.tumblr\.com~i', 'https://', '.tumblr.com'],
        'medium'      => ['~https?://medium\.com/@?([a-zA-Z0-9\-_]+)~i', 'https://medium.com/@'],
        'github'      => ['~https?://(?:www\.)?github\.com/([a-zA-Z0-9_\-]+)~i', 'https://github.com/'],
        'gitlab'      => ['~https?://(?:www\.)?gitlab\.com/([a-zA-Z0-9_\-]+)~i', 'https://gitlab.com/'],
        'flickr'      => ['~https?://(?:www\.)?flickr\.com/people/([a-zA-Z0-9@_\-]+)~i', 'https://flickr.com/people/'],
        'behance'     => ['~https?://(?:www\.)?behance\.net/([a-zA-Z0-9\-_]+)~i', 'https://behance.net/'],
        'dribbble'    => ['~https?://(?:www\.)?dribbble\.com/([a-zA-Z0-9\-_]+)~i', 'https://dribbble.com/'],
        'slack'       => ['~https?://([a-zA-Z0-9_-]+)\.slack\.com~i', 'https://', '.slack.com'],
        'patreon'     => ['~https?://(?:www\.)?patreon\.com/([a-zA-Z0-9\-_]+)~i', 'https://patreon.com/'],
        'etsy'        => ['~https?://(?:www\.)?etsy\.com/shop/([a-zA-Z0-9\-_]+)~i', 'https://etsy.com/shop/'],
        'onlyfans'    => ['~https?://(?:www\.)?onlyfans\.com/([a-zA-Z0-9\-_]+)~i', 'https://onlyfans.com/'],
        'amazon'      => ['~https?://(?:www\.)?amazon\.[a-z.]+/([a-zA-Z0-9\-_]+)~i', 'https://amazon.com/', ''],
        'ebay'        => ['~https?://(?:www\.)?ebay\.[a-z.]+/usr/([a-zA-Z0-9\-_]+)~i', 'https://ebay.com/usr/'],
        'bluesky'     => ['~https?://(?:www\.)?bsky\.app/profile/([a-zA-Z0-9_\-\.]+)~i', 'https://bsky.app/profile/'],
        'spotify'     => ['~https?://open\.spotify\.com/user/([a-zA-Z0-9]+)~i', 'https://open.spotify.com/user/'],

        // Rankings & Directories
        'similarweb'  => ['~https?://www\.similarweb\.com/website/([\w\.\-]+)~i', 'https://www.similarweb.com/website/'],
        'alexa'       => ['~https?://www\.alexa\.com/siteinfo/([\w\.\-]+)~i', 'https://www.alexa.com/siteinfo/'],
        'crunchbase'  => ['~https?://www\.crunchbase\.com/organization/([\w\-]+)~i', 'https://www.crunchbase.com/organization/'],
        'statista'    => ['~https?://www\.statista\.com/chart/(\d+)~i', 'https://www.statista.com/chart/'],
        'dmoz'        => ['~https?://www\.dmoz\.org/([^/]+)/([^/]+)/([^/]+)/~i', 'https://www.dmoz.org/'],
    ];

    /**
     * Extracts platform links and usernames/IDs.
     * @param string $html The site HTML.
     * @param string $base_url Optional site url (for e.g. building ranking links).
     * @return array [platform => ['url'=>..., 'username'=>...], ...]
     */
    public function extract_social_profiles($html, $base_url = '') {
        $results = [];

        // 1. Scan anchor tags and simple string search
        if (preg_match_all('~<a[^>]+href=["\']([^"\']+)["\'][^>]*>~i', $html, $links)) {
            foreach ($links[1] as $href) {
                foreach ($this->platform_patterns as $platform => $data) {
                    $pattern = $data[0];
                    if (preg_match($pattern, $href, $m)) {
                        $user = isset($m[1]) ? $this->sanitize_username($m[1]) : '';
                        // Build full URL properly
                        $url = $data[1] . $user . (isset($data[2]) ? $data[2] : '');
                        if (!isset($results[$platform])) {
                            $results[$platform] = [
                                'url' => esc_url_raw($url),
                                'username' => sanitize_text_field($user)
                            ];
                        }
                    }
                }
            }
        }

        // 2. Also scan JSON-LD for "sameAs" lists
        if (preg_match_all('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~ism', $html, $scripts)) {
            foreach ($scripts[1] as $json_part) {
                $data = json_decode($json_part, true);
                if (isset($data['sameAs']) && is_array($data['sameAs'])) {
                    foreach ($data['sameAs'] as $profile) {
                        foreach ($this->platform_patterns as $platform => $pdata) {
                            if (preg_match($pdata[0], $profile, $m)) {
                                $user = isset($m[1]) ? $this->sanitize_username($m[1]) : '';
                                $url = $pdata[1] . $user . (isset($pdata[2]) ? $pdata[2] : '');
                                if (!isset($results[$platform])) {
                                    $results[$platform] = [
                                        'url' => esc_url_raw($url),
                                        'username' => sanitize_text_field($user)
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // 3. Rankings & Directories: fallback if not natively linked
        $host = $base_url ? parse_url($base_url, PHP_URL_HOST) : '';
        if ($host) {
            if (!isset($results['similarweb'])) {
                $results['similarweb'] = [
                    'url' => 'https://www.similarweb.com/website/' . $host,
                    'username' => $host
                ];
            }
            if (!isset($results['alexa'])) {
                $results['alexa'] = [
                    'url' => 'https://www.alexa.com/siteinfo/' . $host,
                    'username' => $host
                ];
            }
            if (!isset($results['crunchbase'])) {
                $results['crunchbase'] = [
                    'url' => 'https://www.crunchbase.com/organization/' . preg_replace('/\W+/', '', $host),
                    'username' => preg_replace('/\W+/', '', $host)
                ];
            }
        }

        return $results;
    }

    /**
     * Sanitize extracted usernames.
     */
    private function sanitize_username($username) {
        return trim(preg_replace('/[^a-zA-Z0-9_\.\-@]/', '', $username));
    }
}
