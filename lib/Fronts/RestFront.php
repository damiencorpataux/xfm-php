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
 * Front controller class, rest flavour.
 * Deals with model output formatting.
 * @package xFreemwork
**/
class xRestFront extends xFront {

    var $encoding = 'UTF-8';

    var $xml_root_node = 'resultset';
    var $xml_default_node = 'item';

    var $params = array(
        'xformat' => 'php'
    );

    protected function __construct($params = null) {
        parent::__construct($params);
        // Switches encoding if applicable
        if (@$this->params['xencoding']) $this->encoding = $this->params['xencoding'];
    }

    function handle_error($exception) {
        try {
            print $this->encode(array(
                'error' => $exception->getMessage(),
                'message' => $exception->getMessage(), // Should be a i18n end user message
                'data' => @$exception->data,
                'status' => @$exception->status
            ));
        } catch(Exception $e) {
            echo 'Error: '.$e->getmessage();
        }
    }

    /**
     * @param mixed PHP variable to encode
     */
    function encode($data) {
        $format_method = "encode_{$this->params['xformat']}";
        if (!method_exists($this, $format_method)) throw new xException("REST format output not available: {$this->params['xformat']}", 501);
        $output = $this->$format_method($data);
        // Recodes output stream if necessary
        if ($this->encoding != 'UTF-8') {
            $output = iconv('UTF-8', "{$this->encoding}//TRANSLIT", $output);
        }
        return $output;
    }

    function encode_php($data) {
        return var_export($data, true);
    }

    function encode_json($data) {
        if (!function_exists('json_encode')) throw new xException("JSON encoding unavailable", 501);
        return json_encode($data);
    }

    function encode_xml($data) {
        $result = $this->encode_xml_nodes($data);
        if ($this->xml_root_node) {
            $open_tag = $this->xml_root_node;
            $close_tag = array_shift(explode(' ', $this->xml_root_node));
            $xml = "<{$open_tag}>{$result}</{$close_tag}>";
        } else {
            $xml = $result;
        }
        return "<?xml version=\"1.0\" encoding=\"{$this->encoding}\"?>\n{$xml}";
    }
    function encode_xml_nodes($data) {
        if (!is_array($data)) return $data;
        $r = '';
        foreach ($data as $key => $value) {
            $open_tag = $key;
            $close_tag = array_shift(explode(' ', $key));
            if (is_array($value)) $value = $this->encode_xml_nodes($value);
            if (is_numeric($key)) $open_tag = $close_tag = $this->xml_default_node;
            $r .= "<{$open_tag}>{$value}</{$close_tag}>";
        }
        return $r;
    }

    function encode_xmlrpc($data) {
        if (!function_exists('xmlrpc_encode')) throw new xException("XMLRPC encoding unavailable", 501);
        return xmlrpc_encode($data);
    }
}

?>