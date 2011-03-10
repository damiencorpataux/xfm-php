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
 * Base controller class with web niceties.
 * Deals with caller interactions (request & response).
 * @package xFreemwork
**/
abstract class xWebController extends xController {

    /**
     * Controller instance metadata (associative array).
     * @var array
     */
    var $meta = array();


    /**
     * Convenience method for merging this instance metadata
     * with additional metadata.
     * The given metadata will erase this instance metadata.
     * The merged metadata array replaces this instance metadata
     * and is also returned.
     * Metadata name format can be:
     *  - plain: eg. somename
     *  - hirearchic: eg. some/meta/name, which will result in an
     *    array('some' => array('meta' => array('name' => $contents)))
     * @param array|string Metadata array | Metadata name.
     * @param mixed (Mandatory if 1st param is a string) The metadata contents.
     * @return array Merged metadata array.
     */
    function add_meta($meta, $contents = null) {
        if (is_array($meta)) {
            return $this->meta = xUtil::array_merge($this->meta, $meta);
        } elseif (strpos($meta, '/') === false) {
            return $this->meta = xUtil::array_merge($this->meta, array($meta => $contents));
        } else {
            // TODO: avoid eval
            foreach (explode('/', $meta) as $part) @$a .= "['{$part}']";
            $m = array();
            eval("\$m{$a} = \$contents;");
            return $this->meta = xUtil::array_merge($this->meta, $m);
        }
    }

    /**
     * Save/retrieve value(s) in controller session.
     * Parameter behave as follows:
     * - If only key param given, the corresponding stored value is returned (null if key does not exist)
     * - If both key and value are given, the value is stored in session.
     * - If no argument given, return the session array of the controller.
     * @param string The key identifier.
     * @param string The value to store. If null or not provided, the 
     * @return mixed
     *     - The stored session array for the controller if $key is not provided or null
     *     - The stored value if a valid key is given without value.
     */
    function session($key = null, $value = null) {
        if (func_num_args() == 0) return @$_SESSION['x'][get_class($this)];
        if (func_num_args() == 1) return @$_SESSION['x'][get_class($this)][$key];
        elseif (func_num_args() == 2) {
            if (is_null($value)) unset($_SESSION['x'][get_class($this)][$key]);
            else return @$_SESSION['x'][get_class($this)][$key] = $value;
        }
    }

    /**
     * @see xWebFront::previous_url()
     */
    function previous_url() {
        return @xWebFront::$history[1] ? xWebFront::$history[1] : xContext::$baseuri;
    }
}

?>
