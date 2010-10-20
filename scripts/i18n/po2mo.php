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
 * Compiles po files to mo files
 * @package xFreemwork
**/

require_once(dirname(__file__).'/../util.php');

init();
process();
o();
o("You should restart you apache webserver to apply changes");
o();

/**
 * Sets up script useful information
 */
function init() {
    global $po_path, $mo_path;
    require_once(dirname(__file__).'/../../lib/Util/Bootstrap.php');
    new xBootstrap('script');
    $po_path = xContext::$basepath.'/i18n/po';
    $mo_path = xContext::$basepath.'/i18n/mo';
}

/**
 * Processes po files, using languages defined in configuration
 */
function process() {
    global $po_path, $mo_path;
    foreach (xContext::$config->i18n->lang->alias->toArray() as $lang => $locale) {
        o();
        $po_file = "{$po_path}/{$lang}.po";
        o("Processing po file: {$po_file}");
        if (!file_exists($po_file)) {
            o("File does not exist: {$po_file}", 1);
            continue;
        }
        $mo_file = "{$mo_path}/{$lang}/LC_MESSAGES/{$lang}.mo";
        compile($po_file, $mo_file);
    }
}

/**
 * Creates a compiled mo file from a po file
 * @param $po_file the source po file
 * @param $mo_file the destination mo file
 */
function compile($po_file, $mo_file) {
    if (!file_exists(dirname($mo_file))) {
        $dir = dirname($mo_file);
        o("Creating directory for mo file: {$dir}", 1);
        mkdir($dir, 0755, true);        
    }
    o("Compiling file", 1);
    o("Source po file: {$po_file}", 2);
    o("Destination mo file: {$mo_file}", 2);
    exec("msgfmt -o {$mo_file} {$po_file}", $output, $r);
    if ($output) o("Error compiling file: {$output}", 2);
}

?>