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
 * Famework plugin base class.
 * @package xFreemwork
 */
abstract class xPlugin extends xElement {

    /**
     * @param array
     * @param xElement Plugin loader instance.
     *
     */
    protected function __construct($params=array()) {
        parent::__construct($params);
        $this->init();
    }

    /**
     * Hook for plugin initialization logic.
     */
    function init() {}

    /**
     * Loads and returns the specified plugin element.
     * For example, the following code will
     * load the fronts/geolocalize.php file.
     * and return an instance of the WebFront class:
     * <code>
     * xPlugin::load('geolocalize');
     * </code>
     * @param string The plugin to load.
     * @return xPlugin
     */
    static function load($name, $params=array()) {
        $files = array(
            str_replace(array('/', '.', '-', '_'), '', $name)."Plugin" => xContext::$basepath."/plugins/{$name}.php",
            "x{$name}Plugin" => xContext::$libpath.'/Plugin/'.ucfirst($name).'Plugin.php'
        );
        return self::load_these($files, $params);
    }
}