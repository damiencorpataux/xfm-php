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
 * Famework elements base class.
 * This clas implements:
 * - parameters array construction
 * - loader logic
 * @package xFreemwork
 */
abstract class xElement {

    /**
     * Instance parameters (associative array).
     * @var array
     */
    var $params = array();

    protected function __construct($params = null) {
        $this->params = xUtil::array_merge($this->params, $params);
        $this->init();
    }

    /**
     * Hook for subclass initialization logic.
     */
    protected function init() {}

    /**
     * Loads and returns an instance of the specified xElement.
     * @return xElement An new instance of the specified xElement.
     */
    abstract static function load($name, $params = null);

    /**
     * Helper method for loading and instanciating an xElement.
     * This method takes an associative array of classname => filename.
     * The method will try to load and instanciate each of the
     * given set of classname => filename. The first set to load
     * and instanciate successfully will be returned.
     * @param array Array of classname => filename to load.
     * @param array Array of parrameters to pass to the created xEelement instance.
     * @return xElement An xElement instance with the given parameters set.
     */
    protected function load_these($items, $params = null) {
        $valid = null;
        foreach ($items as $class => $file) {
            xContext::$log->log("Trying to load: $file", 'xElement');
            if (file_exists($file)) break;
            else $class = $file = null;
        }
        if (!$class || !$file) throw new xException('No valid file to load', 404, $items);
        xContext::$log->log("Loading: $file", 'xElement');
        require_once($file);
        xContext::$log->log("Instanciating: $class", 'xElement');
        return new $class($params);
    }
}

/**
 * Famework RESTful elements base class.
 * This clas implements the default responses for HTTP methods (get, post, put, delete)
 * @package xFreemwork
 */
abstract class xRestElement extends xElement {

    /**
     * Default behaviour
     * @throws xException
     * @return null
     */
    function get() {
        throw new xException('Not implemented', 501);
    }

    /**
     * Default behaviour
     * @throws xException
     * @return null
     */
    function post() {
        throw new xException('Not implemented', 501);
    }

    /**
     * Default behaviour
     * @throws xException
     * @return null
     */
    function put() {
        throw new xException('Not implemented', 501);
    }

    /**
     * Default behaviour
     * @throws xException
     * @return null
     */
    function delete() {
        throw new xException('Not implemented', 501);
    }
}

?>