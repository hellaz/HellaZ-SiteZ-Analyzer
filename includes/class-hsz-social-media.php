// Social media detection logic
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
}
