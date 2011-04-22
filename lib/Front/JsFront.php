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
 * Front controller for fetching views js files and js context object.
 * Returns the specified js file content.
 * @todo Be able to hanlde for multiple files request (comma-separated),
 *       returning a unique concatenated and minified stream
 * @package xFreemwork
**/

class xJsFront extends xFront {

    var $allowed_mime_types = array(
        'js' => 'application/x-javascript',
        'css' => 'text/css'
    );

    function __construct($params = null) {
        parent::__construct($params);
    }

    function handle_error($exception) {
        print $exception->getMessage();
    }

    function set_header($extension) {
        $mime = @$this->allowed_mime_types[$extension];
        if ($mime) header("Content-type: {$mime}");
    }

    function get() {
        if ($this->params['view']=='context.js') {
            header('Content-type: application/javascript');
            print $this->get_context_js();
            return;
        }
        $file = xContext::$basepath."/views/{$this->params['view']}/{$this->params['jsfile']}";
        $extension = array_pop(explode('.', $file));
        $allowed_extensions = array_keys($this->allowed_mime_types);
        $allowed = in_array($extension, $allowed_extensions);
        if (!$allowed) throw new xException("Unauthorized file", 401);
        if (!file_exists($file)) throw new xException("File not found", 404);
        $this->set_header($extension);
        print file_get_contents($file);
    }

    /**
     * Return partial xContext state for javascript usage.
     * Careful: do not put sensible information here because it is world readable.
     * @return string Javascript context object
     */
    function get_context_js() {
        session_start(); // For accessign auth info
        return "
var x = x || {};
x.context = x.context || {};
x.context.baseuri = '".xContext::$baseuri."';
x.context.profile = '".xContext::$profile."';
x.context.config = x.context.config || {};
x.context.config.error = x.context.config.error || {};
x.context.config.error.reporting = '".xContext::$config->error->reporting."';
x.context.config.log = x.context.config.log || {};
x.context.config.log.level = '".xContext::$config->log->level."';
x.context.auth = x.context.auth || {};
x.context.auth.username = '".xContext::$auth->username()."';
";
    }
}

?>
