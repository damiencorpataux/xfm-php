<?php
/*
 * (c) 2012 Damien Corpataux
 *
 * LICENSE
 * This library is licensed under the GNU GPL v3.0 license,
 * accessible at http://www.gnu.org/licenses/gpl-3.0.html
 *
**/

/**
 * This component is to be used for transforming multiple fields.
 * @package xTransformer
 **/
class xTransformerStore {

    /**
     * Array of created xTransformer instances.
     * @var array
     */
    var $transformers = array();

    /**
     * Parameters (values) to transform
     * @var array
     */
    var $params = array();

    /**
     * Instanciate a new xTransformerStore,
     * creating xTransformer instances from the given $options
     * @param array An array containing transformers fieldname => options:
     * <code>
     * array(
     * 'text' => array(
     * 'striphtml',
     * 'allow' => array('table', 'tr', 'th', 'td')
     * ),
     * 'name' => 'uppercasewords'
     * )
     * </code>
     * @param array An array of fieldname => value to validate:
     * <code>
     * array(
     * 'text' => '<script>console.log("this is malicious")</script>',
     * 'name' => 'King l'autre luis',
     * 'birthyear' => '08/04/1901'
     * )
     * </code>
     */
    function __construct($options, $params = array()) {
        // Stores params (values)
        $this->params = $params;
        // Creates validators
        foreach ($options as $field => $validators) {
            $validators = xUtil::arrize($validators);
            foreach ($validators as $validator => $options) {
                // Validators without options can be passed as a simple key
                if (is_int($validator)) {
                    $validator = $options;
                    $options = array();
                }
                // Creates and stores validator instance
                $this->validators[$field][$validator] = xValidator::create($validator, $options);
            }
        }
    }

    /**
     * Returns the given validator.
     * @param string Field name.
     * @param string Validator name.
     * @return xValidator xValidator instance corresponding
     * to the given field and validator names,
     * null if not found.
     */
    function get($field_name = null, $validator_name = null) {
        if (!$field_name) return $this->validators;
        elseif (!$validator_name) return @$this->validators[$field_name];
        else return @$this->validators[$field_name][$validator_name];
    }

    /**
     * Returns an array containing field => message for each invalid field.
     * @param array Array of fields names to validate against.
     * If specified, only the given $fields will be validated,
     * otherwise every field will be validated.
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
                // Validates the field if not already invalided, and saves message if applicable
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
 * Generic transformer.
 * @package xTransformer
 **/
abstract class xTransformer {

    /**
     * Transformation options (e.g. string min/max length)
     * @var array
     */
    var $options = array();

    function __construct($options = array()) {
        // We have to put messages declaration in a method
        // in order to be able to use the _() function for i18n.
        $this->options = array_merge($this->options, $options);
        $this->init();
        // TODO:
        // let an $options['message'] override the default message.
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
        // Creates validators instances
        $validator_class = "xValidator{$validator}";
        return new $validator_class($options);
    }

    /**
     * Returns the transformed $value,
     * or throws an exception on transformation failure.
     * @param mixed The value to validate against.
     * @return mixed The transformed value.
     */
    abstract function transform($value);
}

/**
 * Generic regular expression based validator
 * @package xValidator
 **/
abstract class xTransformerRegexp extends xTransformer {

    var $regexp_search;
    var $regexp_replace;

    function transform($value) {
        $result = preg_replace($this->regexp_search, $this->regexp_replace, $value);
        if ($replace === null) throw new xException('Regexp transformation failed', 500, array(
            'regexp_search' => $this->regexp_search,
            'regexp_replace' => $this->regexp_replace,
            'value' => $value
        ));
        return $result;
    }
}