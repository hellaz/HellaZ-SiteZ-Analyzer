<?php
namespace HSZ;

class SocialMedia {
    private $platforms = ['facebook', 'twitter', 'instagram', 'linkedin', 'youtube'];

    public function detect_links($html) {
        $links = [];
        foreach ($this->platforms as $platform) {
            $pattern = "/https?:\/\/(www\.)?$platform\.com\/[^\s\"']+/i";
            preg_match_all($pattern, $html, $matches);
            if (!empty($matches[0])) {
                $links[$platform] = array_unique($matches[0]);
            }
        }
        return $links;
    }
    public function detect_social_media_links($html) {
            $platforms = [
                'facebook' => '/facebook\.com/i',
                'twitter' => '/twitter\.com/i',
                'instagram' => '/instagram\.com/i',
                'linkedin' => '/linkedin\.com/i',
                'youtube' => '/youtube\.com/i',
                'vimeo' => '/vimeo\.com/i',
                'tiktok' => '/tiktok\.com/i',
                'bsky' => '/bsky\.app/i',
            ];
    
            $social_media = [];
            foreach ($platforms as $platform => $pattern) {
                if (preg_match_all($pattern, $html, $matches)) {
                    $social_media[$platform] = array_unique($matches[0]);
                }
            }
            return $social_media;
        }
    
}
