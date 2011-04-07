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
 * Front controller class, web flavour.
 * Deals with controller output decoration for producing a web page.
 * @package xFreemwork
**/
class xWebFront extends xFront {

    /**
     * The url history.
     * @var array
     */
    static $history;

    function __construct($params = null) {
        if (!session_id()) session_start();
        $this->setup_history();
        parent::__construct($params);
    }

    function handle_error($exception) {
        unset($this->params['xmodule']);
        $error_controller = xContext::$config->error->controller ?
            xContext::$config->error->controller : 'error';
        $this->params = xUtil::array_merge($this->params, array(
            'xcontroller' => xContext::$config->error->controller,
            'xaction' => 'default',
            'exception' => $exception
        ));
        $this->handle();
    }

    function get() {
        $controller_name = $this->params['xcontroller'];
        if (@$this->params['xmodule']) $controller_name = "{$this->params['xmodule']}/$controller_name";
        $controller = xController::load($controller_name, $this->params);
        $data = array();
        $data['html']['content'] = $controller->call(@$this->params['xaction']);
        // Renders and output the decorated controller action data
        $layout = xView::load('layout/layout', $data);
        $layout->meta = xUtil::array_merge($layout->meta, $controller->meta);
        print $layout->render();
    }

    function post() {
        $this->get();
    }

    /**
     * Setups the url history.
     * @see xWebFront::history
     */
    function setup_history() {
        if (!@$_SESSION['x']['xWebFront']['history']) $_SESSION['x']['xWebFront']['history'] = array();
        self::$history = &$_SESSION['x']['xWebFront']['history'];
        if (!@self::$history[0] || self::$history[0] != xUtil::current_url())
            array_unshift(self::$history, xUtil::current_url());
        self::$history = array_slice(self::$history, 0, 10);
    }

    /**
     * Return the last called page url if applicable, otherwise returns the site root page
     * @return string
     */
    static function previous_url() {
        return @xWebFront::$history[1] ? xWebFront::$history[1] : xContext::$baseuri;
    }

    /**
     * Add/retrieve messages in the web messages stack.
     * @param string The message to display.
     *     - If false, the messages stack is returned as an array.
     *     - If not defined or null, the messages stack is returned as an array and the stack is reset.
     * @param string The type of the message.
     * @return array|null
     *     - Null if $text is given,
     *     - An array containing the stored messages if $text is empty.
     */
    static function messages($text = null, $type = 'info') {
        if ($text === false) {
            $messages = @$_SESSION['x']['xWebFront']['messages'] ? $_SESSION['x']['xWebFront']['messages'] : array();
            return xUtil::arrize($messages);        
        } elseif (is_null($text)) {
            $messages = @$_SESSION['x']['xWebFront']['messages'] ? $_SESSION['x']['xWebFront']['messages'] : array();
            $_SESSION['x']['xWebFront']['messages'] = array();
            return xUtil::arrize($messages);
        } else {
            $_SESSION['x']['xWebFront']['messages'][] = array(
                'type' => $type,
                'text' => $text
            );
        }
    }
}

?>