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
     * The base path of the application (absolute form).
     * @var string
     */
    static $basepath;

    /**
     * The config path of the application (absolute form).
     * @var string
     */
    static $configpath;

    /**
     * The base path of the xfreemwork library (absolute form).
     * @var string
     */
    static $libpath;

    /**
     * The base uri of the application.
     * @var string
     */
    static $baseuri;

    /**
     * The base url of the application (including http://domaine.tld/baseuri).
     * @var string
     */
    static $baseurl;

    /**
     * The current language (as defined in config i18n.lang.alias directive).
     * @var string
     */
    static $lang;

    /**
     * The application configuration data object.
     * @var Zend_Config_Ini
     */
    static $config;

    /**
     * The numeric value of the PHP reporting level,
     * as set in the bootstrap from config file.
     * @var int
     */
    static $error_reporting;

    /**
     * The application-wide logger object.
     * @var xLogger
     */
    static $log;

    /**
     * The application-wide bootstrap object.
     * Set by xBootstrap.
     * @var xBootstrap
     * @see xBootstrap
     */
    static $bootstrap;

    /**
     * The application-wide router object.
     * Set by xBootstrap.
     * @var xRouter
     * @see xBootstrap
     */
    static $router;

    /**
     * The application-wide front object.
     * As instanciated by the xRouter.
     * @var xBootstrap
     * @see xRouter::route()
     */
    static $front;

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
