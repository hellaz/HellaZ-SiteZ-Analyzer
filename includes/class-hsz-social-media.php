<?php
namespace HSZ;

class SocialMedia {
    public function detect_social_media_links($html) {
        // Validate input
        if (!is_string($html) || empty(trim($html))) {
            return [];
        }

        $social_media = [];

        // Define social media platforms and their patterns
        $platforms = [
            'facebook' => '/https?:\/\/(?:www\.)?facebook\.com\/([a-zA-Z0-9._-]+)/i',
            'twitter' => '/https?:\/\/(?:www\.)?(?:twitter\.com|x\.com)\/(?:@|#!\/)?([a-zA-Z0-9_]{1,15})/i',
            'instagram' => '/https?:\/\/(?:www\.)?instagram\.com\/([a-zA-Z0-9._-]+)/i',
            'linkedin' => '/https?:\/\/(?:www\.)?linkedin\.com\/(company|in)\/([a-zA-Z0-9_-]+)/i',
            'youtube' => '/https?:\/\/(?:www\.)?youtube\.com\/(user\/[a-zA-Z0-9_-]+|channel\/[a-zA-Z0-9_-]+|c\/[a-zA-Z0-9_-]+)/i',
            'tiktok' => '/https?:\/\/(?:www\.)?tiktok\.com\/@([a-zA-Z0-9._-]+)/i',
            'pinterest' => '/https?:\/\/(?:www\.)?pinterest\.(com|co\.uk|de|fr|es|it|nl|ru|jp|br|cl|mx|ca|au|nz)\/([a-zA-Z0-9_-]+)/i',
            'reddit' => '/https?:\/\/(?:www\.)?reddit\.com\/user\/([a-zA-Z0-9_-]+)/i',
            'snapchat' => '/https?:\/\/(?:www\.)?snapchat\.com\/add\/([a-zA-Z0-9_-]+)/i',
            'telegram' => '/https?:\/\/(?:www\.)?t\.me\/([a-zA-Z0-9_-]+)/i',
            'whatsapp' => '/https?:\/\/(?:www\.)?wa\.me\/([a-zA-Z0-9_-]+)/i',
            'vimeo' => '/https?:\/\/(?:www\.)?vimeo\.com\/([a-zA-Z0-9_-]+)/i',
            'soundcloud' => '/https?:\/\/(?:www\.)?soundcloud\.com\/([a-zA-Z0-9_-]+)/i',
            'spotify' => '/https?:\/\/(?:www\.)?open\.spotify\.com\/user\/([a-zA-Z0-9_-]+)/i',
            'discord' => '/https?:\/\/(?:www\.)?discord\.gg\/([a-zA-Z0-9_-]+)/i',
            'medium' => '/https?:\/\/(?:www\.)?medium\.com\/@([a-zA-Z0-9_-]+)/i',
            'tumblr' => '/https?:\/\/([a-zA-Z0-9_-]+)\.tumblr\.com/i',
            'flickr' => '/https?:\/\/(?:www\.)?flickr\.com\/photos\/([a-zA-Z0-9_-]+)/i',
            'behance' => '/https?:\/\/(?:www\.)?behance\.net\/([a-zA-Z0-9_-]+)/i',
            'dribbble' => '/https?:\/\/(?:www\.)?dribbble\.com\/([a-zA-Z0-9_-]+)/i',
            'github' => '/https?:\/\/(?:www\.)?github\.com\/([a-zA-Z0-9_-]+)/i',
            'gitlab' => '/https?:\/\/(?:www\.)?gitlab\.com\/([a-zA-Z0-9_-]+)/i',
            'bitbucket' => '/https?:\/\/(?:www\.)?bitbucket\.org\/([a-zA-Z0-9_-]+)/i',
            'slack' => '/https?:\/\/([a-zA-Z0-9_-]+)\.slack\.com/i',
            'patreon' => '/https?:\/\/(?:www\.)?patreon\.com\/([a-zA-Z0-9_-]+)/i',
            'etsy' => '/https?:\/\/(?:www\.)?etsy\.com\/shop\/([a-zA-Z0-9_-]+)/i',
            'amazon' => '/https?:\/\/(?:www\.)?amazon\.(com|co\.uk|de|fr|es|it|nl|ru|jp|br|cl|mx|ca|au|nz)\/([a-zA-Z0-9_-]+)/i',
            'ebay' => '/https?:\/\/(?:www\.)?ebay\.(com|co\.uk|de|fr|es|it|nl|ru|jp|br|cl|mx|ca|au|nz)\/usr\/([a-zA-Z0-9_-]+)/i',
            'bluesky' => '/https?:\/\/(?:www\.)?bsky\.app\/profile\/([a-zA-Z0-9._-]+)/i',
        ];

        foreach ($platforms as $platform => $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $profile_url = $matches[0]; // Full profile URL
                $username = $this->extract_username_from_url($platform, $profile_url); // Extract username or identifier
                $social_media[$platform][] = [
                    'url' => $profile_url,
                    'username' => $username,
                ];
            }
        }

        // Log a debug message if no social media links are found (optional)
        if (empty($social_media)) {
            error_log('[HellaZ SiteZ Analyzer] No social media links found in the provided HTML.');
        }

        return $social_media;
    }

    private function extract_username_from_url($platform, $url) {
        switch ($platform) {
            case 'facebook':
            case 'instagram':
            case 'tiktok':
            case 'reddit':
            case 'snapchat':
            case 'telegram':
            case 'whatsapp':
            case 'vimeo':
            case 'soundcloud':
            case 'spotify':
            case 'discord':
            case 'medium':
            case 'flickr':
            case 'behance':
            case 'dribbble':
            case 'github':
            case 'gitlab':
            case 'bitbucket':
            case 'patreon':
            case 'etsy':
            case 'amazon':
            case 'ebay':
            case 'bluesky':
                return basename($url);
            case 'youtube':
                // Handle YouTube variations: /user/username, /channel/channel_id, /c/custom_name
                $path = parse_url($url, PHP_URL_PATH);
                $segments = explode('/', trim($path, '/'));
                return end($segments);
            case 'linkedin':
                // Handle LinkedIn variations: /in/username, /company/company_name
                $path = parse_url($url, PHP_URL_PATH);
                $segments = explode('/', trim($path, '/'));
                return isset($segments[1]) ? $segments[1] : '';
            case 'tumblr':
                // Extract subdomain from Tumblr URLs
                $host = parse_url($url, PHP_URL_HOST);
                $subdomain = explode('.', $host)[0];
                return $subdomain;
            default:
                return '';
        }
    }
}
