<?php

/**
 * This class uses Mootools and Datepicker
 * @see http://mootools.net/
 * @see http://www.monkeyphysics.com/mootools/script/2/datepicker
 */
class xFormFieldDatepickerMoo extends xFormFieldText {
    
    function add_meta() {
        $path = '/a/reusables/xFormField/DatepickerMoo/public';
        $this->meta = array(
            'js' => array(
                $path.'/mootools-core-1.3-full-compat-yc.js',
                $path.'/datepicker-minified.js'
            ),
            'css' => array(
                $path.'/datepicker.css'
            )
        );
    }
    
    protected function js() {
        return "
            window.addEvent('load', function() {
                new DatePicker('#{$this->options['name']}', { timePicker: true, format: 'd.m.Y H:i', positionOffset: { x: 0, y: 5 }});
            });
        ";
    }

    function init() {
        $this->options['type'] = 'text';
        $this->add_meta();
    }

    function render_field() {
        return parent::render_field().'<script>'.$this->js().'</script>';
    }

}