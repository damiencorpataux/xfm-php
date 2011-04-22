<?php

/**
 * Reusable component
 * @package xReusable
**/
class xDeveloperInformation {

    function render() {
        $map = array(
            'Host' => php_uname('n'),
            'Profile' => xContext::$profile,
            'Database' => xContext::$config->db->host.':'.xContext::$config->db->database
        );
        $view = xView::load(null);
        $view->path = dirname(__file__);
        return $view->apply('info.tpl', $map);
    }
}

?>