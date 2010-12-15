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
 * This class is used to create command line scripts
 * @package xFreemwork
**/
abstract class xScript {

    function __construct($autorun = true) {
        $this->setup();
        if ($autorun) $this->run();
    }

    /**
     * Defines the location of the bootstrap to be used.
     * Enables to use an overridden bootstrap.
     * This property is set in the constructor.
     * @return string Bootstrap absolute location
     */
    function bootstrap_location() {
        return dirname(__file__).'/Bootstrap.php';
    }

    /**
     * Setups script components.
     * @param bool If true, calls the run() method automatically from constructor.
     */
    function setup() {
        $this->setup_bootstrap();
        $this->init();
        $this->print_profile_information();
    }

    /**
     * Setups Bootstrap.
     */
    function setup_bootstrap() {
        require_once($this->bootstrap_location());
        $b = new xBootstrap();
    }

    function print_profile_information() {
        $p = xContext::$profile;
        $db = xContext::$config->db->toArray();
        $this->log();
        $this->log("Running script with:");
        $this->log("Profile: {$p}", 1);
        $this->log("Database: {$db['user']}@{$db['host']}/{$db['database']}", 1);
        $this->log("----");
        $this->log();
    }

    /**
     * Hook for initializing specific things.
     */
    function init() {}

    /**
     * The actual user script logic.
     * This method is to be defined in child class.
     */
    abstract function run();

    /**
     * Outputs a string on stdout, optionally indenting it
     * @param string $msg The string to output
     * @param int $indent_level The indentation level
     */
    function log($msg = '', $indent_level = 0) {
        $indent = '';
        for ($i=0; $i<$indent_level; $i++) $indent .= '*';
        if (strlen($indent)) $indent .= ' ';
        print "{$indent}{$msg}\n";
    }

    /**
     * Returns command line options/arguments
     * @see http://php.net/manual/en/function.getopt.php
     * @param string Options string as defined in PHP getopt()
     * @return array An array of options/argument as defined in PHP geoopt()
     */
    function opts($opts = 'h') {
        $opts = getopt($opts);
        if (isset($opts['h'])) {
            echo 'Usage: '.@$_SERVER['argv'][0]."\n\n";
            foreach (xUtil::arrize($this->help()) as $line) echo "{$line}\n";
            echo "\n";
            exit();
        }
        return $opts;
    }

    function help() {
        return "Arguments description not available";
    }
}

?>
