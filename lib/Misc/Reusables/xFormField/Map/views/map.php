<?php

class AllMapMapView extends xView {

    function init() {
        // Computes Google Maps API key according the current domain
        preg_match('/\w*\.\w*$/', $_SERVER['SERVER_NAME'], $m);
        $domain = str_replace('.', '_', @$m[0]);
        $google_maps_key = @xContext::$config->site->apikeys->googlemaps->$domain;
        if (!$google_maps_key) throw new xException('Could not find google maps api key in config', 500);
        // Adds js meta
        $this->add_meta(array(
            'js' => array(
                "http://maps.google.com/maps?file=api&amp;v=2&amp;key={$google_maps_key}",
                xUtil::url('a/js/openlayers/lib/OpenLayers.js')
            )
        ));
        // Sets default values
        if (!@$this->data['width']) $this->data['width'] = '600';
        if (!@$this->data['height']) $this->data['height'] = '400';
        if (!@$this->data['']) $this->data[''] = '';
        if (!@$this->data['']) $this->data[''] = '';
    }
}

?>
