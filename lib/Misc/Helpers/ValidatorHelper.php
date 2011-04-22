<?php

/**
 * Data validation helper
 * @package xHelper
**/
class xValidatorHelper {

    function email($value) {
        return preg_match('/^[^\s]+?@[^\s]+?\.[\w]{2,5}$/', $value) > 0;
    }

    function phone($value) {
        $value = str_replace(' ', '', $value);
        return preg_match('/^(0[89]{1}|^0[^89]{1})\d{8}$/', $value) > 0;
    }

    function url($value) {
        return preg_match('/^([^\s]+\.)+\w{2,4}(\/\S*)?$/', $value) > 0;
    }

    function integer($value) {
        return preg_match('/^[0-9]+$/', $value) > 0;
    }

    function length($value, $min_length = null, $max_length = null) {
        return (is_null($min_length) || strlen($value)>=$min_length)
            && (is_null($max_length) || strlen($value)<=$max_length);
    }

    function within($value, $allowed_values = array()) {
        return in_array($value, $allowed_values);
    }
}

?>