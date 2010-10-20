<?php
/*
 * (c) 2010 Damien Corpataux
 *
 * LICENSE
 * This library is licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * Base controller class.
 * Deals with caller interactions (request & response).
 * @package xFreemwork
**/
abstract class xController {

    /**
     * Controller instance parameters (associative array).
     * @var array
     */
    var $params = array();


    protected function __construct($params = null) {
        $this->params = xUtil::array_merge($this->params, $params);
        $this->init();
    }

    /**
     * Hook for subclass initialization logic
     */
    protected function init() {}

    /**
     * Loads and returns the controller specified object.
     * For example, the following code will
     * load the controllers/entry.php file.
     * and return an instance of the EntryController class:
     * <code>
     * xView::load('entry');
     * </code>
     * @param string The controller to load.
     * @return xController
     */
    static function load($name, $params = null) {
        $file = xContext::$basepath."/controllers/{$name}.php";
        xContext::$log->log("Loading controller: $file", 'xController');
        if (!file_exists($file)) throw new xException("Controller file not found (controller {$name})", 404);
        require_once($file);
        $class_name = str_replace(array('/', '.'), '', $name)."Controller";
        xContext::$log->log(array("Instanciating controller: $class_name"), 'xController');
        $instance = new $class_name($params);
        return $instance;
    }

    /**
     * Calls controller action and returns its output.
     * @return string
     */
    function call($action) {
        $action = !empty($action) ? $action : 'default';
        $action_method = str_replace('-', '', $action).'Action';
        if ($action{0} == '_') throw new xException("Action {$action} is not meant to be called", 401);
        xContext::$log->log("Calling controller {$action_method}() method", $this);
        if (!method_exists($this, $action_method)) throw new xException("Controller action not found: {$action}", 404);
        return $this->$action_method();
    }

    // select
    function get() {
        throw new xException('Not implemented', 501);
    }

    // insert
    function post() {
        throw new xException('Not implemented', 501);
    }

    // update
    function put() {
        throw new xException('Not implemented', 501);
    }

    // delete
    function delete() {
        throw new xException('Not implemented', 501);
    }
}

?>
