<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * LICENSE
 * This library is licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

require_once(dirname(__file__).'/../../lib/Util/Script.php');

/**
 * Compiles po files to mo files
 * @package xFreemwork
**/
class Po2MoScript extends xScript {

    public $po_path;
    public $mo_path;

    function init() {
        $this->po_path = xContext::$basepath.'/i18n/po';
        $this->mo_path = xContext::$basepath.'/i18n/mo';
    }

    function run() {
        $aliases = $this->get_configured_locales();
        // Compiles each configured alias .po file into .mo
        foreach ($aliases as $lang => $locale) {
            $po_file = "{$this->po_path}/{$lang}.po";
            $this->log("Processing po file: {$po_file}", 1);
            if (file_exists($po_file)) {
                $mo_file = "{$this->mo_path}/{$lang}/LC_MESSAGES/{$lang}.mo";
                $this->compile($po_file, $mo_file);
            } else {
                $this->log("File does not exist: {$po_file}", 2);
            }
            $this->log();
        }
        // A friendly reminder because we care about the user
        $this->log('Done');
        $this->log('You should restart you apache webserver to apply changes', 1);
        $this->log('sudo apache2ctl restart', 1);
    }

    /**
     * Retrives configured languages and returns an alias => locales array.
     * @return array
     */
    function get_configured_locales() {
        $this->log('Processing configured languages...');
        // Retrives configured languages
        $aliases_config = @xContext::$config->i18n->lang->alias;
        $aliases = $aliases_config ? $aliases_config->toArray() : array();
        if (!$aliases) $this->log('No configured languages found', 1);
        return $aliases;
    }

    /**
     * Creates a compiled mo file from a po file
     * @param $po_file the source po file
     * @param $mo_file the destination mo file
     */
    function compile($po_file, $mo_file) {
        if (!file_exists(dirname($mo_file))) {
            $dir = dirname($mo_file);
            $this->log("Creating directory for mo file: {$dir}", 2);
            mkdir($dir, 0755, true);
        }
        $this->log("Compiling file", 2);
        $this->log("Source po file: {$po_file}", 3);
        $this->log("Destination mo file: {$mo_file}", 3);
        exec("msgfmt -o {$mo_file} {$po_file}", $output, $r);
        if ($output) $this->log("Error compiling file: {$output}", 2);
    }

    function help() {
        return "This function compiles i18n/po/*.po files into i18n/mo/*.mo";
    }
}

new Po2MoScript();