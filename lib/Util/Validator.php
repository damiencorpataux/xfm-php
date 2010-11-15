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
 * @package xFreemwork
**/
class xValidatorStore {

    var $validators = array();

    /**
     * Parameters (values) to validate
     * @var array
     */
    var $params = array();

    /**
     * Instanciate a new xValidatorStore,
     * creating xValidator instances from the given $options
     * @param array An array containing validators ids => options:
     * <code>
     * array(
     *     'name' => array(
     *         'mandatory',
     *         'string' => array(2, 50)
     *     ),
     *     'birthyear' => array(
     *         'integer' => array(1900, 2012)
     *     )
     * )
     * </code>
     */
    function __construct($options, $params = array()) {
        // Stores params (values)
        $this->params = $params;
        // Creates validators
        foreach ($options as $field => $validators) {
            foreach ($validators as $validator => $options) {
                // Validators without options can be passed as a simple key
                if (is_int($validator)) {
                    $validator = $options;
                    $options = array();
                }
                // Creates and stores validator instance
                $this->validators[$field][$validator] = xValidator::create($validator, $options);
            }
        }
    }

    /**
     * Returns an array containing field => message for each invalid field.
     * @param array Array of fields names to validate against.
     *              If specified, only the given $fields will be validated,
     *              otherwise every field will be validated.
     * @return array
     */
    function invalids($fields = array()) {
        // Defines what fields are to be validated
        $v = $this->validators;
        if ($fields) $v = xUtil::filter_keys($this->validators, $fields);
        // Validates fields
        $messages = array();
        foreach ($v as $field => $validators) {
            foreach ($validators as $validator) {
                $value = @$this->params[$field];
                // Validates the field if not already invalided, and saves message if applicable
                if (@!$messages[$field] && $message = $validator->invalid($value)) {
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
    var $message = array();

    /**
     * Validation options (e.g. string min/max length)
     * @var array
     */
    var $options = array();

    function __construct($options = array()) {
        // We have to put messages declaration in a method
        // in order to be able to use the _() function for i18n.
        $this->options = array_merge($this->options, $options);
        // TODO:
        // let an $options['message'] override the default message.
    }

    /**
     * Returns an instance of the given $validator
     * initialized with the given optional $options.
     * @param string The validator name
     * @param array Options for the validator instance
     * @return xValidator
     */
    static function create($validator, $options = array()) {
        // Creates validators instances
        $validator_class = "xValidator{$validator}";
        return new $validator_class($options);
    }

    /**
     * Returns a user message if the valid is invalid,
     * or returns false if the value is valid.
     * @param mixed The value to validate against.
     * @return string|bool
     */
    abstract function invalid($value);

    function valid() { return (bool)$this->invalid(); }

    /**
     * Returns the validator message.
     * Used to initialize the validator message
     * and must be implemented in child classes.
     * @note Validator message have to be defined here
     * in order to be able to use the _() function.
     * @return array
     */
    abstract function message();
}

class xValidatorModel extends xValidator {

    var $options = array(
        'name' => null, // Name of the model to validate against
        'field' => null // Name of the model field to validate against
    );

    function message() {}

    function invalid($value) {
        $name = $this->options['name'];
        $field = $this->options['field'];
        $model = xModel::load($name, array($field=>$value));
        $message = array_shift($model->invalids($field));
        if ($message) return $message;
        else return false;
    }
}

abstract class xValidatorRegexp extends xValidator {

    var $regexp;

    function invalid($value) {
        if (!preg_match($this->regexp, $value) > 0) {
            return $this->message();
        } else {
            return false;
        }
    }
}

//

class xValidatorMandatory extends xValidator {

    function message() {
        return _('is mandatory');
    }

    function invalid($value) {
        if ($value == '') return $this->message('mandatory');
        else return false;
    }
}

class xValidatorEmail extends xValidatorRegexp {
    var $regexp = '/^[^\s]+?@[^\s]+?\.[\w]{2,5}$/';
    function message() {
        return _('invalid');
    }
}

class xValidatorMinlength extends xValidator {
    var $options = array(
        'length' => 0
    );
    function message() {
        return _(sprintf("too short (minimum %d characters)", $this->options['length']));
    }
    function invalid($value) {
        $o = $this->options;
        if (isset($o['length']) && strlen($value) < $o['length'])
            return $this->message();
        else return false;
    }
}

class xValidatorMaxlength extends xValidator {
    var $options = array(
        'length' => null
    );
    function message() {
        return _(sprintf("too long (maximum %d characters)", $this->options['length']));
    }
    function invalid($value) {
        $o = $this->options;
        if ($o['length'] && strlen($value) > $o['length'])
            return $this->message('length');
        else return false;
    }
}

class xValidatorInteger extends xValidatorRegexp {
    var $regexp = '/^[0-9]+$/';
    function message() {
        return  _('must be a number');
    }
}

class xValidatorMinvalue extends xValidator {
    var $options = array(
        'value' => null,
    );
    function message() {
        return _(sprintf("too small (minimum %s)", $this->options['value']));
    }
    function invalid($value) {
        $o = $this->options;
        if (isset($o['value']) && (int)$value < $o['value'])
            return $this->message();
        else return false;
    }
}

class xValidatorMaxvalue extends xValidator {
    var $options = array(
        'value' => null,
    );
    function message() {
        return _(sprintf("too big (maximum %s)", $this->options['value']));
    }
    function invalid($value) {
        $o = $this->options;
        if (isset($o['value']) && (int)$value > $o['value'])
            return $this->message();
        else return false;
    }
}

class xValidatorConfirm extends xValidator {
    var $options = array(
        'match' => null,
        'label' => null
    );
    function message() {
        return sprintf(_('must match %s field'), $this->options['label']);
    }
    function invalid($value) {
        $o = $this->options;
        if ($value != @$_REQUEST[$o['match']])
            return $this->message();
        else return false;
    }
}

class xValidatorChecked extends xValidator {
    function message() {
        return _('must be checked');
    }
    function invalid($value) {
        if (strlen($value) <= 0) return $this->message();
        else return false;
    }
}

class xValidatorUnique extends xValidator {
    var $options = array(
        'model' => null,
        'field' => null
    );
    function message() {
        return _('is already taken');
    }
    function invalid($value) {
        $model = $this->options['model'];
        $field = $this->options['field'];
        $exists = (bool)xModel::load($model, array($field=>$value))->count();
        if ($exists) return $this->message();
        else return false;
    }
}