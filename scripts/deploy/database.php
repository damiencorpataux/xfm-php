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
 * Executes .sql files present in the sql/ directory (alphabetical order).
 * @package scripts
**/
class DeployDatabaseScript extends xScript {

    public $sql_path;

    function init() {
        $this->sql_path = xContext::$basepath.'/sql';
    }

    /**
     * Setups Bootstrap with database selection fault tolerance.
     * @see xScript::setup_bootstrap()
     */
    function setup_bootstrap() {
        try {
            parent::setup_bootstrap();
        } catch (Exception $e) {
            // Prevent outputting buffer started in xScript::setup()
            @ob_end_clean();
            // Manages null $db resource if configured database does not yet exist
            if (!$e->getMessage() == 'Could not select database') throw $e;
            // Sets config->db->database to null temporarily
            // to allow running xBootstrap::setup()
            $dbname = xContext::$config->db->database;
            xContext::$config->db->database = null;
            parent::setup_bootstrap();
            // Restores config->db->database value
            xContext::$config->db->database = $dbname;
        }
    }

    function run() {
        $this->log("Processing files in {$this->sql_path}");
        foreach($this->get_files_to_execute() as $file) {
            $this->log("Executing {$file}", 1);
            // Passes .sql file through xView
            // (allows using templated SQL for eg. variable database name)
            $view = xView::load(null);
            $view->path = xContext::$basepath;
            $view->default_tpl = $file;
            // Renders SQL
            $sql = $view->render();
            // Executes SQL
            try {
                // TODO: ensure splitting by ";\n" will never split inside an sql statement
                //       use a regex /;(\s)$/
                //       -OR- use CLIENT_MULTI_STATEMENTS (http://www.php.net/manual/fr/function.mysql-query.php#91669)
                // ---
                // Allows multiple statements in a single file,
                // discarding empty statements
                $statements = array_filter(array_map('trim', explode(";", $sql)));
                foreach ($statements as $statement) xModel::q($statement);
            } catch (Exception $e) {
                $this->log("Failed executing '{$file}'", 2);
                $this->log();
                throw $e;
            }
        }
    }

    /**
     * Returns an array containing the files to be parsed
     * @return array
     */
    function get_files_to_execute() {
        $filenames = xUtil::cascade_dir($this->sql_path, 'sql');
        foreach ($filenames as &$filename) {
            $filename = substr($filename, strlen(xContext::$basepath.'/'));
        }
        return $filenames;
    }

    function help() {
        $files = implode(
            "\n",
            array_map(
                function($e) { return "* {$e}"; },
                $this->get_files_to_execute()
            )
        );
        return array(
            "Executes alphabetically all the .sql files present",
            "in '{$this->sql_path}' and its subdirectories:",
            null,
            $files
        );
    }
}

new DeployDatabaseScript();
