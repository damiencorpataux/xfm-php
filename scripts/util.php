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
 * Utility functions for xfreemwork scripts
 * @package xFreemwork
**/

/**
 * Outputs a string on stdout, optionally indenting it
 * @param string $msg The string to output
 * @param int $indent_level The indentation level
 */
function o($msg = '', $indent_level = 0) {
    $indent = '';
    for ($i=0; $i<$indent_level; $i++) $indent .= '*';
    if (strlen($indent)) $indent .= ' ';
    print "{$indent}{$msg}\n";
}

/**
 * Returns all absolute file names with the given extension
 * contained in the given directory (recursive lookup)
 * @param string $dir The directory to explore (e.g. /some/path)
 * @param string $extension The extension of the file (e.g. php)
 * @param array $filesnames Array of initial filenames (optional, used for recursive calls)
 * @return array Array of absolute filenames
 */
function explore_dir($dir, $extension) {
    $filenames = array();
    if (!is_dir($dir)) return $filenames;
    foreach (scandir($dir) as $file) {
        if ('.' == @$file{0}) continue;
        $file = "{$dir}/{$file}";
        if (is_file($file) && ".{$extension}" == substr($file, -strlen(".{$extension}"))) {
            //o("Matching file: ${file}", 1);
            $filenames[] = $file;
        } elseif (is_dir($file)) {
            //o("Exploring directory: ${file}", 1);
            $filenames = array_merge($filenames, explore_dir($file, $extension));
        }
    }
    return $filenames;
}

?>