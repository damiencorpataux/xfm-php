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
 * Updates po files with new strings id from controllers and views files
 * @package scripts
**/
class PoUpdateScript extends xScript {

    public $po_path;

    function init() {
        $this->po_path = xContext::$basepath.'/i18n/po';
    }

    function run() {
        $files = $this->get_files_to_parse();
        $aliases = $this->get_configured_langs();
        $this->log('Processing configured languages...');
        if (!$aliases) $this->log('No configured languages found', 1);
        foreach ($aliases as $lang => $locale) {
            // TODO: refactor: $po_file = "{$this->po_path}/{$lang}.po";
            $po_file = substr(
                $this->po_path,
                strlen(xContext::$basepath.'/')
            )."/$lang.po";
            // Because xgettext references into the po file
            // without cleaning outdated references, the po file
            // has to be cleaned before running xgettext
            $this->log("Cleaning po reference lines for file '{$po_file}'");
            $this->clean_po_references($po_file);
            // Processes every view and controller files for each language
            // defined in .ini configuration
            $this->log("Processing language '{$lang}'");
            foreach ($files as $file) {
                $parse_file = xContext::$basepath."/$file";
                $this->log("Parsing file {$file} into {$po_file}", 1);
                $this->parse($parse_file, $po_file);
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
            $this->log();
        }
    }

    /**
     * Retrives configured languages and returns an alias => locales array.
     * @return array
     */
    function get_configured_langs() {
        // Retrives configured languages
        $aliases_config = @xContext::$config->i18n->lang->alias;
        $aliases = $aliases_config ? $aliases_config->toArray() : array();
        return $aliases;
    }

    /**
     * Returns an array containing the files to be parsed
     * @return array
     */
    function get_files_to_parse() {
        $filenames = array_merge(
            xUtil::cascade_dir(xContext::$basepath.'/views', 'php'),
            xUtil::cascade_dir(xContext::$basepath.'/views', 'tpl'),
            xUtil::cascade_dir(xContext::$basepath.'/controllers', 'php')
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
            mkdir -p $(dirname {$po_file}) && \
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

    function help() {
        $aliases = implode(', ', array_keys($this->get_configured_langs()));
        return array(
            "Updates i18n/po/*.po files with i18n strings",
            "present in application views and controllers.",
            null,
            "Note that only configured languages will be updated:",
            $aliases ? $aliases : 'No configured language found.'
        );
    }
}

new PoUpdateScript();
