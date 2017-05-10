<?php

class Debug_Bar_WP_XHProf extends \Debug_Bar_Panel {
    public function init()
    {
        $this->title('XHProf');
    }

    public function prerender()
    {
        $this->set_visible(true);
    }

    public function render()
    {
        SF_XHProfProfiler::get_instance()->stop_profiling();
    }
}
