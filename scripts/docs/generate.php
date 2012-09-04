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
 * @package xFreemwork
**/
class ApiDocGenerateScript extends xScript {

    public $phpdoc_path;
    public $project_path;
    public $output_path;

    public $phpdoc_options = array(
        "--title xFreemwork",
        //"--template new-black"
    );

    function init() {
        $this->phpdoc_path = '/tmp/phpDocumentor2';
        // Setups project & output paths
        $this->project_path = $this->project_path();
        $this->output_path = $this->output_path();
    }

    function run() {
        // Installs phpDocumentor2, skipped if --no-install argument is given
        if (!$this->opt('no-install', true)) $this->install_phpdocumentor();
        // Generates documentation
        $this->clear_output_path();
        $this->generate_api_doc();
    }

    /**
     * Returns the project path to be used for parsing.
     * @return string Project path.
     */
    function project_path() {
        return xContext::$libpath;
    }

    /**
     * Returns the output path to store the generated documentation files.
     * @return string Documentation output path.
     */
    function output_path() {
        return '/tmp/xfm-apidoc'; //xContext::$basepath;
    }

    function install_phpdocumentor() {
        $this->log("Installing Php Documentor 2...");
        // Ensures install path exists
        $this->log("Ensuring path exists", 1);
        @mkdir($this->phpdoc_path, 0777, true);
        // Remove directory contents
        $this->log("Removing existing files", 1);
        $cmd = "rm -rf {$this->phpdoc_path}/*";
        exec($cmd, $output, $status);
        if ($status) throw new xException("Error emptying directory {$this->phpdoc_path}");
        // Installs Php Documentor 2
        $this->log("Downloading and installing...", 1);
        $cmd = "cd {$this->phpdoc_path} && curl https://raw.github.com/phpDocumentor/phpDocumentor2/develop/installer.php 2>/dev/null | php";
        exec($cmd, $output, $status);
        if ($status) throw new xException("Error installing Php Documentor 2");
        // Changes to previous directory
        exec('cd -', $output, $status);
    }

    /**
     * Ensures that the output path exists and is empty.
     */
    function clear_output_path() {
        $this->log("Preparing output path ({$this->output_path})...");
        // Ensures output_path exists
        $this->log("Ensuring path exists", 1);
        @mkdir($this->output_path, 0777, true);
        // Remove directory contents
        $this->log("Removing existing files", 1);
        $cmd = "rm -rf {$this->output_path}/*";
        exec($cmd, $output, $status);
        if ($status) throw new xException("Error emptying directory {$this->output_path}");
    }

    /**
     * Generates API documentation files.
     */
    function generate_api_doc() {
        $phpdoc = "{$this->phpdoc_path}/bin/phpdoc.php";
        // Generates API documentation
        $this->log("Generating API documentation from {$this->project_path}");
        $cmd = implode(' ', array_merge(
            array(
                "php {$phpdoc}",
                "project:run -d {$this->project_path} -t {$this->output_path}",
            ),
            $this->phpdoc_options
        ));
        exec($cmd, $output, $status);
        if ($status) throw new xException("Error generating documentation", 500, $output);
        $this->log("Done", 1);
    }

    function help() {
        return array(
            'Generates API Documentation using phpDocumentor2',
            '',
            'Command line options',
            '--------------------',
            "\t--no-install\tskips installation of PhpDocumentor 2",
        );
    }
}

new ApiDocGenerateScript();
