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
 * Front controller base class.
 *
 * Responsibilities
 * - decorate controllers
 * @package xFreemwork
**/
abstract class xFront extends xRestElement {

    protected function __construct($params = null) {
        parent::__construct($params);
        $this->setup_i18n();
    }

    /**
     * Sets up the Gettext locale and domain according
     * the selected/guessed language.
     */
    function setup_i18n() {
        // Defines the current language
        $lang_available = xContext::$config->i18n->lang->alias->toArray();
        $lang_browser = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
        // If a language is given  and is available, use it
        if (array_key_exists(@$_REQUEST['xlang'], $lang_available)) {
            $lang = $_REQUEST['xlang'];
        // Else, if the session stored language is available, use it
        } elseif (array_key_exists(@$_SESSION['x']['lang'], $lang_available)) {
            $lang = $_SESSION['x']['lang'];
        // Else, use the browser language if it is available
        } elseif (array_key_exists($lang_browser, $lang_available)) {
            $lang = $lang_browser;
        // Else use the default language specified in config
        } else {
            $lang = xContext::$config->i18n->lang->default;
        }
        $_SESSION['x']['lang'] = xContext::$lang = $lang;
        // Sets up gettext
        $directory = xContext::$basepath.'/i18n/mo';
        $locale = $lang_available[$lang]; // Warning: must the exact locale as defined on the linux host,
        $domain = $lang;
        xContext::$log->log("Setting up gettext for '{$lang}' language, using '{$locale}' locale and '{$domain}' domain", $this);
        setlocale(LC_MESSAGES, $locale);
        putenv("LANG={$locale}"); // putenv only is useful for Windows
        bindtextdomain($domain, $directory);
        textdomain($domain);
        bind_textdomain_codeset($domain, 'UTF-8');
    }

    /**
     * Entry point for Front controller.
     * This method call the method related
     * to the HTTP status.
     * This is part of the REST orientatin of the framework.
     * @return mixed
     */
    function handle() {
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                return $this->get();
            break;
            case 'POST':
                return $this->post();
            case 'PUT':
                return $this->put();
            break;
            case 'DELETE':
                return $this->delete();
            break;
            default:
                header("HTTP/1.0 405 Method Not Allowed");
            break;
        }
    }

    /**
     * Loads and returns the specified front element.
     * For example, the following code will
     * load the fronts/web.php file.
     * and return an instance of the WebFront class:
     * <code>
     * xFront::load('web');
     * </code>
     * @param string The front to load.
     * @return xFront
     */
    static function load($name, $params = null) {
        $files = array(
            "{$name}Front" => xContext::$basepath."/fronts/{$name}.php",
            "x{$name}Front" => xContext::$libpath.'/Fronts/'.ucfirst($name).'Front.php'
        );
        return self::load_these($files, $params);
    }

    /**
     * Returns an error message in case of an error.
     * @return string The error message to output.
     */
    abstract function handle_error($exception);
}

?>