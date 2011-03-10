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
 * 
 * Responsibilities
 * - expose user actions
 * - manage user inputs
 * - output user data
 * @package xFreemwork
**/
abstract class xController extends xRestElement {

    protected function __construct($params = null) {
        parent::__construct($params);
    }

    /**
     * Loads and returns the controller specified object.
     * For example, the following code will
     * load the controllers/entry.php file.
     * and return an instance of the EntryController class:
     * <code>
     * xController::load('entry');
     * </code>
     * @param string The controller to load.
     * @return xController
     */
    static function load($name, $params = null) {
        $files = array(
            str_replace(array('/', '.'), '', $name)."Controller" => xContext::$basepath."/controllers/{$name}.php"
        );
        return self::load_these($files, $params);
    }

    /**
     * Calls a controller action and returns its output.
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
}

?>
