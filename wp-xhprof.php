<?php
/*
Plugin Name: WP XHProf-Tideways
Description: Allows profiling WordPress using Facebook's XHProf Profiler extension.
Version: 0.1
Author: Stefan Fisk - 'Mucked about with' by Sut3kh
Author URI: http://stefanfisk.com
License: MIT
*/

class SF_XHProfProfiler {
    private $loader = null;
    private $namespace = 'wp-xhprof-tideways';

    function __construct()
    {
        global $sf_xhprof_loader;

        if (isset($sf_xhprof_loader)) $this->loader = $sf_xhprof_loader;

        if (!$this->is_started()) return;

        add_action('shutdown', array($this, 'stop_profiling'));
    }

    function is_started()
    {
        return $this->loader && $this->loader->is_started();
    }

    function profile_url($run_id, $namespace = null)
    {
        if (!$namespace) $namespace = $this->namespace;

        $relative_url = sprintf('xhprof/xhprof_html/index.php?run=%s&source=%s', urlencode($run_id), urlencode($namespace));

        return plugins_url($relative_url , __FILE__ );
    }

    function stop_profiling()
    {
        include_once('xhprof/xhprof_lib/utils/xhprof_lib.php');
        include_once('xhprof/xhprof_lib/utils/xhprof_runs.php');

        $xhprof_data = $this->loader->stop();
        $xhprof_runs = new XHProfRuns_Default();
        $run_id = $xhprof_runs->save_run($xhprof_data, $this->namespace);
        $run_url = $this->profile_url($run_id);

        // show the link if logged in
        if (is_user_logged_in()) {
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

new SF_XHProfProfiler();
