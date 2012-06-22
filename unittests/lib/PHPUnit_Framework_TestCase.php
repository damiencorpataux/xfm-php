<?php

/**
 * Custom PHPUnit_Framework_TestCase.
 * Sets up custom authentication information with 'local-superuser' role.
 * @package unittests
 */

class xPHPUnit_Framework_TestCase extends PHPUnit_Framework_TestCase {

    /**
     * To be overriden in project-specific xPHPUnit_Framework_TestCase
     * child class if your project uses a custom xBootstrap.
     */
    function setup_bootstrap() {
        require_once(__dir__.'/../../lib/Core/Bootstrap.php');
        new xBootstrap();
    }

    function setUp() {
        $this->setup_bootstrap();
    }
}

?>