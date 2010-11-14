<?php

class xFormValidator {

    var $validators = array();

    function __construct($options) {
        //$this->messages = $this->messages();
        foreach ($options as $field => $validators) {
            foreach ($validators as $validator => $options) {
                // Validators without options can be passed as a simple key
                if (is_int($validator)) {
                    $validator = $options;
                    $options = array();
                }
                // Creates validators instances
                $validator_class = "xValidator{$validator}";
                $this->validators[$field][$validator] = new $validator_class($options);
            }
        }
    }

    function invalids() {
        $messages = array();
        foreach ($this->validators as $field => $validators) {
            foreach ($validators as $validator => $options) {
                // Validators without options can be passed as a simple key
                if (is_int($validator)) {
                    $validator = $options;
                    $options = array();
                }
                // Validates the field if not already invalided, and saves message if applicable
                $value = @$_REQUEST[$field];
                if (@!$messages[$field] && $message = $this->validators[$field][$validator]->invalid($value)) {
                    $messages[$field] = $message;
                }
            }
        }
        return $messages;
    }
}

///////////////////////////////////////////////////////////////////////////////

abstract class xValidator {

    /**
     * Validator messages array.
     * Populated by the constructor from the messages().
     * @var array
     */
    var $messages = array();

    /**
     * Validation options (e.g. string min/max length)
     * @var array
     */
    var $options = array();

    function __construct($options) {
        // We have to put messages declaration in a method
        // in order to be able to use the _() function for i18n.
        $this->options = array_merge($this->options, $options);
        // Merges options messages with predefined messages
        $options_messages = @$this->options['messages'] ? $this->options['messages'] : array();
        $this->messages = array_merge($this->messages(), $options_messages);
    }

    abstract function invalid($value);

    function valid() { return (bool)$this->invalid(); }

    abstract function messages();

    function message($type = 'default') {
        return @$this->messages[$type] ? $this->messages[$type] : _('invalid');
    }
}

abstract class xValidatorRegexp extends xValidator {

    var $regexp;

    function invalid($value) {
        if (!preg_match($this->regexp, $value) > 0) {
            return $this->message();
        }
    }
}

class xValidatorMandatory extends xValidator {

    function messages() {
        return array(
            'mandatory' => _('is mandatory'),
        );
    }

    function invalid($value) {
        if ($value == '') return $this->message('mandatory');
        else return false;
    }
}

class xValidatorEmail extends xValidatorRegexp {

    var $regexp = '/^[^\s]+?@[^\s]+?\.[\w]{2,5}$/';

    function messages() {
        return array(
            'default' => _('invalid')
        );
    }
}

class xValidatorString extends xValidator {

    var $options = array(
        'min' => 0,
        'max' => null
    );

    function messages() {
        return array(
            'min' => _(sprintf("too short (minimum %d characters)", $this->options['min'])),
            'max' => _(sprintf("too long (maximum %d characters)", $this->options['max'])),
        );
    }

    function invalid($value) {
        $o = $this->options;
        if (isset($o['min']) && strlen($value) < $o['min'])
            return $this->message('min');
        if (isset($o['max']) && strlen($value) > $o['max'])
            return $this->message('max');
        else return false;
    }
}

class xValidatorInteger extends xValidator {

    var $options = array(
        'min' => null,
        'max' => null
    );

    function messages() {
        return array(
            'numeric' => _('must be a number'),
            'min' => _(sprintf("too big (minimum %s)", $this->options['min'])),
            'max' => _(sprintf("too small (maximum %s)", $this->options['max'])),
        );
    }

    function invalid($value) {
        $o = $this->options;
        if (!preg_match('/[0-9]*/', $value) > 0)
            return $this->message('numeric');
        if (isset($o['min']) && (int)$value > $o['min'])
            return $this->message('min');
        if (isset($o['max']) && (int)$value > $o['max'])
            return $this->message('max');
        else return false;
    }
}

class xValidatorConfirm extends xValidator {

    var $options = array(
        'match' => null,
        'label' => null
    );

    function messages() {
        return array(
            'match' => sprintf(_('must match %s field'), $this->options['label']),
        );
    }

    function invalid($value) {
        $o = $this->options;
        if ($value != @$_REQUEST[$o['match']])
            return $this->message('match');
        else return false;
    }
}

class xValidatorChecked extends xValidator {

    function messages() {
        return array(
            'checked' => _('must be checked'),
        );
    }

    function invalid($value) {
        if (!$value) return $this->message('checked');
        else return false;
    }
}