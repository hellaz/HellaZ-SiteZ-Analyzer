// Hook and filter system
namespace HSZ;

class Hooks {
    public function __construct() {
        add_action('hsz_before_analysis', [$this, 'before_analysis_hook']);
        add_action('hsz_after_analysis', [$this, 'after_analysis_hook']);
        add_filter('hsz_modify_output', [$this, 'modify_output']);
    }

    public function before_analysis_hook($url) {
        do_action('hsz_before_analysis', $url);
    }

    public function after_analysis_hook($data) {
        do_action('hsz_after_analysis', $data);
    }

    public function modify_output($output) {
        return apply_filters('hsz_modify_output', $output);
    }
}
