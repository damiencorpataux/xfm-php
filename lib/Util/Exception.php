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
 * This class enhances the native PHP Exception with xFreemwork specific information.
 * @package xFreemwork
**/
class xException extends Exception {

    /**
     * The HTTP status to be associated with the exception, if applicable.
     * @var integer
     */
    var $status;

    /**
     * Static definition of possible HTTP statues
     * with their associated text header definition.
     * @var array
     * @todo fixme: successful 2xx responses do not belong to an exception,
     */
    static $statuses = array(
        // Sucessful 2xx
        200 => 'HTTP/1.0 200 OK',
        201 => 'HTTP/1.0 201 Created',
        202 => 'HTTP/1.0 202 Accepted',
        // Client error 4xx
        400 => 'HTTP/1.0 400 Bad Request',
        401 => 'HTTP/1.0 401 Unauthorized',
        404 => 'HTTP/1.0 404 Not Found',
        405 => 'HTTP/1.0 405 Method Not Allowed',
        // Server error 5xx
        500 => 'HTTP/1.0 500 Internal Server Error',
        501 => 'HTTP/1.0 501 Not Implemented'
    );

    /**
     * Additional data about exception
     * @var array
     */
    var $data = array();

    function __construct($message, $status = 500, $data = array()) {
        parent::__construct($message);
        $this->status = $status;
        $this->data = $data;
        xContext::$log->log(array("Thrown exception ({$this->status}): {$message}", $this->getTraceAsString()), $this, xLogger::FATAL);
    }

    function __toString() {
        return parent::__toString() . "\n\nException data: " . print_r($this->data, true);
    }

}

?>