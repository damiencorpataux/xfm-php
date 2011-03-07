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
 * Utility class for generating form html code.
 * @package xHelper
**/
class xFormHelper {

    /**
     * Form fields configuration object
     * @todo Example config array for each input type
     * @var Object
     */
    var $fields;

    /**
     * Templates used for generating fields
     * @todo Implement sprintf templates for fields, labels, and messages
     * @var array
     */
    var $templates = array(
        'text' => 'todo',
        'password' => 'todo',
        'checkbox' => 'todo',
        'radio' => 'todo',
        'select' => 'todo',
        'option' => 'todo'
    );

    var $invalid_classname = 'invalid';


    function __construct() {
        $this->fields = new stdClass();
    }

    function __set($property, $value) {
        // Only assign the given property if it is existing in this object,
        // except for the 'fields' propery
        if (in_array($property, array_keys(get_object_vars($this)))
            && $property !== 'fields') {
            $this->$property = $value;
        } else {
            $this->fields->$property = $value;
        }
    }

    /**
     * Return a field input html code.
     * @param string The id of the field, as defined in field configuration.
     * @return string The field html code.
     */
    function field($id) {
        $def = $this->fields->$id;
        $type = $def['type'];
        $id = @$def['id'] ? $def['id'] : $id;
        $name = @$def['name'] ? $def['name'] : $id;
        $maxlength = @$def['maxlength'];
        $class = @$def['class'];
        $class .= @$def['message']['display'] ? " {$this->invalid_classname}" : '';
        // Text and password
        if (in_array($type, array('text', 'password', 'hidden'))) {
            if ($type=='password') $value = '';
            else $value = @$def['value'] ? $def['value'] : $_REQUEST[$id];
            $maxlength = $maxlength ? " maxlength=\"$maxlength\"" : '';
            return "<input type=\"{$type}\" id=\"{$id}\" name=\"{$name}\" value=\"{$value}\"{$maxlength} class=\"{$class}\"/>";
        }
        // Textarea
        elseif ($type == 'textarea') {
            $value = @$def['value'];
            $text = xUtil::arrize($def['text']);
            $column = $def['column'] > 0 ? $def['column'] : 5 ;
            $row = $def['row'] > 0 ? $def['row'] : 5 ;
            return  "<textarea name=\"{$name}\" cols=\"{$column}\" rows=\"{$row}\" id=\"{$id}\" class=\"{$class}\">{$value}</textarea>";
        }
        // Radio & checkboxes
        elseif (in_array($type, array('radio', 'checkbox'))) {
            $values = xUtil::arrize($def['values']);
            $text = xUtil::arrize($def['text']);
            $checked = @$def['checked'] ? $def['checked'] : $_REQUEST[$id];
            $s = '';
            for ($i=0; $i<count($values); $i++) {
                $value = $values[$i];
                $id = "{$name}_{$value}";
                $checked_attribute = $checked==$value ? ' checked="checked"' : '';
                $s .= "<input type=\"{$type}\" name=\"{$name}\" id=\"{$id}\"value=\"{$value}\"{$checked_attribute} class=\"{$class}\"/>";
                $s .= $text[$i] ? "&nbsp;<label for=\"{$id}\">$text[$i]</label>" : '';
                $s .= $i < count($values)-1 ? "<br/>\n" : '';
            }
            return $s;
        // Selects (comboboxes)
        } elseif ($type == 'select') {
            $values = @$def['values'];
            $text = xUtil::arrize($def['text']);
            $checked = @$def['checked'] ? $def['checked'] : $_REQUEST[$id];
            $s = "<select id=\"{$id}\" name=\"{$name}\" class=\"{$class}\">";
            for ($i=0; $i<count($values); $i++) {
                $selected_attribute = $checked==$values[$i] ? ' selected="selected"' : '';
                $s .= "<option value=\"{$values[$i]}\"{$selected_attribute}>{$text[$i]}</option>";
            }
            $s .= "</select>";
            return $s;
        } else {
            throw new xException("Unsupported field type: {$type}", 500);
        }
    }

    /**
     * Return a field label html code.
     * @param string The id of the field, as defined in field configuration.
     * @return string The field label html code.
     */
    function label($id) {
        $def = $this->fields->$id;
        $id = @$def['id'] ? $def['id'] : $id;
        $label = @$def['label'];
        if (in_array($def['type'], array('radio', 'checkbox'))) return "<label>{$label}</label>";
        else return "<label for=\"{$id}\">{$label}</label>";
    }

    /**
     * Return a field message html code.
     * @param string The id of the field, as defined in field configuration.
     * @return string The field message html code.
     */
    function message($id) {
        $def = $this->fields->$id;
        $id = @$def['id'] ? $def['id'] : $id;
        $message = @$def['message'];
        $text = $message['text'];
        $class = @$message['class'] ? $message['class'] : 'warning';
        $style_attribute = $message['display'] ? '' : "style=\"display:none\"";
        return "<span id=\"{$id}_{$class}\" class=\"{$class} tip\"{$style_attribute}>{$text}</span>";
    }
}

?>
