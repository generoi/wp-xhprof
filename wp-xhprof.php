<?php
/*
Plugin Name: WP XHProf-Tideways
Description: Allows profiling WordPress using Facebook's XHProf Profiler extension.
Version: 0.1.5
Author: Stefan Fisk - 'Mucked about with' by Sut3kh
Author URI: http://stefanfisk.com
License: MIT
*/

class SF_XHProfProfiler {

    private static $instance = null;

    private $loader = null;
    private $namespace = 'wp-xhprof-tideways';
    public $plugin_name = 'wp-xhprof';
    public $github_url = 'https://github.com/generoi/wp-xhprof';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'start_profiling']);
        add_action('plugins_loaded', [$this, 'init']);
        Puc_v4_Factory::buildUpdateChecker($this->github_url, __FILE__, $this->plugin_name);
    }

    public function init()
    {
        add_action('debug_bar_panels', [$this, 'add_debug_bar']);
        add_action('shutdown', [$this, 'stop_profiling']);
    }

    public function is_runnable()
    {
        $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
        $is_debug = defined('WP_DEBUG') && WP_DEBUG;
        $is_rest = defined('REST_REQUEST') && REST_REQUEST;
        $is_cli = (php_sapi_name() === 'cli');
        $is_customizer = is_customize_preview();
        return !$is_ajax && !$is_customizer && $is_debug && !$is_rest && !$is_cli;
    }

    public function is_started()
    {
        return $this->loader && $this->loader->is_started();
    }

    public function profile_url($run_id, $namespace = null)
    {
        if (!$namespace) $namespace = $this->namespace;

        $relative_url = sprintf('xhprof/xhprof_html/index.php?run=%s&source=%s', urlencode($run_id), urlencode($namespace));

        return plugins_url($relative_url , __FILE__);
    }

    public function add_debug_bar($panels)
    {
        remove_action('shutdown', array($this, 'stop_profiling'));
        if (!$this->is_started()) {
            return $panels;
        }
        require_once __DIR__ . '/includes/debug-bar.php';
        $panels[] = new Debug_Bar_WP_XHProf();
        return $panels;
    }

    public function start_profiling()
    {
        require_once __DIR__ . '/xhprof-loader.php';

        $this->loader = new SF_XHProfLoader();
        if ($this->loader->should_profile_current_request()) {
            $this->loader->start();
        }
    }

    public function stop_profiling()
    {
        if (!$this->is_started()) {
            return;
        }

        require_once __DIR__ . '/xhprof/xhprof_lib/utils/xhprof_lib.php';
        require_once __DIR__ . '/xhprof/xhprof_lib/utils/xhprof_runs.php';

        $xhprof_data = $this->loader->stop();
        $xhprof_runs = new XHProfRuns_Default();
        $run_id = $xhprof_runs->save_run($xhprof_data, $this->namespace);
        $run_url = $this->profile_url($run_id);

        if ($this->is_runnable()) {
            ?>
            <div style="padding: 1em;">
                <a href="<?php echo esc_attr($run_url); ?>" target="_blank">Profiler output</a>
            </div>
            <?php
        }



        // log the url
        error_log("XHProf Run $run_id: $run_url");
    }
}

if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    require_once $composer;
}

SF_XHProfProfiler::get_instance();
