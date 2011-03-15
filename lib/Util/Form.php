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
 * xFormTemplate class.
 * Manages for templates processing.
 * @package xForm
**/
class xFormTemplate {

    /**
     * Applies the given data on the given template and returns the result.
     * Templates are in the following form:
     * <code>
     * The value type is {type} and its value is {value}
     * </code>
     * Data are in the following structure:
     * <code>
     * array(
     *     'type' => 'integer',
     *     'value' => 12
     * )
     * </code>
     * @param string A template string
     * @param array An associative array containing the template data
     * @return string The processed template output
     */
    static function apply($template, $data) {
        // Creates a PHP processable string
        $template = str_replace('"', '\"', $template);
        $php = preg_replace('/\{([\w]*)\}/', '{$data[\'$1\']}', $template);
        // Processes PHP string
        eval('$result = "'.$php.'";');
        return $result;
    }
}

/**
 * xForm class.
 * Deals with form creation, validation and HTML generation.
 * @package xForm
**/
abstract class xForm {

    /**
     * An array of fields options.
     * Populated from fields_options().
     * <code>
     * array(
     *    'fieldname' => array(
     *        'name' => 'field name',
     *        'value' => 'field value(s)',
     *        'validation' => array(
     *            'validator' => array('validator', 'arguments'),
     *            'validator_without_options'
     *        )
     *    )
     * )
     * </code>
     * @see fields_options()
     * @var array
     */
    var $fields_options;

    /**
     * An array of form options.
     * Populated from form_options().
     * <code>
     * array(
     *    'action' => 'field action url',
     *    'method' => 'post or get'
     * )
     * </code>
     * @see form_options()
     * @var array
     */
    var $form_options;

    /**
     * Array containing the created xFormField instances.
     * @var array
     */
    var $fields = array();

    /**
     * Singleton for generated form validator
     * @see validator()
     * @var null|xValidatorStore
     */
    var $validator;

    /**
     * HTML Form template (xFormTemplate format)
     * @var string
     */
    var $template_form = '<form action="{action}" method="{method}"><table>{content}</table></table></form>';

    /**
     * HTML Form row template (xFormTemplate format)
     * @var string
     */
    var $template_row = '<tr><th>{label}</th><td>{field} {message}</td></tr>';

    /**
     * HTML Form row template for mandatory fields (xFormTemplate format)
     * @var string
     */
    var $template_row_mandatory = '<tr><th>{label}<span style="font-size:bold;color:red">*</span></th><td>{field} {message}</td></tr>';


    function __construct() {
        $this->form_options = xUtil::array_merge($this->form_options, $this->form_options());
        $this->fields_options = xUtil::array_merge($this->fields_options, $this->fields_options());
        $this->create_fields();
    }

    /**
     * Returns the form fields definition.
     * This method must be implemented in child classes and return
     * the form fields definition according the xFormField::options() format.
     * @return array Fields definitions.
     * 
     */
    abstract function fields_options();

    function form_options() {
        return array(
            'action' => '',
            'method' => 'post',
        );
    }

    function create_fields() {
        // Setups xFormFields
        foreach ($this->fields_options as $field => $options) {
            $options['name'] = $field;
            // Assigns posted value if none defined
            if (!isset($options['value'])) {
                $options['value'] = @$_REQUEST[$options['name']];
            }
            $this->fields[$field] = xFormField::create($options);
        }
    }

    function add_field($options) {
        $this->fields[] = new xFormField($options);
    }

    function render() {
        $s = '';
        foreach ($this->fields as $field) {
            // Determines if the field is mandatory
            $mandatory = (bool)$this->validator()->get($field->options['name'], 'mandatory');
            // Renders form contents (fields)
            $row_template = $mandatory ? $this->template_row_mandatory : $this->template_row;
            $s .= xFormTemplate::apply($row_template, array(
                'label' => $field->render_label(),
                'field' => $field->render_field(),
                'message' => $field->render_message()
            ));
        }
        // Applies and returns the processed form template
        $options = array_merge(array('content' => $s), $this->form_options);
        return xFormTemplate::apply($this->template_form, $options);
    }

    /**
     * Returns a xValidatorStore singleton from fields 'validator' option.
     * @return xValidatorStore
     */
    function validator() {
        if ($this->validator) return $this->validator;
        $options = array();
        foreach ($this->fields_options as $field => $field_options) {
            if (!@$field_options['validation']) continue;
            $options[$field] = $field_options['validation'];
        }
        return $this->validator = new xValidatorStore($options, $_REQUEST);
    }

    function invalids() {
        $messages = $this->validator()->invalids();
        foreach ($messages as $field => $message) {
            $fieldname = @$this->fields_options[$field]['label'] ? $this->fields_options[$field]['label'] : $field;
            $this->fields[$field]->options['message_current'] =
                $this->fields[$field]->options['message'] ?
                $this->fields[$field]->options['message'] :
                ucfirst($fieldname).' '.$message;
            $this->fields[$field]->options['state_current'] =
                $this->fields[$field]->options['state'] ?
                $this->fields[$field]->options['state'] :
                'invalid';
        }
        return $messages;
    }

    function valid() {
        return !$this->invalid();
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

/**
 * 
 * @package xForm
**/
class xFormFieldText extends xFormField {
    var $template_field = '<input type="{type}" name="{name}" id="{name}" value="{value}" class="{state_current}"/>';
    function options() {
        return array(
            'type' => 'text'
        );
    }
}

/**
 *
 * @package xForm
**/
class xFormFieldTextarea extends xFormField {
    var $template_field = '<textarea name="{name}" id="{name}" rows="{rows}" cols="{cols}" class="{state_current}">{value}</textarea>';
    function options() {
        return array(
            'type' => 'textarea',
            'rows' => null,
            'cols' => null
        );
    }
}

/**
 *
 * @package xForm
**/
class xFormFieldPassword extends xFormField {
    var $template_field = '<input type="{type}" name="{name}" id="{name}" value="{value} class="{state_current}""/>';
    function options() {
        return array(
            'type' => 'password',
            'persistant' => false
        );
    }
    function init() {
        // Reset password value if not 'persistant'
        if (!$this->options['persistant']) $this->options['value'] = null;
    }
}

/**
 *
 * @package xForm
**/
class xFormFieldCheckbox extends xFormField {
    var $template_field = '<input type="{type}" name="{name}" id="{name}" class="{state_current}" {selected}/>';
    var $template_selected = 'checked="checked"';
    function options() {
        return array(
            'type' => 'checkbox',
            'value' => null, // true to select the checkbox by default
            'default' => null, // true to select the checkbox by default
        );
    }
    function init() {
        // Determines whether the checkbox is selected or not
        $this->options['selected'] = isset($this->options['value']) ?
            true : @(bool)$this->options['default'];
    }
}

/**
 *
 * @package xForm
**/
class xFormFieldSelect extends xFormField {
    var $template_field = '<select name="{name}" id="{name}" value="{value}" class="{state_current}">{options}</select>';
    var $items = array();
    function options() {
        return array(
            'type' => null,
            'option_class' => 'option',
            'values' => array(), // An associative array of value => label tuples
            'default' => null // The value of the default selected item
        );
    }
    function init() {
        // Creates xFormField instances
        foreach ($this->options['values'] as $value => $label) {
            $this->items[] = xFormField::create(array(
                'type' => 'option',
                'name' => $this->options['name'],
                'label' => $label,
                'value' => $value,
                'selected' => ($value == @$this->options['value'])
            ));
        }
    }
    function render_field() {
        $html = '';
        foreach ($this->items as $item) $html .= $item->render_field();
        return xFormTemplate::apply($this->template_field, array_merge(
            $this->options,
            array('options' => $html)
        ));
    }
}
/**
 *
 * @package xForm
 * @see xFormFieldSelect
**/
class xFormFieldOption extends xFormField {
    var $template_field = '<option id="option_{name}_{value}" value="{value}" {selected}>{label}</option>';
    var $template_selected = 'selected="selected"';
    function options() {
        return array(
            'type' => 'option',
            'label' => null,
            'value' => null,
            'name' => 'parent_name'
        );
    }
    function render_label() { return '';}
    function render_message() { return ''; }
}

/**
 *
 * @package xForm
**/
class xFormFieldSelectNumeric extends xFormFieldSelect {
    function init() {
        $array_values = array_values($this->options['values']);
        $this->options['values'] = array_combine(
            array_values($this->options['values']),
            array_values($this->options['values'])
        );
        return parent::init();
    }
}

/**
 *
 * @package xForm
**/
class xFormFieldSubmit extends xFormField {
    var $template_field = '<input type="{type}" class="button" name="{name}" id="{name}" value="{value}" {selected}/>';
    function options() {
        return array(
            'type' => "submit"
        );
    }
}

///////////////////////////////////////////////////////////////////////////////

/**
 * Generic form field.
 * @package xForm
**/
abstract class xFormField {

    /**
     * HTML field label template (xFormTemplate format)
     * @var string
     */
    var $template_label = '<label for="{name}">{label}</label>';

    /**
     * HTML field input template (xFormTemplate format)
     * @var string
     */
    var $template_field;

    /**
     * HTML field message template (xFormTemplate format)
     * @var string
     */
    var $template_message = '<br/><div class="{state_current} tip">{message_current}</div>';

    /**
     * HTML string for selecting/checking for input/option
     * @var string
     */
    var $template_selected;


    /**
     * Associative array containing the default field options.
     * @var array
     */
    var $options = array(
        'name' => null,
        'label' => null,
        'type' => null,
        'value' => null,
        'selected' => null,
        'mandatory' => null,
        'state' => 'invalid',
        'state_current' => null,
        'message' => null,
        'message_current' => null
    );

    function __construct($options = array()) {
        $this->options = xUtil::array_merge($this->options, $this->options(), $options);
        $this->init();
        $this->options['selected'] = @$this->options['selected'] ? $this->template_selected : null;
    }

    /**
     * Hook for initializing form fields.
     */
    function init() {}

    /**
     * Default options for the field.
     * This method returns an associative array
     * containing the field default options.
     * @note This method is called in the constructor and merges the class
     *     options memeber with the result of this function. This array is
     *     implemented in an method in order to allow functions and variables
     *     in the array values.
     * @return array An associative array containing the field default options.
     */
    abstract function options();

    /**
     * Instanciates and return a field of the given type.
     * @return xFormField xFormField instance.
     */
    static function create($options = array()) {
        if (!@$options['type']) throw new Exception('Missing "type" in $options array argument');
        $class = "xFormField{$options['type']}";
        return new $class($options);
    }

    /**
     * Returns the HTML code for the field label.
     * @return string HTML code for the field label.
     */
    function render_label() {
        return xFormTemplate::apply($this->template_label, $this->options);
    }

    /**
     * Returns the HTML code for the field input.
     * @return string HTML code for the field input.
     */
    function render_field() {
        return xFormTemplate::apply($this->template_field, $this->options);
    }

    /**
     * Returns the HTML code for the field message.
     * @return string HTML code for the field message.
     */
    function render_message() {
        return $this->options['message_current'] ?
            xFormTemplate::apply($this->template_message, $this->options) :
            null;
    }

    /**
     * Concatenates and returns the HTML code for the field label, input and message.
     * @return string HTML code for the field.
     */
    function render() {
        return $this->render_label().$this->render_field().$this->render_message();
    }
}
