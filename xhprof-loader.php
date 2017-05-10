<?php

class SF_XHProfLoader {
    private $started = false;

    function xhprof_is_enabled()
    {
        return extension_loaded($this->get_extension());
    }

    function get_extension()
    {
        return PHP_VERSION_ID < 50600 ? 'xhprof' : 'tideways';
    }

    function should_profile_current_request()
    {
        return $this->xhprof_is_enabled();
    }

    function flags()
    {
        if ($this->get_extension() == 'tideways') {
            return TIDEWAYS_FLAGS_CPU | TIDEWAYS_FLAGS_MEMORY;
        }
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

    function start()
    {
        if (!$this->xhprof_is_enabled())
        {
            error_log('Failed to start profiling, the xhprof extension is not loaded. NOTE: the tideways extension is required for PHP 5.6+');
            return;
        }

        $fn = $this->get_extension() . '_enable';
        $fn($this->flags(), $this->options());

        $this->started = true;
    }

    function is_started()
    {
        return $this->started;
    }

    function stop()
    {
        $fn = $this->get_extension() . '_disable';
        return $fn();
    }
}
