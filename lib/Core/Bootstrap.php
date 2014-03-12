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

    function __construct() {
        try {
            // Setups application
            $this->setup();
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
        if (!headers_sent()) header(xException::$statuses[$status]);
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
     */
    function setup() {
        $this->setup_includes();
        $this->setup_dummy_log();
        xContext::$basepath = substr(dirname(__file__), 0, strpos(dirname(__file__), '/lib'));
        xContext::$configpath = xContext::$basepath.'/config';
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
            $params = xContext::$router->route();
            // Calls front controller
            xContext::$front = xFront::load($params['xfront'], $params);
            xContext::$front->handle();
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
        // This dummy logger mocks the real logger API until it is setup.
        xContext::$log = new xDummyLogger();
    }

    function setup_includes_externals() {
        // Set general lib include path
        set_include_path(get_include_path() . PATH_SEPARATOR . xContext::$basepath.'/lib/');
    }

    function setup_config() {
        // Does not overwrite existing config to allow custom config injection
        // Eg. The /scripts/deploy.php can nullify the database configuration to allow
        //     the bootstrap to run before the actual database is created.
        // TODO: Allow Bootstrap user to switch profile and re-setup
        //       by using xBootstrap::setup('new-profile').
        if (xContext::$config instanceof xZend_Config_Ini) return;
        // Makes config_path variable local
        $config_path = xContext::$configpath;
        // Sets default profile to 'development'
        xContext::$profile = 'development';
        // Loads default configuration file
        // and create basic xZend_Config_Ini instance
        try {
            $config = new xZend_Config_Ini(
                "{$config_path}/default.ini",
                null,
                array(
                    'allowModifications' => true
                )
            );
        } catch (Exception $e) {
            throw new xException(
                'Could not read default.ini config file'.$e->getMessage()
            );
        }
        // Merges additionnal configuration file(s)
        foreach ($this->get_config_files('conf.d') as $file) {
            $config->merge(new xZend_Config_Ini($file));
        }
        // Merges instance-specific configuration file(s)
        $instance_host = php_uname('n');
        $instance_path = str_replace('/', '-', trim(xContext::$basepath, '/'));
        $instance_files = array_intersect(
            $this->get_config_files('instances'), array(
                "{$config_path}/instances/{$instance_host}.ini",
                "{$config_path}/instances/{$instance_host}_{$instance_path}.ini"
            )
        );
        foreach ($instance_files as $file) {
            $config->merge(new xZend_Config_Ini($file));
        }
        // Sets up profile name according instance config
        xContext::$profile = $profile = $config->profile ?
            $config->profile : xContext::$profile;
        // Merges profile-specific configuration files
        foreach ($this->get_config_files('profiles') as $file) {
            // Skips filenames that do not begin with $profile
            $parts = explode('/', $file);
            $filename = array_pop($parts);
            if (substr($filename, 0, strlen($profile)) != $profile) continue;
            $config->merge(new xZend_Config_Ini($file));
        }
        xContext::$config = $config;
    }

    /**
     * Returns an array containing the existing additional configuration files,
     * in alphabetical order.
     * @param string|array The path(s) to process, relative to config/ directory.
     * @return array
     */
    protected function get_config_files($paths) {
        $config_path = xContext::$basepath.'/config';
        $files = array();
        $paths = xUtil::arrize($paths);
        foreach ($paths as $path) {
            $path = "{$config_path}/{$path}";
            $f = xUtil::arrize(@scandir($path));
            foreach ($f as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if ($ext != 'ini') continue;
                $file = "{$path}/$file";
                if (is_file($file)) $files[] = $file;
            }
        }
        // Host/instance-specific files
        $host = php_uname('n');
        $app_path = str_replace('/', '-', trim(xContext::$basepath, '/'));
        $instance_files = array(
            "{$config_path}/{$host}.ini",
            "{$config_path}/{$host}_{$app_path}.ini"
        );
        foreach ($instance_files as $file) {
            if (is_file($file)) $files[] = $file;;
        }
        return $files;
    }

    function setup_error_reporting() {
        // Redefines php error reporting level
        $level = @xContext::$config->error->reporting ? xContext::$config->error->reporting : 0;
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
        $file = @xContext::$config->log->file ? xContext::$config->log->file : '/tmp/xfreemwork.log';
        $level = @xContext::$config->log->level ? xContext::$config->log->level : 'NONE';
        $classes = @xContext::$config->log->classes ? explode(',', xContext::$config->log->classes) : null;
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
        // Creates mysql link
        $host = xContext::$config->db->host;
        xContext::$log->log("Creating database link to host '{$host}'", $this);
        $db = mysql_connect(
            xContext::$config->db->host,
            xContext::$config->db->user,
            xContext::$config->db->password,
            true // creates a new connection on every call
        );
        if (!$db) throw new xException("Could create link to database");
        // Selects database (if applicable)
        $dbname = xContext::$config->db->database;
        if ($dbname) {
            xContext::$log->log("Selecting database '{$dbname}'", $this);
            $success = mysql_select_db(xContext::$config->db->database, $db);
            if (!$success) throw new xException("Could not select database '{$dbname}'");
        } else {
            xContext::$log->log("Skipping database selection", $this);
        }
        // Forces database link encoding
        xContext::$log->log('Setting database client encoding to: '.mysql_client_encoding($db), $this);
        mysql_set_charset('utf8', $db);
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
        // Sets default language from config (null if not defined)
        xContext::$lang = @xContext::$config->i18n->lang->default;
    }

    function setup_router() {
        $route_defaults = @xContext::$config->route_defaults ? xContext::$config->route_defaults->toArray() : array();
        xContext::$router = new xRouter($route_defaults);
        xContext::$log->log(array("Setting routes"), $this);
        // Sorts routes according their index in config
        $routes = xContext::$config->route ? xContext::$config->route->toArray() : array();
        ksort($routes);
        foreach ($routes as $params) {
            $pattern = @$params['pattern'];
            if (!$pattern) throw new xException("Route pattern mandatory in .ini file");
            xContext::$router->add($pattern, $params);
        }
    }

    function setup_addons() {}

}
