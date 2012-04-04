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
 * Front controller class, rest flavour.
 * Deals with model output formatting.
 * @package xFreemwork
**/
class xRestFront extends xFront {

    var $encoding = 'UTF-8';

    var $xml_root_node = 'resultset';
    var $xml_default_node = 'item';

    var $params = array(
        'xformat' => 'xml'
    );

    /**
     * Mime types definition for each format
     */
    var $mimetypes = array(
        'xml' => 'text/xml',
        'json' => 'application/json', // http://www.ietf.org/rfc/rfc4627.txt
    );

    protected function __construct($params = null) {
        parent::__construct($params);
        // Switches encoding if applicable
        if (@$this->params['xencoding']) $this->encoding = $this->params['xencoding'];
        $this->handle_request_body_params();
    }

    /**
     * Merges the HTTP Request body paramters with the the instance parameters.
     */
    function handle_request_body_params() {
        // Merges HTTP Request body with the instance parameters,
        // instance params have priority for security reasons
        $body = $this->get_request_body();
        $params = $this->decode($body);
        $this->params = xUtil::array_merge(xUtil::arrize($params), $this->params);
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
     * Decodes and returns the given data into a PHP array or scalar type.
     * @param mixed PHP variable to decode.
     * @return mixed Decoded data
     */
    function decode($data) {
        $format = $this->params['xformat'];
        $format_method = "decode_{$format}";
        if (!method_exists($this, $format_method)) throw new xException("REST format input not allowed: {$format}", 501);
        // TODO: Recodes input stream if necessary
        $input = $this->$format_method($data);
        return $input;
    }
    function decode_php($data) {
        return unserialize($data);
    }
    function decode_json($data) {
        if (!function_exists('json_decode')) throw new xException("JSON decoding unavailable", 501);
        return json_decode($data, true);
    }
    function decode_xml($data) {
        // Warning: get_object_vars() might not be suitable for deep nested XML
        return @get_object_vars(simplexml_load_string($data));
    }
    function decode_xmlrpc($data) {
        if (!function_exists('xmlrpc_decode')) throw new xException("XMLRPC decoding unavailable", 501);
        return xmlrpc_decode($data);
    }


    /**
     * Encodes and returns the given data into the format specified by the xformat parameter.
     * @param mixed PHP variable to encode.
     * @return string Encoded data
     */
    function encode($data) {
        $format = $this->params['xformat'];
        $format_method = "encode_{$format}";
        if (!method_exists($this, $format_method)) throw new xException("REST format output not available: {$format}", 501);
        $output = $this->$format_method($data);
        // Recodes output stream if necessary
        if ($this->encoding != 'UTF-8') {
            $output = iconv('UTF-8', "{$this->encoding}//TRANSLIT", $output);
        }
        // Sets HTTP mime type
        $mime = $this->mimetypes[$format] ? $this->mimetypes[$format] : 'text/plain';
        header ("Content-Type: {$mime}; charset={$this->encoding}");
        // Returns output
        return $output;
    }
    function encode_php($data) {
        return serialize($data);
    }
    function encode_json($data) {
        if (!function_exists('json_encode')) throw new xException("JSON encoding unavailable", 501);
        // TODO: manage 'invalid UTF-8 sequence' case (PHP Warning issue)
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
        return "<?xml version=\"1.0\" encoding=\"{$this->encoding}\"?>\r\n{$xml}";
    }
    function encode_xml_nodes($data) {
        if (!is_array($data)) return $data;
        $r = '';
        foreach ($data as $tag => $value) {
            // Extracts tag:
            $open_tag = $tag;
            $close_tag = array_shift(explode(' ', $tag));
            if (is_array($value)) $value = $this->encode_xml_nodes($value);
            else $value = "<![CDATA[{$value}]]>";
            if (is_numeric($tag)) $open_tag = $close_tag = $this->xml_default_node;
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