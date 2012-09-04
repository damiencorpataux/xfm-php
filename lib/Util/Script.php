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
 * This class is used to create command line scripts
 * @package xFreemwork
**/
abstract class xScript {

    var $timer_start = null;

    /**
     * Setups script components.
     * @param bool If true, calls the run() method automatically from constructor.
     */
    function __construct($autorun = true) {
        $this->timer_start();
        $this->setup();
        if ($autorun) $this->autorun();
    }

    function timer_start() {
        $this->timer_start = microtime();
    }
    function timer_lapse() {
        list($old_usec, $old_sec) = explode(' ', $this->timer_start);
        list($new_usec, $new_sec) = explode(' ', microtime());
        $old_mt = ((float)$old_usec + (float)$old_sec);
        $new_mt = ((float)$new_usec + (float)$new_sec);
        return $new_mt - $old_mt;
    }

    function setup() {
        // Bootstrap setup,
        // preventing outputting xBootstrap::handle_exception(),
        // outputting raw exception instead (see catch block)
        try {
            ob_start();
            $this->setup_bootstrap();
            @ob_end_flush();
        } catch (Exception $e) {
            @ob_end_clean();
            throw $e;
        }
        // Script setup
        $this->init();
        $this->print_profile_information();
        // Help display (if applicable)
        if ($this->opt('h', 'help')) {
            $this->display_help();
            exit(0);
        }
    }

    /**
     * Setups Bootstrap.
     * You might want to refine this method if you are using a custom bootstrap.
     */
    function setup_bootstrap() {
        require_once(dirname(__file__).'/../Core/Bootstrap.php');
        new xBootstrap();
    }

    function print_profile_information() {
        $p = xContext::$profile;
        $db = xContext::$config->db ? xContext::$config->db->toArray() : null;
        $this->log();
        $this->log("Running script with:");
        $this->log("Profile: {$p}", 1);
        if ($db) $this->log("Database: {$db['user']}@{$db['host']}/{$db['database']}", 1);
        $this->log("----");
        $this->log();
    }

    /**
     * Hook for initializing specific things.
     */
    function init() {}

    function autorun() {
        try {
            $this->run();
        } catch(Exception $e) {
            $message = $e->getMessage();
            $this->log("ERROR: {$message}");
            for ($i=0; $i<3; $i++) $this->log();
            throw $e;
        }
        // Displays run time
        $this->log();
        $elapsed = number_format($this->timer_lapse(), 4);
        $this->log('Runtime: '.$elapsed.' seconds');
    }

    /**
     * The actual user script logic.
     * This method is to be defined in child class.
     */
    abstract function run();

    /**
     * Outputs a string on stdout, optionally indenting it
     * @param string $msg The string to output
     * @param int $indent_level The indentation level
     * @param string $indent_symbol The indentation symbol to use
     */
    function log($msg = '', $indent_level=0, $indent_symbol='*', $newline=true) {
        $indent = '';
        for ($i=0; $i<$indent_level; $i++) $indent .= $indent_symbol;
        if (strlen($indent)) $indent .= ' ';
        print "{$indent}{$msg}";
        if ($newline) print "\n";
    }

    function prompt($msg, $indent_level=1, $indent_symbol='>', $newline=false) {
        $this->log();
        $this->log($msg, $indent_level, $indent_symbol, $newline);
        @ob_flush();
        $reply = trim(fgets(STDIN));
        return $reply;
    }
    function confirm($msg, $indent_level=1) {
        $reply = $this->prompt("{$msg} [y/N]: ", $indent_level);
        if (strtolower($reply) !== 'y') exit(0);
    }

    /**
     * Returns information about the given option:
     * - false: option is not found
     * - true: option is found with no value
     * - mixed: option is found with the given value
     * @see http://php.net/manual/en/function.getopt.php
     * @param string Options string as defined in PHP getopt()
     * @param boolean True to use long options, false to use short options (defaults to false)
     * @return array Information about the given option
     */
    function opt($name, $long_options=false) {
        $opts = (!$long_options) ?
            getopt($name) : getopt(null, xUtil::arrize($name));
        if (!$opts) return false;
        $opt = array_shift($opts);
        if (!$opt) return true;
        return $opt;
    }

    function display_help() {
        echo 'Usage: '.@$_SERVER['argv'][0]."\n\n";
        foreach (xUtil::arrize($this->help()) as $line) echo "{$line}\n";
        echo "\n";
    }

    function help() {
        return "Script description not available";
    }
}