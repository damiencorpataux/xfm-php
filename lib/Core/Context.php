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
 * This class contains the application context.
 * It is a globally accessible static class that contains application-wide
 * settings and tools
 * @package xFreemwork
 */
class xContext {

    /**
     * Application profile: used to determine in what environement the application runs.
     *
     * Should be defined by the bootstrap or by the main php file (index.php).
     *
     * Typically used to determine what configuration section should be used
     * from application .ini file.
     *
     * @var string
     */
    static $profile;

    /**
     * The base path of the xfreemwork library.
     *
     * @var string
     */
    static $libpath;

    /**
     * The base path of the application.
     *
     * @var string
     */
    static $basepath;

    /**
     * The base uri of the application.
     *
     * @var string
     */
    static $baseuri;

    /**
     * The base url of the application (including http://domaine.tld/baseuri).
     *
     * @var string
     */
    static $baseurl;

    /**
     * The current language (as defined in config i18n.lang.alias directive).
     *
     * @var string
     */
    static $lang;

    /**
     * The application configuration data object.
     * @var Zend_Config_Ini
     */
    static $config;

    /**
     * The application-wide logger object.
     * @var xLogger
     */
    static $log;

    /**
     * The application-wide router object.
     * @var xRouter
     */
    static $router;

    /**
     * The application-wide database object.
     * @var ressource
     */
    static $db;

    /**
     * The application-wide auth object.
     * @var xAuth
     */
    static $auth;


    /**
     * Prevents class instanciation.
     */
    private function __construct() {}

    /**
     * Returns a text dump of the context object for inspection.
     * @return string 
     */
    static function dump() {
        return print_r(get_class_vars("xContext"), true);
    }
}

?>