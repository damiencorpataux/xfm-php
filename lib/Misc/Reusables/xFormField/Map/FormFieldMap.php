<?php

/**
 * This class uses OpenLayers
 * @see http://www.openlayers.org/
 */
class xFormFieldMap extends xFormFieldText {
    
    function add_meta() {
        $this->meta = array(
            'js' => array('http://openlayers.org/api/OpenLayers.js')
        );
    }
    
    protected function js() {
        return "
            window.addEvent('load', function() {
                map = new OpenLayers.Map('{$this->options['name']}');
            });
        ";
    }

    function init() {
        $this->add_meta();
    }

    function render_field() {
        return 
            "<div id=\"{$this->options['name']}\" style=\"width:100px;height:100px\"></div>".
            '<script>'.$this->js().'</script>';
    }

}