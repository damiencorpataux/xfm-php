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
 * This component is to be used for validating multiple fields
 * @package xValidator
**/
class xValidatorStore {

    /**
     * Array of created xValidator instances.
     * @var array
     */
    var $validators = array();

    /**
     * Parameters (values) to validate
     * @var array
     */
    var $params = array();

    /**
     * Instanciate a new xValidatorStore,
     * creating xValidator instances from the given $options
     * @param array An array containing validators fieldname => options:
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
     * @param array An array of fieldname => value to validate:
     * <code>
     * array(
     *     'name' => 'King Luis',
     *     'birthyear' => '08/04/1901'
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
     * Returns the given validator.
     * @param string Field name.
     * @param string Validator name.
     * @return xValidator xValidator instance corresponding
     *     to the given field and validator names,
     *     null if not found.
     */ 
    function get($field_name = null, $validator_name = null) {
        if (!$field_name) return $this->validators;
        elseif (!$validator_name) return @$this->validators[$field_name];
        else return @$this->validators[$field_name][$validator_name];
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

/**
 * Generic validator.
 * @package xValidator
**/
abstract class xValidator {

    /**
     * Validation options (e.g. string min/max length)
     * @var array
     */
    var $options = array();

    function __construct($options = array()) {
        // We have to put messages declaration in a method
        // in order to be able to use the _() function for i18n.
        $this->options = array_merge($this->options, $options);
        $this->init();
        // TODO:
        // let an $options['message'] override the default message.
    }

    function init() {}

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

/**
 * This validator is made for re-using models validation logic
 * @package xValidator
**/
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

/**
 * Generic regular expression based validator
 * @package xValidator
**/
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

/**
 * Checks whether a value is present or not
 * @package xValidator
**/
class xValidatorMandatory extends xValidator {

    function message() {
        return _('is mandatory');
    }

    function invalid($value) {
        if ($value == '') return $this->message();
        else return false;
    }
}

/**
 * Checks whether an email pattern is correct
 * @package xValidator
**/
class xValidatorEmail extends xValidatorRegexp {
    var $regexp = '/^[^\s]+?@[^\s]+?\.[\w]{2,5}$/';
    function message() {
        return _('invalid');
    }
}

/**
 * Checks whether a url pattern is correct
 * @package xValidator
**/
class xValidatorUrl extends xValidatorRegexp {
    var $regexp = '/^([^\s]+\.)+\w{2,4}(\/\S*)?$/';
    function message() {
        return _('invalid');
    }
}

/**
 * Checks the minimum length of a string
 * @package xValidator
**/
class xValidatorMinlength extends xValidator {
    var $options = array(
        'length' => 0
    );
    function message() {
        return sprintf(_("too short (minimum %d characters)"), $this->options['length']);
    }
    function invalid($value) {
        $o = $this->options;
        if (isset($o['length']) && strlen($value) < $o['length'])
            return $this->message();
        else return false;
    }
}

/**
 * Checks the maximum length of a string
 * @package xValidator
**/
class xValidatorMaxlength extends xValidator {
    var $options = array(
        'length' => null
    );
    function message() {
        return sprintf(_("too long (maximum %d characters)"), $this->options['length']);
    }
    function invalid($value) {
        $o = $this->options;
        if ($o['length'] && strlen($value) > $o['length'])
            return $this->message();
        else return false;
    }
}

/**
 * Checks whether a value is an integer
 * @package xValidator
**/
class xValidatorInteger extends xValidatorRegexp {
    var $regexp = '/^[0-9]+$/';
    function message() {
        return  _('must be a number');
    }
}

/**
 * Checks whether a value is a valid date
 * @package xValidator
**/
class xValidatorDate extends xValidator {
    var $options = array(
        // According PHP strftime() function format
        // http://www.php.net/manual/fr/function.strftime.php
        'format' => null
    );
    function init() {
        // If not defined, uses the configuration format,
        // or a hardcoded default format
        if (!@$this->options['format']) {
            $this->options['format'] = xContext::$config->i18n->format->date ?
                xContext::$config->i18n->format->date :
                '%d-%m-%Y';
        }
    }
    function message() {
        return _("invalid");
    }
    function invalid($value) {
        $o = $this->options;
        $date_info = strptime($value, $this->options['format']);
        if (!$this->valid_date($date_info)) return $this->message();
        return false;
    }
    /**
     * Tells whether the given time is valid
     * @see http://www.php.net/manual/fr/function.strptime.php
     * @param array PHP strptime() return value
     * @return bool
     */
    function valid_date($date_info) {
        $day = isset($date_info['tm_mday']) ? $date_info['tm_mday'] : null;
        $month = isset($date_info['tm_mon']) ? $date_info['tm_mon']+1 : null;
        $year = isset($date_info['tm_year']) ? $date_info['tm_year']+1900 : null;
        $unparsed = isset($date_info['unparsed']) ? $date_info['unparsed'] : null;
        return !$unparsed && checkdate($day, $month, $year);
    }
}

/**
 * Checks whether a value is a valid time
 * @package xValidator
**/
class xValidatorTime extends xValidator {
    var $options = array(
        // According PHP strftime() function format
        // http://www.php.net/manual/fr/function.strftime.php
        'format' => null
    );
    function init() {
        // If not defined, uses the configuration format,
        // or a hardcoded default format
        if (!@$this->options['format']) {
            $this->options['format'] = @xContext::$config->i18n->format->time ?
                xContext::$config->i18n->format->time :
                '%H:%M';
        }
    }
    function message() {
        return _("invalid");
    }
    function invalid($value) {
        $o = $this->options;
        $date_info = strptime($value, $this->options['format']);
        if (!$this->valid_time($date_info)) return $this->message();
        return false;
    }
    /**
     * Tells whether the given time is valid
     * @see http://www.php.net/manual/fr/function.strptime.php
     * @param array PHP strptime() return value
     * @return bool
     */
    function valid_time($date_info) {
        $hours = isset($date_info['tm_hour']) ? $date_info['tm_hour'] : null;
        $minutes = isset($date_info['tm_min']) ? $date_info['tm_min'] : 0;
        $seconds = isset($date_info['tm_sec']) ? $date_info['tm_sec'] : 0;
        $unparsed = isset($date_info['unparsed']) ? $date_info['unparsed'] : null;
        return !$unparsed && !is_null($hours);
    }
}

/**
 * Checks whether a value is a valid datetime
 * @package xValidator
**/
class xValidatorDatetime extends xValidator {
    var $options = array(
        // According PHP strftime() function format
        // http://www.php.net/manual/fr/function.strftime.php
        'format' => null
    );
    function init() {
        // If not defined, uses the configuration format,
        // or a hardcoded default format
        if (!@$this->options['format']) {
            $this->options['format'] = @xContext::$config->i18n->format->date && @xContext::$config->i18n->format->time ?
                xContext::$config->i18n->format->date.' '.xContext::$config->i18n->format->time :
                '%d-%m-%Y %H:%M';
        }
    }
    function message() {
        return _("invalid");
    }
    function invalid($value) {
        $o = $this->options;
        $date_info = strptime($value, $this->options['format']);
        // Validates date & time using related validators
        $valid_date = xValidator::create('date')->valid_date($date_info);
        $valid_time = xValidator::create('time')->valid_time($date_info);
        if (!$valid_date || !$valid_time) return $this->message();
        return false;
    }
}

/**
 * Checks whether an integer value is equal or greater than a given value
 * @package xValidator
**/
class xValidatorMinvalue extends xValidator {
    var $options = array(
        'value' => null,
    );
    function message() {
        return sprintf(_("too small (minimum %s)"), $this->options['value']);
    }
    function invalid($value) {
        $o = $this->options;
        if (isset($o['value']) && (int)$value < $o['value'])
            return $this->message();
        else return false;
    }
}

/**
 * Checks whether an integer value is equal or less than a given value
 * @package xValidator
**/
class xValidatorMaxvalue extends xValidator {
    var $options = array(
        'value' => null,
    );
    function message() {
        return sprintf(_("too big (maximum %s)"), $this->options['value']);
    }
    function invalid($value) {
        $o = $this->options;
        if (isset($o['value']) && (int)$value > $o['value'])
            return $this->message();
        else return false;
    }
}

/**
 * Checks whether two fields values are identical
 * @package xValidator
**/
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

/**
 * Checks whether a checkbox is checked
 * @package xValidator
**/
class xValidatorChecked extends xValidator {
    function message() {
        return _('must be checked');
    }
    function invalid($value) {
        if (strlen($value) <= 0) return $this->message();
        else return false;
    }
}

/**
 * Checks whether a value does not already exists in a table row
 * @package xValidator
**/
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