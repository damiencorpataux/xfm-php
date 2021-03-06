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
 * This class contains utility functions.
 * @package xFreemwork
 */
class xUtil {

    /**
     * Prevents class instanciation.
     */
    private function __construct() {}

    static function pre() {
        foreach (func_get_args() as $var) {
          echo '<pre style="text-align:left;color:gray;font-size:10px;font-family:monospace">'.print_r($var, true).'</pre>';
          echo '<hr/>';
        }
    }

    /**
     * Filesystem: copies the given source to the given destination, using the given options.
     * Useful for recursive file copy.
     * @param string The source file or directory.
     * @param string The destination file or directory.
     * @param string Options (defaults to 'rf'), valid options are:
     *               - r: recursive
     *               - f: force
     * @return bool True if success, false otherwise.
     */
    static function copy($source, $destination, $options = 'rf') {
        if ($options) $options = "-{$options}";
        xContext::$log->log(array(
            'Copying files',
            "Source: {$source}",
            "Destination: {$destination}",
            "Options: {$options}"
        ), 'xUtil');
        $o = exec("cp {$options} {$source} {$destination} && echo ok");
        return $o == 'ok';
    }

    /**
     * Filesystem: removes the given location
     * Useful for non-empty directory removal.
     * @param string The location (file or directory) to remove.
     * @param string Options (defaults to 'rf'), valid options are:
     *               - r: recursive
     *               - f: force
     * @return bool True if success, false otherwise.
     */
    static function remove($location, $options = 'rf') {
        if ($options) $options = "-{$options}";
        xContext::$log->log(array(
            'Removing file',
            "Location: {$location}",
            "Options: {$options}"
        ), 'xUtil');
        $o = exec("rm {$options} {$location} && echo ok");
        return $o == 'ok';
    }

    /**
    * Filesystem: Returns all absolute file names with the given $extension
    * contained in the given $dir (recursive lookup).
    * @param string The directory to cascade (e.g. /some/path).
    * @param string The extension of the file (e.g. php).
    * @param array Array of initial filenames (optional, used for recursive calls).
    * @return array Array of absolute filenames.
    */
    static function cascade_dir($dir, $extension) {
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
                $filenames = array_merge(
                    $filenames,
                    xUtil::cascade_dir($file, $extension)
                );
            }
        }
        return $filenames;
    }

    /**
     * Make sure the given value is an array by returning an array of the given value.
     * If the given value is NULL, returns an empty array.
     * If the given value is a scalar, returns an array containing the value.
     * If the given value is already an array, returns the given value without transformation.
     * @param mixed The value
     * @return array
     */
    static function arrize($value) {
        if (is_array($value)) return $value;
        elseif (is_null($value)) return array();
        return array($value);
    }

    /**
     * Merges arrays recursively, replacing existing keys.
     * Priority is given to the last given array parameter.
     *
     * Careful: This algorythm differs from PHP array_merge_recursive
     * in the sense that merging array with same-level-same-keyname
     * will remain unique when using xUtil::array_merge().
     *
     * Merge example:
     * <code>
     * print_r(xUtil::array_merge(array(
     *     'Index content',
     *     'assoc' => array(
     *         'a_key' => 'a_content',
     *         'b_key' => 'b_content',
     *         '2nd level index content'
     *     ),
     *     array(
     *         'key' => 'value'
     *     ),
     *     'this-key' => 'remains-unique',
     * ), array(
     *     'assoc' => array(
     *         'a_key' => 'a_content_modified',
     *         'c_key' => 'c_content',
     *     ),
     *     array(
     *         'key' => 'value'
     *     ),
     *     'Index content 2',
     *     'this-key' => 'remains-unique'
     * )));
     * </code>
     * will output:
     * <code>
     * Array
     * (
     *     [0] => Index content
     *     [assoc] => Array
     *         (
     *             [a_key] => a_content_modified
     *             [b_key] => b_content
     *             [0] => 2nd level index content
     *             [c_key] => c_content
     *         )
     *
     *     [1] => Array
     *         (
     *             [key] => value
     *         )
     *
     *     [2] => Array
     *         (
     *             [key] => value
     *         )
     *
     *    [3] => Index content 2
     *    [this-key] => remains-unique
     * )
     * </code>
     * @param array $array1,... unlimited optional Arrays to merge.
     * @return array Merged array.
     * @todo Write unit tests (use the above example for one of the tests).
     */
    static function array_merge() {
        $arrays = func_get_args();
        $merged = array_shift($arrays);
        foreach($arrays as $array) {
            //xContext::$log->log(array('xUtil::merge_array(): Merging array: ', $array), 'xUtil');
            if (is_null($array)) continue;
            if (!is_array($array)) throw new xException('xUtil::array_merge() can only merge arrays', 500, $arrays);
            foreach ($array as $key => &$value) {
                if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                    if (is_numeric($key)) $merged[] = xUtil::array_merge($merged[$key], $value);
                    else $merged[$key] = xUtil::array_merge($merged[$key], $value);
                } else {
                    if (is_numeric($key)) $merged[] = $value;
                    else $merged[$key] = $value;
                }
            }
        }
        return $merged;
    }

    /**
     * Returns a deep clone of the given array.
     *
     * @param array The array to clone.
     * @return array The clone of the given array.
     */
    static function array_clone($array) {
        // check if input is really an array
        if (!is_array($array)) return $array;
        // initialize return array
        $clone = array();
        // get array keys
        $aKeys = array_keys($array);
        // get array values
        $aVals = array_values($array);
        // loop through array and assign keys+values to new return array
        foreach ($array as $key => $cell) {
            // clone if object
            if (is_object($cell)) $clone[$key] = clone $cell; // recursively add array
            elseif (is_array($cell)) $clone[$key] = array_clone($cell); // assign just a plain scalar value
            else $clone[$key] = $cell;
        }
        return $clone;
    }

    /**
     * Filters and returns the given array, keeping only the given keys.
     * @param array The array to filter.
     * @param array|string The key(s) to keep.
     * @param bool If true, the filter is inverted. Defaults to false.
     * @return array The filtered array
     */
    static function filter_keys($array, $keys, $invert_filter = false) {
        $keys = xUtil::arrize($keys);
        if (!count($keys)) return $invert_filter ? $array : array();
        $keys_array = array_combine($keys, array_fill(0, count($keys), 1));
        if ($invert_filter) {
            return array_diff_key($array, $keys_array);
        } else {
            return array_intersect_key($array, $keys_array);
        }
    }

    /**
     * Returns true if the given needle(s) exist in the given haystack.
     * Note that multiple needles can be given.
     * @param array Haystack.
     * @param mixed Needle(s). Multiple needles can be provided within a array.
     * @param bool And search. False to perform an OR search (default), true to perform an AND search.
     * @param bool Strict. True to also check types.
     * @return bool
     */
    static function in_array($needles, $haystack, $and_search=false, $strict=false) {
        $needles = xUtil::arrize($needles);
        $matches = array();
        foreach ($needles as $needle) {
            $matches[] = in_array($needle, $haystack, $strict);
        }
        return $and_search ? min($matches) : max($matches);
    }

    /**
     * Returns the last class called, null if last call is not a class.
     * @return string The name of the last called class (null if not a class)
     */
    static function get_calling_class() {
        $trace=debug_backtrace();
        $caller=$trace[2];
        return @$caller['class'];
    }

    /**
     * Returns the last function called, null if last call is not a function.
     * @return string The name of the last called class (null if not a class)
     */
    static function get_calling_function() {
        $trace=debug_backtrace();
        $caller=$trace[2];
        return @$caller['function'];
    }

    /**
     * Returns the given $string with HTML tags stripped,
     * preserving null value for $string.
     * @param string|array The string (or array of string) to strip.
     * @param string Allowable HTML tags (those will not be stripped).
     * @return string|array The stripped string (or array is $string is an array).
     */
    static function strip_tags($string, $allowable_tags = null) {
        // Preserve a null value for $string
        if (is_null($string)) {
            return $string;
        }
        if (is_array($string)) {
            $strip_tags = function($v) use ($allowable_tags) {
                return strip_tags($v, $allowable_tags);
            };
            return array_map($strip_tags, $string);
        }
        return strip_tags($string, $allowable_tags);
    }

    /**
     * Returns the given $text trimmed to $max_characters,
     * taking care not to truncate words.
     * @param string The text to be trimmed.
     * @param int Max characters allowed.
     * @param string Optionnal suffix to append if text was trimmed.
     * @return string The trimmed text.
     */
    static function trim_text($text, $max_characters, $suffix = '') {
        $words = explode(' ', $text);
        $index = $count = 0;
        do {
            $count += strlen($words[$index])+1;
            $index++;
        } while ($count <= $max_characters);
        $trimmed_text = implode(' ', array_slice($words, 0, $index-1));
        // Trims text if the first word is too long
        if (!$trimmed_text) $trimmed_text = substr($text, 0, $max_characters);
        // Adds ... if necessary
        if (strlen($trimmed_text) < strlen($text)) {
            $trimmed_text = rtrim($trimmed_text, ',.');
            $trimmed_text .= $suffix;
        }
        return $trimmed_text;
    }

    /**
     * Issues cross-browser header for page redirection.
     * @param string url to redirect to.
     */
    static function redirect($url, $exit = true) {
        if (!@$url || @xContext::$config->prevent_redirect) return;
        header("Status: 301");
        header("Location: $url");
        if ($exit) exit;
    }

    /**
     * Returns document root relative url with the given url suffix.
     * Example: the following code returns
     * '/path/to/site/some_controller/some_action'
     * <code>
     * print url('some_controller/some_action');
     * </code>
     * @param string Suffix of the url.
     * @param bool If true, prepends protocol and hostname to the url
     * @return string Asbolute path url
     */
    static function url($suffix = null, $full = false) {
        $url = '';
        if ($full) {
            $protocol = @$_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
            $host = @$_SERVER['HTTP_HOST'];
            $port = $protocol == 'http' && @$_SERVER['SERVER_PORT'] == 80 ||
                    $protocol == 'https' && @$_SERVER['SERVER_PORT'] == 443 ?
                    '' : ':'.@$_SERVER['SERVER_PORT'];
            $url = "{$protocol}://{$host}{$port}";
        }
        return $url . xContext::$baseuri."/{$suffix}";
    }

    /**
     * Returns the current full url.
     * @param bool|array|string If true (default), query string is appended to returned url.
     *                   If false,no query string is appended to returned url.
     *                   If an array, the given associative array is merged into the current query string.
     *                   If a string, the given query string is used as is.
     * @return string Current url.
     */
    static function current_url($query_string = true) {
        $url =  @$_SERVER['HTTPS'] == 'on' ? 'https' : 'http';
        $url .= '://' . @$_SERVER['HTTP_HOST'];
        $url .= @$_SERVER['SERVER_PORT'] == 80 ? '' : @":{$_SERVER['SERVER_PORT']}";
        // Query string
        @list($uri, $qs) = explode('?', $_SERVER['REQUEST_URI']);
        $url .= $uri;
        if ($query_string === true) {
            $url .= $qs ? "?{$qs}" : '';
        } elseif (is_array($query_string)) {
            $url .= '?'.http_build_query(array_merge($_GET, $query_string));
        } elseif (is_string($query_string)) {
            $url .= "?{$query_string}";
        }
        return $url;
    }

    /**
     * Returns the extension of the given file.
     * @param string A filename.
     * @return string The extension.
     */
    static function extension($file) {
        return array_pop(explode('.', $file));
    }

    static function valid_email($value) { return xValidatorHelper::email($value); }
    static function valid_phone($value) { return xValidatorHelper::phone($value); }
    static function valid_url($value) { return xValidatorHelper::url($value); }
    static function valid_integer($value) { return xValidatorHelper::integer($value); }
    static function valid_length($value, $min_length = null, $max_length = null) { return xValidatorHelper::length($value, $min_length, $max_length); }

    static function format_phone($number) {
        // Different formatting for 0800, 084x, 090x
        if (preg_match('/0800|084\d|090\d/', substr($number, 0, 4)) > 0) {
            return preg_replace('/(\d{4})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4', $number);
        } else {
            return preg_replace('/(\d{3})(\d{3})(\d{2})(\d{2})/', '$1 $2 $3 $4', $number);
        }
    }

    static function format_money($value) {
        return number_format(xUtil::round_money($value), 2, '.', ' ');
    }
    static function round_money($value) {
        return round($value, 1);
    }

    /**
     * Returns an array containing date components from a ISO/US date.
     * Useful for parsing mysql date fields values.
     * @param string ISO/US formatted date (yyyy-mm-dd hh:mm:ss).
     * @return int The corresponding date components array.
     */
    static function dateparts($mysql_date) {
        // Extracts date components
        list($date, $time) = explode(' ', $mysql_date);
        list($year, $month, $day) = explode('-', $date);
        list($hour, $minute, $second) = explode(':', $time);
        // Returns date parts, casted to (int)
        return array(
            'year' => (int)$year,
            'month' => (int)$month,
            'day' => (int)$day,
            'hour' => (int)$hour,
            'minute' => (int)$minute,
            'second' => (int)$second
        );
    }

    /**
     * Returns a timestamp from a ISO/US date.
     * Useful for parsing mysql date fields values.
     * @param string ISO/US formatted date (yyyy-mm-dd hh:mm:ss).
     * @return int The corresponding timestamp.
     */
    static function timestamp($mysql_date) {
        if (!$mysql_date) return null;
        $parts = self::dateparts($mysql_date);
        // Fixes date components towards a valid date
        if (!$parts['year']) $parts['year'] = 1970;
        if (!$parts['month']) $parts['month'] = 1;
        if (!$parts['day']) $parts['day'] = 1;
        // Creates and returns timestamp
        return mktime(
            $parts['hour'],
            $parts['minute'],
            $parts['second'],
            $parts['month'],
            $parts['day'],
            $parts['year']
        );
    }

    /**
     * Returns a ISO/US formatted date from a unix timestamp.
     * Useful for mysql inserts.
     * @param string A unix timestamp.
     * @return int The corresponding ISO/US date (yyyy-mm-dd hh:mm:ss).
     */
    static function ustime($timestamp = null) {
        if (!$timestamp) $timestamp = time();
        return @date("Y-m-d H:i:s", $timestamp);
    }

    /**
     * Returns a formatted date from the given timestamp or ISO/US date.
     * @param int Timestamp.
     * @param string Value to return if timestamp is null.
     * @return string
     */
    static function date($timestamp = null, $null = null) {
        if (!$timestamp) return $null;
        // Manages ISO/US date
        if (!is_numeric($timestamp)) {
            $parts = self::dateparts($timestamp);
            $timestamp = self::timestamp($timestamp);
            // Manages incomplete date formatting
            if (!$parts['day']) $format = xContext::$config->i18n->format->date_month;
            if (!$parts['month']) $format = xContext::$config->i18n->format->date_year;
            if (!$parts['year']) $format = '';
        }
        if (!isset($format)) $format = xContext::$config->i18n->format->date;
        return trim(strftime($format, $timestamp));
    }

    /**
     * Returns a formatted time from the given timestamp or ISO/US date.
     * @param int Timestamp or ISO/US date.
     * @param string Value to return if timestamp is null.
     * @return string
     */
    static function time($timestamp = null, $null = null) {
        if (!$timestamp) return $null;
        if (!is_numeric($timestamp)) $timestamp = self::timestamp($timestamp);
        return strftime(xContext::$config->i18n->format->time, $timestamp);
    }

    /**
     * Returns a formatted date and time from the given timestamp or ISO/US date.
     * @param int Timestamp or ISO/US date.
     * @param string Value to return if timestamp is null.
     * @return string
     */
    static function datetime($timestamp = null, $null = null) {
        if (!$timestamp) return $null;
        return implode(' ', array_filter(array(
            self::date($timestamp), self::time($timestamp)
        )));
    }

    /**
     * Returns the timespan between the given timestamp and now.
     * @param int Timestamp (if not given, now is considered).
     * @return string
     */
    static function timeago($timestamp = null) {
        if (!$timestamp) return _('never');
        if (!is_numeric($timestamp)) $timestamp = self::timestamp($timestamp);
        $config = array(
            array(
                'interval' => 60,
                'factor' => 1,
                'scale' => array(_('second'), _('seconds'))
            ),
            array(
                'interval' => 3600,
                'factor' => 1/60,
                'scale' => array(_('minute'), _('minutes'))
            ),
            array(
                'interval' => 86400,
                'factor' => 1/60/60,
                'scale' => array(_('hour'), _('hours')),
            ),
            array(
                'interval' => 604800,
                'factor' => 1/60/60/24,
                'scale' => array(_('day'), _('days'))
            ),
            array(
                'interval' => 2592000,
                'factor' => 1/60/60/24/7,
                'scale' => array(_('week'), _('weeks'))
            ),
            array(
                'interval' => 31104000,
                'factor' => 1/60/60/24/30,
                'scale' =>array(_('month'), _('months'))
            ),
            array(
                'interval' => 31104001,
                'factor' => 1/60/60/24/360,
                'scale' => array(_('year'), _('years'))
            )
        );
        // Processes fuzzy calculation
        $seconds_ago = mktime() - $timestamp;
        $seconds = abs($seconds_ago);
        foreach ($config as $i => $item) {
            if($seconds < $item['interval']) break;
        }
        // Prepares output
        $diff = round($seconds*$item['factor']);
        $scale = $diff > 1 ? $item['scale'][1] : $item['scale'][0];
        $fuzzy = "{$diff} {$scale}";
        // Language dependant sentence
        switch (xContext::$lang) {
            case 'fr':
                return $seconds_ago < 0 ? 'dans '.$fuzzy : 'il y a '.$fuzzy;
                break;
            case 'de':
                return $seconds_ago < 0 ? 'in '.$fuzzy : 'vor '.$fuzzy;
                break;
            default:
                // English
                return $seconds_ago < 0 ? 'in '.$fuzzy : $fuzzy.' ago';
                break;
        }
    }

    /**
     * @deprecated
     */
    static function generatePsw($size) {
        return substr(md5(microtime()+'this is a secret salt'), 0, $size);
    }

    /**
     * Redefines the mail php function with encoding.
     * @param string Recipient email address.
     * @param string Mail subject.
     * @param string Mail body.
     * @param string Sender email address.
     * @param string Encoding (eg. UTF-8, iso-8859-1).
     * @param string
     * @return bool The PHP mail function return value.
     */
    static function mail($to, $subject , $message, $from, $encoding = 'utf-8') {
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= "Content-type: text/plain; charset={$encoding}" . "\r\n";
        return mail($to, $subject, $message, $headers.$from);
    }

    /**
     * Returns distance in meters between two points
     * @param float Longitude 1, in degrees
     * @param float Lattitude 1, in degrees
     * @param float Longitude 2, in degrees
     * @param float Lattitude 2, in degrees
     * @return float
     */
    static function distance($lon1, $lat1, $lon2, $lat2) {
        // Note: earth's circumference is 40030 Km long, divided in 360 degrees, that's 111190
        if ($lat1==$lat2 && $lon1==$lon2) return 0;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon1-$lon2));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        if ($dist>0) return round($dist * 111190);
        return 0;
    }


}
