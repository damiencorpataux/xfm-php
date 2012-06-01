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
 * This class is a dummy logger.
 * Used for early stage bootstrap setup.
 * @package xFreemwork
**/
class xDummyLogger {
    function __call($m, $a) {}
}

/**
 * This class creates the application context and launches the router.
 *
 * Responsibilities
 * - create application context (environment variables, configuration, database, etc)
 * - launch the router and output the HTTP response body
 * @package xFreemwork
**/
class xBootstrap {

    function __construct($profile=null) {
        try {
            // Setups application
            $this->setup($profile);
            // References bootstra instance in xContext
            xContext::$bootstrap = $this;
        } catch (Exception $e) {
            $this->handle_exception($e);
            throw $e;
        }
    }

    /**
     * Called when an exception is catched by the bootstrap.
     * This method handles exceptions
     * and outputs the HTTP response body.
     * @param Exception The catched exception.
     */
    static function handle_exception($exception) {
        // Sends HTTP error status
        $status = @$exception->status ? $exception->status : 500;
        header(xException::$statuses[$status]);
        // Calls specific front error handler
        if (xContext::$router) $error_front = xFront::load(
            xContext::$router->params['xfront'],
            xContext::$router->params
        );
        if (@$error_front) $error_front->handle_error($exception);
        else throw $exception;
    }

    /**
     * Setups the application context.
     * @param string The profile to load (defaults to 'development')
     */
    function setup($profile) {
        $this->setup_includes();
        if ($profile) xContext::$profile = $profile;
        $this->setup_dummy_log();
        xContext::$basepath = substr(dirname(__file__), 0, strpos(dirname(__file__), '/lib'));
        xContext::$libpath = substr(dirname(__file__), 0, -strlen('/Util'));
        xContext::$baseuri = substr($_SERVER['SCRIPT_NAME'], 0, -strlen('/index.php'));
        xContext::$baseurl = xUtil::url(xContext::$baseuri, true);
        $this->setup_includes_externals();
        $this->setup_config();
        $this->setup_error_reporting();
        $this->setup_error_handler();
        $this->setup_log();
        $this->setup_router();
        $this->setup_i18n();
        $this->setup_db();
        $this->setup_auth();
        $this->setup_addons();
    }

    /**
     * Runs the application and outputs the HTTP response body.
     */
    function run() {
        try {
            xContext::$router->route();
        } catch (Exception $e) {
            $this->handle_exception($e);
        }
    }

    function setup_includes() {
        // Set xFreemwork lib include path
        set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__file__).'/../');
        require_once('Core/Logger.php');
        require_once('Core/Util.php');
        require_once('Core/Exception.php');
        require_once('Core/Context.php');
        require_once('Core/Config.php');
        require_once('Core/Bootstrap.php');
        require_once('Core/Element.php');
        require_once('Core/Controller.php');
        require_once('Core/WebController.php');
        require_once('Front/Front.php');
        require_once('Front/WebFront.php');
        require_once('Front/RestFront.php');
        require_once('Front/ApiFront.php');
        require_once('Front/JsFront.php');
        require_once('Front/ModelFront.php');
        require_once('Front/RssFront.php');
        require_once('Util/Router.php');
        require_once('Util/Script.php');
        require_once('Util/Auth.php');
        require_once('Util/Form.php');
        require_once('Data/Validator.php');
        require_once('Data/Model.php');
        require_once('Data/ModelPostgres.php');
        require_once('Data/ModelMysql.php');
        require_once('Data/Transaction.php');
        require_once('View/View.php');
        require_once('Misc/Helpers/FormHelper.php');
        require_once('Misc/Helpers/ValidatorHelper.php');
    }

    function setup_dummy_log() {
        xContext::$log = new xDummyLogger();
    }

    function setup_includes_externals() {
        // Set general lib include path
        set_include_path(get_include_path() . PATH_SEPARATOR . xContext::$basepath.'/lib/');
    }

    function setup_config() {
        $config_path = xContext::$basepath.'/config';
        // Detects profile to be used
        if (!xContext::$profile) {
            try {
                $profile = new xZend_Config_Ini(
                    "{$config_path}/default.ini",
                    'profile',
                    array('allowModifications' => true)
                );
            } catch (Exception $e) {
                throw new xException(
                    'Could not read default [profile] from config file (default.ini): '.$e->getMessage()
                );
            }
            foreach ($this->get_config_files() as $file) {
                try { $profile->merge(new xZend_Config_Ini($file, 'profile')); }
                catch (Exception $e) { continue; }
            }
            if ($profile->name) xContext::$profile = $profile->name;
        }
        // Loads default configuration file
        try {
            xContext::$config = new xZend_Config_Ini(
                "{$config_path}/default.ini",
                xContext::$profile,
                array('allowModifications' => true)
            );
        } catch (Exception $e) {
            throw new xException(
                'Could not read config file (default.ini), profile ('.xContext::$profile.'): '.$e->getMessage()
            );
        }
        // Merges environment (host and/or app-path specific) configuration file
        foreach ($this->get_config_files() as $file) {
            try { xContext::$config->merge(new xZend_Config_Ini($file, 'overrides')); }
            catch (Exception $e) { continue; }
        }
        if (!@xContext::$config) xContext::$config = new stdClass();
    }

    /**
     * Returns an array containing the files elligible for
     * configuration overrides, in the correct order.
     * @return array
     */
    protected function get_config_files() {
        $config_path = xContext::$basepath.'/config';
        $host = php_uname('n');
        $app_path = str_replace('/', '-', trim(xContext::$basepath, '/'));
        $files = array(
            "{$config_path}/{$host}.ini",
            "{$config_path}/{$host}_{$app_path}.ini"
        );
        foreach ($files as $i => $file) {
            if (!file_exists($file)) unset($files[$i]);
        }
        return $files;
    }

    function setup_error_reporting() {
        // Redefines php error reporting level
        $level = xContext::$config->error->reporting ? xContext::$config->error->reporting : 0;
        $level_numeric = is_int($level) ? $level : constant($level);
        xContext::$error_reporting = $level_numeric;
        error_reporting($level_numeric);
    }

    function setup_error_handler() {
        if (!function_exists('myErrorHandler')) {
            function myErrorHandler($errno, $errstr, $errfile, $errline) {
                $exception = new xException($errstr, 500);
                xBootstrap::handle_exception($exception);
            }
        }
        // TODO: this should catch all errors excpet notices and warnings
        set_error_handler("myErrorHandler", E_ERROR);
    }

    function setup_log() {
        $file = xContext::$config->log->file ? xContext::$config->log->file : '/tmp/xfreemwork.log';
        $level = xContext::$config->log->level ? xContext::$config->log->level : 'NONE';
        $classes = xContext::$config->log->classes ? explode(',', xContext::$config->log->classes) : null;
        xContext::$log = new xLogger($file, constant("xLogger::{$level}"), $classes);
    }

    function setup_db() {
        if (!@xContext::$config->db) return;
        xContext::$db = $this->create_db();
        if (!xContext::$db) throw new xException('Could not setup database: '.print_r(xContext::$config->db->toArray(), true));
    }

    function create_db() {
        xContext::$log->log("Setting up database link on host ".xContext::$config->db->host, $this);
        // Forks based on database driver
        $driver = @xContext::$config->db->driver ? xContext::$config->db->driver : 'mysql';
        xContext::$log->log("Using database driver: {$driver}", $this);
        $setup_function = "create_db_{$driver}";
        return $this->$setup_function();
    }
    function create_db_mysql() {
        $db = mysql_connect(
            xContext::$config->db->host,
            xContext::$config->db->user,
            xContext::$config->db->password,
            true // creates a new connection on every call
        );
        xContext::$log->log("Connecting to database ".xContext::$config->db->database, $this);
        if (!$db) throw new xException('Could not connect to database');
        if (!mysql_select_db(xContext::$config->db->database, $db)) {
            throw new xException('Could not select database');
        }
        mysql_set_charset('utf8', $db);
        xContext::$log->log('Setting database client encoding to: '.mysql_client_encoding($db), $this);
        return $db;
    }
    function create_db_postgres() {
        xContext::$log->log("Connecting to database ".xContext::$config->db->database, $this);
        $host = xContext::$config->db->host;
        $user = xContext::$config->db->user;
        $password = xContext::$config->db->password;
        $database = xContext::$config->db->database;
        $db = pg_connect(
            "host=$host port=5432 dbname=$database user=$user password=$password options='--client_encoding=UTF8'"
        );
        return $db;
    }

    function setup_auth() {
        xContext::$log->log("Setting up xAuth", $this);
        xContext::$auth = new xAuth();
    }

    /**
     * Sets up the default language.
     * Persistant language selection and gettext setup is done xWebFront controller.
     * @see xFront::setup_i18n()
     */
    function setup_i18n() {
        // If Gettext is not installed, simulates the _() function
        // and aborts i18n setup
        if (!function_exists('_')) {
            xContext::$log->log('Gettext is not installed', $this);
            function _($str) { return $str; };
            return;
        }
        // Sets default language from config
        xContext::$lang = xContext::$config->i18n->lang->default;
    }

    function setup_router() {
        xContext::$router = new xRouter(xContext::$config->route_defaults->toArray());
        xContext::$log->log(array("Setting routes"), $this);
        foreach (xContext::$config->route->toArray() as $params) {
            $params = $params->toArray();
            if (!isset($params['pattern'])) throw new xException("Route pattern mandatory in .ini file");
            $pattern = $params['pattern'];
            unset($params['pattern']);
            xContext::$router->add($pattern, $params);
        }
    }

    function setup_addons() {}

}