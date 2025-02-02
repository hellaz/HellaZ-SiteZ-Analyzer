// Caching system (Transient API)
namespace HSZ;

class Cache {
    public function get_cached_data($url) {
        $cache_key = 'hsz_' . md5($url);
        $data = get_transient($cache_key);
        if ($data === false) {
            $data = $this->fetch_data($url);
            set_transient($cache_key, $data, DAY_IN_SECONDS);
        }
        return $data;
    }

    private function fetch_data($url) {
        // Fetch and process data here
        return [];
    }
}
