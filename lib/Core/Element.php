<?php

/*
 * (c) 2012 Damien Corpataux
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

    /**
     * Plugins instances.
     * @var array
     * @see xPlugin
     */
    var $plugins = array();

    /**
     * Class constructor.
     * Classes must be instanciated using xElement::load().
     * @see load()
     */
    protected function __construct($params = null) {
        // Ensures the given params is an array
        if (!is_null($params) && !is_array($params)) $params = xUtil::arrize($params);
        $this->params = xUtil::array_merge($this->params, $params);
    }

    /**
     * Loads and stores and activates an instance of the specified plugin with the given params.
     * <code>
     * $this->loadplugin('test', array('text' => 'Sample text.'));
     * </code>
     * @return xPlugin The stored plugin instance
     */
    protected function load_plugin($name, $params=array()) {
        return $this->plugins[] = xPlugin::load($name, $params);
    }

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
        // Checks if instantiable before returning instance
        // FIXME: This doesn't work (plain classes are said not instantiable)
        //$rc = new ReflectionClass($class);
        //if (!$rc->IsInstantiable()) throw new xException("Cannot instanciate {$class}");
        return new $class($params);
    }
}

/**
 * Framework RESTful elements base class.
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