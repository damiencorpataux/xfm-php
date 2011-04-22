<?php

abstract class xGeocoder {

    var $url;

    var $options = array();

    /**
     * Constructor.
     * @param array An array of geocoding options
     */
    function __construct($options = array()) {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Returns an array containing the coordinates
     * @return array
     */
    function get($params) {
        throw new Exception('Not implemented');
    }

    /**
     * Returns an array containing the address
     * @return array
     */
    function reverse() {
        throw new Exception('Not implemented');
    }


    function call($params) {
        $url = $this->url.'?'.http_build_query($params);
        // Service call
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}


abstract class xGeocoderGeonames extends xGeocoder {
    function get() {
        $params = array_merge($this->options, array('type'=>'json'));
        return json_decode(parent::call($params));
    }
}

/**
 * Geonames fulltext search
 * @see http://www.geonames.org/export/geonames-search.html
 */
class xGeocoderGeonamesFulltext extends xGeocoderGeonames {
    var $url = 'http://ws.geonames.org/searchJSON';
    var $options = array(
        'q' => null,
        'country' => null,
        'maxRows' => null
    );
}

/**
 * Geonames fulltext search
 * @see http://www.geonames.org/export/geonames-search.html
 */
class xGeocoderGeonamesZipSearch extends xGeocoderGeonames {
    var $url = 'http://ws.geonames.org/postalCodeSearchJSON';
    var $options = array(
        'postalcode' => null,
        'country' => null,
        'maxRows' => null
    );
}


// TEST
/*
$g = new xGeocoderGeonamesFulltext(array('q'=>'lausanne', 'country'=>'CH'));
var_dump($g->get());
$g = new xGeocoderGeonamesZip(array('postalcode'=>'1000', 'country'=>'CH'));
var_dump($g->get());
*/