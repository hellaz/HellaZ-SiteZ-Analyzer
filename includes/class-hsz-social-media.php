<?php
namespace HSZ;

if (!defined('ABSPATH')) exit;

/**
 * SocialMedia class – Extracts social, creative, commerce, ranking, and directory URLs and usernames
 * from a website's HTML content.
 */
class SocialMedia {
    /**
     * Platform regex patterns and URL prefixes for constructing profile URLs.
     * Third array element 'suffix' is optional, appended after username.
     *
     * @var array<string, array{pattern: string, url_prefix: string, suffix?: string}>
     */
    private $platforms = [
        'facebook'   => [
            'pattern' => '~https?://(?:www\.)?facebook\.com/([a-zA-Z0-9\.]+)~i',
            'url_prefix' => 'https://facebook.com/',
        ],
        'twitter'    => [
            'pattern' => '~https?://(?:www\.)?(?:twitter|x)\.com/([a-zA-Z0-9_]+)~i',
            'url_prefix' => 'https://twitter.com/',
        ],
        'instagram'  => [
            'pattern' => '~https?://(?:www\.)?instagram\.com/([a-zA-Z0-9_.]+)~i',
            'url_prefix' => 'https://instagram.com/',
        ],
        'linkedin'   => [
            'pattern' => '~https?://(?:www\.)?linkedin\.com/(?:in|company)/([a-zA-Z0-9\-\_]+)~i',
            'url_prefix' => 'https://linkedin.com/in/',
        ],
        'youtube'    => [
            'pattern' => '~https?://(?:www\.)?youtube\.com/(?:channel|user|c)/([a-zA-Z0-9_\-]+)~i',
            'url_prefix' => 'https://youtube.com/channel/',
        ],
        'github'    => [
            'pattern' => '~https?://(?:www\.)?github\.com/([a-zA-Z0-9_\-]+)~i',
            'url_prefix' => 'https://github.com/',
        ],
        'gitlab'    => [
            'pattern' => '~https?://(?:www\.)?gitlab\.com/([a-zA-Z0-9_\-]+)~i',
            'url_prefix' => 'https://gitlab.com/',
        ],
        'spotify'   => [
            'pattern' => '~https?://open\.spotify\.com/user/([a-zA-Z0-9]+)~i',
            'url_prefix' => 'https://open.spotify.com/user/',
        ],
        'medium'    => [
            'pattern' => '~https?://medium\.com/@?([a-zA-Z0-9\-_]+)~i',
            'url_prefix' => 'https://medium.com/@',
        ],
        'tumblr'    => [
            'pattern' => '~https?://([a-zA-Z0-9\-]+)\.tumblr\.com~i',
            'url_prefix' => 'https://',
            'suffix' => '.tumblr.com',
        ],
        'flickr'    => [
            'pattern' => '~https?://(?:www\.)?flickr\.com/people/([a-zA-Z0-9@_\-]+)~i',
            'url_prefix' => 'https://flickr.com/people/',
        ],
        'behance'   => [
            'pattern' => '~https?://(?:www\.)?behance\.net/([a-zA-Z0-9\-_]+)~i',
            'url_prefix' => 'https://behance.net/',
        ],
        'dribbble'  => [
            'pattern' => '~https?://(?:www\.)?dribbble\.com/([a-zA-Z0-9\-_]+)~i',
            'url_prefix' => 'https://dribbble.com/',
        ],
        'slack'     => [
            'pattern' => '~https?://([a-zA-Z0-9_-]+)\.slack\.com~i',
            'url_prefix' => 'https://',
            'suffix' => '.slack.com',
        ],
        'patreon'   => [
            'pattern' => '~https?://(?:www\.)?patreon\.com/([a-zA-Z0-9\-_]+)~i',
            'url_prefix' => 'https://patreon.com/',
        ],
        'etsy'      => [
            'pattern' => '~https?://(?:www\.)?etsy\.com/shop/([a-zA-Z0-9\-_]+)~i',
            'url_prefix' => 'https://etsy.com/shop/',
        ],
        'onlyfans'  => [
            'pattern' => '~https?://(?:www\.)?onlyfans\.com/([a-zA-Z0-9\-_]+)~i',
            'url_prefix' => 'https://onlyfans.com/',
        ],
        'amazon'    => [
            'pattern' => '~https?://(?:www\.)?amazon\.[a-z.]+/([a-zA-Z0-9\-_]+)~i',
            'url_prefix' => 'https://amazon.com/',
        ],
        'ebay'      => [
            'pattern' => '~https?://(?:www\.)?ebay\.[a-z.]+/usr/([a-zA-Z0-9\-_]+)~i',
            'url_prefix' => 'https://ebay.com/usr/',
        ],
        'bluesky'   => [
            'pattern' => '~https?://(?:www\.)?bsky\.app/profile/([a-zA-Z0-9_\-\.]+)~i',
            'url_prefix' => 'https://bsky.app/profile/',
        ],
        'tiktok'    => [
            'pattern' => '~https?://(?:www\.)?tiktok\.com/@([a-zA-Z0-9._]+)~i',
            'url_prefix' => 'https://tiktok.com/@',
        ],
        'threads'   => [
            'pattern' => '~https?://(?:www\.)?threads\.net/@([a-zA-Z0-9_.]+)~i',
            'url_prefix' => 'https://threads.net/@',
        ],
        'mastodon'  => [
            'pattern' => '~https?://(?:[a-z0-9\-]+\.)?mastodon\.[a-z]+/@?([a-zA-Z0-9_\-]+)~i',
            'url_prefix' => '',
        ],
        'reddit'    => [
            'pattern' => '~https?://(?:www\.)?reddit\.com/user/([a-zA-Z0-9_\-]+)~i',
            'url_prefix' => 'https://reddit.com/user/',
        ],
        'telegram'  => [
            'pattern' => '~https?://(?:t\.me|telegram\.me)/([a-zA-Z0-9_]+)~i',
            'url_prefix' => 'https://t.me/',
        ],
        'snapchat'  => [
            'pattern' => '~https?://(?:www\.)?snapchat\.com/add/([a-zA-Z0-9._]+)~i',
            'url_prefix' => 'https://www.snapchat.com/add/',
        ],
        'whatsapp'  => [
            'pattern' => '~https?://(?:wa\.me|api\.whatsapp\.com)/([0-9]+)~i',
            'url_prefix' => 'https://wa.me/',
        ],
        'vimeo'     => [
            'pattern' => '~https?://(?:www\.)?vimeo\.com/([a-zA-Z0-9_\-]+)~i',
            'url_prefix' => 'https://vimeo.com/',
        ],
        'soundcloud'=> [
            'pattern' => '~https?://(?:www\.)?soundcloud\.com/([a-zA-Z0-9_\-]+)~i',
            'url_prefix' => 'https://soundcloud.com/',
        ],
        'discord'   => [
            'pattern' => '~https?://(?:www\.)?discord(?:app)?\.com/invite/([a-zA-Z0-9]+)~i',
            'url_prefix' => 'https://discord.com/invite/',
        ],
        'twitch'   => [
            'pattern' => '~https?://(?:www\.)?twitch\.tv/([a-zA-Z0-9_]+)~i',
            'url_prefix' => 'https://twitch.tv/',
        ],

        // Directory and Rankings:
        'similarweb' => [
            'pattern' => '~https?://www\.similarweb\.com/website/([\w\.\-]+)~i',
            'url_prefix' => 'https://www.similarweb.com/website/',
        ],
        'alexa' => [
            'pattern' => '~https?://www\.alexa\.com/siteinfo/([\w\.\-]+)~i',
            'url_prefix' => 'https://www.alexa.com/siteinfo/',
        ],
        'crunchbase' => [
            'pattern' => '~https?://www\.crunchbase\.com/organization/([\w\-]+)~i',
            'url_prefix' => 'https://www.crunchbase.com/organization/',
        ],
        'statista' => [
            'pattern' => '~https?://www\.statista\.com/chart/(\d+)~i',
            'url_prefix' => 'https://www.statista.com/chart/',
        ],
        'dmoz' => [
            'pattern' => '~https?://www\.dmoz\.org/([^/]+)/([^/]+)/([^/]+)/~i',
            'url_prefix' => 'https://www.dmoz.org/',
        ],
    ];

    /**
     * Extract social profiles from the provided HTML content.
     *
     * @param string $html The raw HTML of the site.
     * @param string $base_url The base URL to construct fallback URLs.
     * @return array<string, array{url: string|null, username: string|null}> Platform keyed array.
     */
    public function extract_social_profiles(string $html, string $base_url = ''): array {
        $results = [];

        // Scrape anchor hrefs from HTML
        if (preg_match_all('~<a[^>]+href=["\']([^"\']+)["\'][^>]*>~i', $html, $matches)) {
            foreach ($matches[1] as $href) {
                foreach ($this->platforms as $platform => $info) {
                    if (preg_match($info['pattern'], $href, $m)) {
                        $username = $this->sanitize_username($m[1] ?? '');
                        if ($username === '') continue;

                        $url = $info['url_prefix'] . $username;
                        if (isset($info['suffix'])) $url .= $info['suffix'];

                        if (!isset($results[$platform])) {
                            $results[$platform] = [
                                'url' => esc_url_raw($url),
                                'username' => sanitize_text_field($username),
                            ];
                        }
                    }
                }
            }
        }

        // Scan JSON-LD for sameAs arrays for social profile URLs
        if (preg_match_all('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $html, $jsonScripts)) {
            foreach ($jsonScripts[1] as $jsonStr) {
                $data = json_decode(trim($jsonStr), true);
                if (is_array($data) && isset($data['sameAs']) && is_array($data['sameAs'])) {
                    foreach ($data['sameAs'] as $profileUrl) {
                        foreach ($this->platforms as $platform => $info) {
                            if (preg_match($info['pattern'], $profileUrl, $m)) {
                                $username = $this->sanitize_username($m[1] ?? '');
                                if ($username === '') continue;
                                $url = $info['url_prefix'] . $username;
                                if (isset($info['suffix'])) $url .= $info['suffix'];

                                if (!isset($results[$platform])) {
                                    $results[$platform] = [
                                        'url' => esc_url_raw($url),
                                        'username' => sanitize_text_field($username),
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Add fallback ranking/directory URLs based on base domain
        $host = parse_url($base_url, PHP_URL_HOST);
        if ($host !== null) {
            if (!isset($results['similarweb'])) {
                $results['similarweb'] = [
                    'url' => 'https://www.similarweb.com/website/' . $host,
                    'username' => $host,
                ];
            }
            if (!isset($results['alexa'])) {
                $results['alexa'] = [
                    'url' => 'https://www.alexa.com/siteinfo/' . $host,
                    'username' => $host,
                ];
            }
            if (!isset($results['crunchbase'])) {
                $results['crunchbase'] = [
                    'url' => 'https://www.crunchbase.com/organization/' . preg_replace('/\W+/', '', $host),
                    'username' => preg_replace('/\W+/', '', $host),
                ];
            }
        }

        return $results;
    }

    /**
     * Sanitize usernames by trimming and removing unsafe characters.
     *
     * @param string $username Raw username extracted from URL
     * @return string Sanitized username
     */
    private function sanitize_username(string $username): string {
        // Remove all characters except letters, numbers, underscore, dot, dash, and @
        $sanitized = trim(preg_replace('/[^a-zA-Z0-9_\.\-\@]/', '', $username));
        return $sanitized;
    }
}
