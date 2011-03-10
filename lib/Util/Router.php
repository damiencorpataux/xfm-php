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
 * Manages HTTP Requests routing.
 *
 * Responsibilities:
 * - parse the URL,
 * - instantiate the requested controller
 * - call the requested action (controller method) through the front controller
 * @package xFreemwork
**/
class xRouter {

    /**
     * The route fragments of the called uri.
     * @var array
     * @internal
     */
    var $fragments = array();

    /**
     * Routes patterns and params for route matching.
     * @var array
     * @internal
     */
    var $routes = array();

    /**
     * Generated params from route config, route arguments and post/get params.
     * @var array
     */
    var $params = array();


    /**
     * @param array $params Default route params.
     */
    function __construct($params = null) {
        if ($params) $this->params = $params;
    }

    /**
     * Adds a route pattern and params for route matching process.
     * @param string $pattern A rails-like route pattern, made of:
     *        - static keywords (e.g. somekeyword)
     *        - variable parameters, those will be set as parameters assigned
     *          with the route fragment value when calling the controller
     *          (e.g. :aparameter)
     *        Example: /somekeyword/:controller/:action/:page
     */
    function add($pattern, $params = null) {
        $this->routes[] = array(
            'pattern' => explode('/', $pattern),
            'params' => $params ? $params : array()
        );
    }

    function process_uri() {
        $base = substr($_SERVER['SCRIPT_NAME'], 0, -strlen('/index.php'));
        $uri = $_SERVER['REQUEST_URI'];
        $uri = substr($uri, strlen($base));
        // Removes query string from url
        if (strpos($uri, '?') !== false) $uri = substr($uri, 0, strpos($uri, '?'));
        $uri = trim($uri, '/');
        xContext::$log->log("Called URI: $uri", $this);
        $this->fragments = explode('/', $uri);
    }

    function match_route() {
        $this->process_uri();
        $routes = $this->routes;
        // Finds the best matching route
        foreach ($this->fragments as $ip => $part) {
            foreach ($routes as $ir => $route) {
                if (@substr($route['pattern'][$ip], 0, 1)  == ':') continue;
                if (@$route['pattern'][$ip] != $part) {
                    unset($routes[$ir]);
                    continue;
                }
            }
        }
        if (count($routes) < 1) throw new xException("No URI matching route", 404);
        return array_shift($routes);
    }

    /**
     * Calls controller action and outputs HTTP response body.
     * Controller actions are called through a front controller.
     * The front controllers acts as a Decorator.
     * (see Decorator design pattern)
     */
    function route() {
        $route = $this->match_route();
        // Get params from route fragments
        $routeargs = array();
        foreach ($this->fragments as $i => $part) {
            $var = $route['pattern'][$i];
            if (@substr($var, 0, 1) != ':') continue;
            $var = substr($var, 1);
            $routeargs[$var] = $part;
        }
        // Debug info
        xContext::$log->log('Matched route: '.implode('/', $route['pattern']), $this);
        // Params priority order: client request, route arguments (:), static route params, default params
        // TODO: asses security analysis on this priorities choice
        $this->params = array_merge($this->params, $route['params'], $routeargs, $_REQUEST);
        xContext::$log->log(array("Final params:", $this->params), $this);
        // Handles route actions
        if (isset($this->params['xredirect'])) {
            $url = xContext::$baseuri.$this->params['xredirect'];
            xContext::$log->log("Redirecting to url: $url", $this);
            xUtil::redirect($url);
            return;
        }
        // Calls front controller
        xFront::load($this->params['xfront'], $this->params)->handle();
    }
}

?>
