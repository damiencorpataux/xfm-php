<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * Licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

// Requires PHPUnit dependancies
$vendors = dirname(__file__).'/vendors';
$paths = array(
    "{$vendors}/phpunit/",
    "{$vendors}/php-file-iterator/",
    "{$vendors}/php-code-coverage/",
    "{$vendors}/php-token-stream/",
    "{$vendors}/php-text-template/",
    "{$vendors}/php-timer/",
    "{$vendors}/phpunit-mock-objects/"
);
foreach ($paths as $path) set_include_path(
    get_include_path() . PATH_SEPARATOR . $path
);

// Requires PHPUnit library
require_once "PHPUnit/Autoload.php";

// Requires xfm custom xPHPUnit_Framework_TestCase
require_once "lib/PHPUnit_Framework_TestCase.php";

// PHPUnit autorun
if (PHP_SAPI==='cli') PHPUnit_TextUI_Command::main();