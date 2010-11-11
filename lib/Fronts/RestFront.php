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

    var $params = array(
        'xformat' => 'php'
    );

    protected function __construct($params = null) {
        parent::__construct($params);
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
        return $this->$format_method($data);
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
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"."<resultset>{$result}</resultset>";
    }
    function encode_xml_nodes($data) {
        if (!is_array($data)) return $data;
        $r = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) $value = $this->encode_xml_nodes($value);
            if (is_numeric($key)) $key = 'item';
            $r .= "<{$key}>{$value}</{$key}>";
        }
        return $r;
    }

    function encode_xmlrpc($data) {
        if (!function_exists('xmlrpc_encode')) throw new xException("XMLRPC encoding unavailable", 501);
        return xmlrpc_encode($data);
    }
}

?>