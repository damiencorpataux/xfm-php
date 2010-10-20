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
 * Updates po files with new strings id from controllers and views files
 * @package xFreemwork
**/

init();
process();

/**
 * Sets up script useful information
 */
function init() {
    global $po_path;
    require_once(dirname(__file__).'/../../lib/Util/Bootstrap.php');
    new xBootstrap('script');
    $po_path = xContext::$basepath.'/i18n/po';
    require_once(dirname(__file__).'/../util.php');
}

/**
 * Processes application views files
 * to adds missing translations into po files
 */
function process() {
    global $po_path;
    $files = get_files();    
    foreach (xContext::$config->i18n->lang->alias->toArray() as $lang => $locale) {
        $po_file = substr($po_path, strlen(xContext::$basepath.'/'))."/$lang.po";
        // Because xgettext references into the po file
        // without cleaning outdated references, the po file
        // has to be cleaned before running xgettext
        o("Cleaning po reference lines for file '{$po_file}'");
        clean_po_references($po_file);
        // Processes every view and controller files for each language
        // defined in .ini configuration
        o("Processing language '{$lang}'");
        foreach ($files as $file) {
            $parse_file = xContext::$basepath."/$file";
            o("Parsing file {$file} into {$po_file}", 1);
            parse($parse_file, $po_file);
        }
        // Fixes po file references path: paths must not be absolute,
        // rather relative from the app basepath
        file_put_contents(
            xContext::$basepath."/{$po_file}",
            str_replace(
                xContext::$basepath.'/',
                '',
                file_get_contents(xContext::$basepath."/{$po_file}")
            )
        );
    }
    die();
}

/**
 * Returns an array containing the files to be parsed
 * @return array
 */
function get_files() {
    o("Determining files to parse");
    $filenames = array_merge(
        explore_dir(xContext::$basepath.'/views', 'php'),
        explore_dir(xContext::$basepath.'/views', 'tpl'),
        explore_dir(xContext::$basepath.'/controllers', 'php')
    );
    foreach ($filenames as &$filename) {
        $filename = substr($filename, strlen(xContext::$basepath.'/'));
    }
    return $filenames;
}

/**
 * Cleans actual po file references (#: lines)
 * @param string $po_file PO file to clean (relative filename from application directory)
 */
function clean_po_references($po_file) {
    $po_full_file = xContext::$basepath."/{$po_file}";
    if (!file_exists($po_full_file)) return;
    $po_content = file_get_contents($po_full_file);
    file_put_contents($po_full_file, preg_replace('/^#:.*\n/m', '', $po_content));
}

/**
 * Launches xgettext and updates the given po file from the given file to parse
 * @param string $parse_file File to parse (relative filename from application directory)
 * @param string $po_file PO file to update (relative filename from application directory)
 * @return string Xgettext generated output
 */
function parse($parse_file, $po_file) {
    exec("cd ".xContext::$basepath." && \
        touch {$po_file} && \
        xgettext \
        --language=PHP \
        --from-code=utf8 \
        --sort-by-file \
        --omit-header \
        --no-wrap \
        --join-existing \
        -o {$po_file} \
        {$parse_file}", $output
    );
    return $output;
}