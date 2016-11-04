<?php

class SF_XHProfLoader {
    private $started = false;

    function xhprof_is_enabled()
    {
      if (PHP_VERSION_ID < 50600) {
        return extension_loaded('xhprof');
      }
      else {
        return extension_loaded('xhprof') && extension_loaded('tideways');
      }
    }

    function should_profile_current_request()
    {
        return $this->xhprof_is_enabled();
    }

    function flags()
    {
        return XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY;
    }

    function options()
    {
        return array(
            'ignored_functions' => array(
                'call_user_func',
                'call_user_func_array',
                'preg_replace_callback',
                'do_action',
                'apply_filters',
            )
        );
    }

    function start($flags = SFHXPROF_FLAGS)
    {
        if (!$this->xhprof_is_enabled())
        {
            error_log('Failed to start profiling, the xhprof extension is not loaded. NOTE: the tideways extension is required for PHP 5.6+');
            return;
        }

        $fn = 'tideways_enable';
        if (!function_exists($fn)) {
          $fn = 'xhprof_enable';
        }

        $fn($this->flags(), $this->options());

        $this->started = true;
    }

    function is_started()
    {
        return $this->started;
    }

    function stop()
    {
        $fn = 'tideways_disable';
        if (!function_exists($fn)) {
          $fn = 'xhprof_disable';
        }
        return $fn();
    }
}

$sf_xhprof_loader = new SF_XHProfLoader();

if ($sf_xhprof_loader->should_profile_current_request()) $sf_xhprof_loader->start();
