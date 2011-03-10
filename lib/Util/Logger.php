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
 * This class is used for logging information into the text log file.
 * @package xFreemwork
**/
class xLogger {

    const DEBUG = 0;
    const INFO = 1;
    const NOTICE = 2;
    const WARNING = 3;
    const ERROR = 4;
    const FATAL = 5;
    const NONE = 6;

    /**
     * Logger levels labels.
     * @var array
     */
    var $labels = array(
        0 => 'DEBUG',
        1 => 'INFO',
        2 => 'NOTICE',
        3 => 'WARNING',
        4 => 'ERROR',
        5 => 'FATAL',
        6 => 'NONE'
    );

    /**
     * The minimum log level to be written in the log file.
     * @var integer
     */
    var $level;

    /**
     * The class names to be logged.
     * @var array
     */
    var $classes = array();

    /**
     * The path and filename to active log file.
     * @var string
     */
    var $file;

    /**
     * The microtimestamp of the logger instanciation time.
     * @var string
     */
    var $start_time;

    function __construct($filename, $level, $classes = array()) {
        $this->start_time = microtime(true);
        $this->level = $level;
        if (self::NONE == $this->level) return;
        $this->classes = $classes;
        $this->file = fopen($filename, "a");
        if (!$this->file) throw new Exception("Could not open log file: $filename");
        fwrite($this->file, "\n--8<---------------------------------------------------------------------------\n");
        // Writes the requested URL
        $url = @$_SERVER['HTTP_HOST'].@$_SERVER['REQUEST_URI'];
        $url = $url ? $url : '[No url]';
        fwrite($this->file, "URL: {$url}\n");
        // Writes the requester IP
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) $ip=$_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
        else $ip=$_SERVER['REMOTE_ADDR'];
        fwrite($this->file, "IP: {$ip}\n");
    }

    function __destruct() {
        if (self::NONE == $this->level) return;
        $processing_time = microtime(true) - $this->start_time;
        fwrite($this->file, "\nTotal processing time: {$processing_time} second\n");
        fclose($this->file);
    }

    /**
     * Returns true if the given level and class has to be written into logfile
     * @param int $level The log level
     * @param string $class The class name
     * @return bool True if the log has to be written into logfile, false otherwise
     */
    function is_log($level, $class) {
        // Don't log if the logline level is lower than the logger level
        if ($level < $this->level && self::NONE == $this->level) return false;
        // Log if logline class
        if (!count($this->classes)) return true;
        // Log if no filter class is set
        elseif (!count($this->classes)) return true;
        // Log if logline class is a filter class
        elseif (count($this->classes) && in_array($class, $this->classes)) return true;
        // Log if logline class is a subclass of a filter class
        else foreach($this->classes as $class_to_log) {
            if (is_subclass_of($class, $class_to_log)) return true;
        }
        return false;
    }

    /**
     * Writes a log line into the log file.
     * @param string|mixed|array $msgs Content (or array of content) to log.
     *        If the content is not scalar, print_r is applied.
     * @param Object|string $instance The instance that produces the log.
     *        Useful for tracing the class name of the object.
     * @param integer $level The log level for the log line.
     */
    function log($msgs, $instance = null, $level = self::DEBUG) {
        $class = is_object($instance) ? get_class($instance) : (string)$instance;
        if (!$this->is_log($level, $class)) return;
        $datum = date('Y-m-d H:i:s');
        $level = $this->labels[$level];
        $class = is_object($instance) ? get_class($instance) : (string)$instance;
        $msgs = is_array($msgs) ? $msgs : array($msgs);
        foreach ($msgs as $i => $msg) {
            if (!is_scalar($msg)) $msg = trim(print_r($msg, true));
            $line = $i == 0 ? "[{$datum}] [{$level}] [{$class}] *** " : '';
            $line .= "{$msg}\n";
            fwrite($this->file, $line);
        }
    }

}

?>