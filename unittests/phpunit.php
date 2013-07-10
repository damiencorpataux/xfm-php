<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * Licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * PHPUnit tests CLI runner.
 * @package xUnittesting
 */
class xPhpUnit {

    function __construct() {
        $this->setup_phpunit();
        $this->setup_xfm();
        $this->setup_custom();
        // PHPUnit autorun
        if (PHP_SAPI==='cli') PHPUnit_TextUI_Command::main();
    }

    /**
     * Returns the base path to PHPUnits vendors path.
     * Override this to suit your custom project structur, if needed.
     */
    function xfm_path() {
        return __dir__.'/..';
    }

    /**
     * Returns the base path to PHPUnits vendors path.
     * @see xfm_path()
     */
    function phpunit_path() {
        return $this->xfm_path().'/unittests/vendors';
    }

    /**
     * Sets PHPUnits include path and includes Autoload.
     * @see xfm_path()
     */
    function setup_phpunit() {
        // Requires PHPUnit dependancies (using existing xfm submodules)
        $phpunit_path = $this->phpunit_path();
        $paths = array(
            "{$phpunit_path}/phpunit/",
            "{$phpunit_path}/php-file-iterator/",
            "{$phpunit_path}/php-code-coverage/",
            "{$phpunit_path}/php-token-stream/",
            "{$phpunit_path}/php-text-template/",
            "{$phpunit_path}/php-timer/",
            "{$phpunit_path}/phpunit-mock-objects/"
        );
        foreach ($paths as $path) set_include_path(
            get_include_path() . PATH_SEPARATOR . $path
        );
        // Requires PHPUnit library
        require_once "PHPUnit/Autoload.php";
    }

    /**
     * Simply loads xfm-specific xPHPUnit_Framework_TestCase.
     */
    function setup_xfm() {
        // Requires xfm custom xPHPUnit_Framework_TestCase
        $xfm_path = $this->xfm_path();
        require_once "{$xfm_path}/unittests/lib/PHPUnit_Framework_TestCase.php";
    }

    /**
     * Hook for setting up your custom stuff.
     * (eg. custom PHPUnit_Framework_TestCase classes)
     */
    function setup_custom() {
        // Requires project-specific xPHPUnit_Framework_TestCase child classes
        // require_once __dir__.'/lib/myPHPUnit_Framework_TestCase.php';
    }
}

new xPhpUnit();
