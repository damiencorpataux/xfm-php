<?php

class xForm {

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
     * An array of xFormField
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
     * HTML Form template (sprintf format)
     * @var string
     */
    var $template_form = '<form action="%2$s" method="%3$s"><table>%1$s</table></table></form>';

    /**
     * HTML Form row template (sprintf format)
     * @var string
     */
    var $template_row = '<tr><th>%s</th><td>%s %s</td></tr>';

    /**
     * HTML Form mandatory template (sprintf format)
     * @var string
     */
    //var $template_mandatory = '*';


    function __construct() {
        $this->form_options = xUtil::array_merge($this->form_options, $this->form_options());
        $this->fields_options = xUtil::array_merge($this->fields_options, $this->fields_options());
        $this->create_fields();
    }

    function fields_options() {
        return array();
    }

    function form_options() {
        return array(
            'action' => '',
            'method' => 'post',
        );
    }

    function create_fields() {
        // Setups the messages from model validation, or form controller $invalids ?
        // Setups xFormFields
        foreach ($this->fields_options as $field => $options) {
            $class = "xFormField{$options['type']}";
            $options['name'] = $field;
            if (@$_REQUEST[$options['name']]) $options['value'] = $_REQUEST[$options['name']];
            $this->fields[$field] = new $class($options);
        }
    }

    function add_field($options) {
        $this->fields[] = new xFormField($options);
    }

    function render() {
        $s = '';
        foreach ($this->fields as $field) {
            $s .= vsprintf($this->template_row, array(
                $field->render_label(),
                $field->render_field(),
                $field->render_message()
            ));
        }
        $options = array_merge(array('content' => $s), $this->form_options);
        return vsprintf($this->template_form, $options);
    }

    /**
     * Creates a xValidatorStore from fields 'validator' option.
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

    function validate() {
        $messages = $this->validator()->invalids();
        foreach ($messages as $field => $message) {
            $fieldname = @$this->fields_options[$field]['label'] ? $this->fields_options[$field]['label'] : $field;
            $this->fields[$field]->options['message'] = ucfirst($fieldname).' '.$message;
        }
        return $messages;
    }
}

///////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////

class xFormFieldText extends xFormField {
    var $template_label = '<label for="%1$s">%2$s</label>';
    var $template_field = '<input type="%3$s" name="%1$s" id="%1$s" value="%4$s" %5$s/>';
    var $template_message = '<div class="%7$s">%8$s</div>';
    function options() {
        return array(
            'type' => "text"
        );
    }
}

class xFormFieldPassword extends xFormField {
    var $template_field = '<input type="%3$s" name="%1$s" id="%1$s" value="%4$s" %5$s/>';
    function options() {
        return array(
            'type' => "password"
        );
    }
    function init() {
        //$this->options['value'] = null;
    }
}

class xFormFieldCheckbox extends xFormField {
    var $template_field = '<input type="%3$s" name="%1$s" id="%1$s" %5$s/>';
    function options() {
        return array(
            'type' => 'checkbox',
            'selected' => 'checked="checked"'
        );
    }
    function init() {
        if (@!$_REQUEST[@$this->options['name']]) $this->options['selected'] = null;
    }
}

class xFormFieldSubmit extends xFormField {
    var $template_field = '<input type="%3$s" class="button" name="%1$s" id="%1$s" value="%4$s" %5$s/>';
    function options() {
        return array(
            'type' => "submit"
        );
    }
}

class xFormFieldCaptcha extends xFormField {
    var $template_field = 'TODO: Captcha';
    function options() {
        return array(
            'validation' => "captcha" // I think we shall validate it here,
                                      // not in a validator (for KISS sake)
        );
    }
}

class xFormField {

    var $template_label = '<label for="%1$s">%2$s</label>';
    var $template_field;
    var $template_message = '<div class="%7$s">%8$s</div>';

    var $options = array(
        'name' => '',
        'label' => '',
        'type' => '',
        'value' => '',
        'selected' => '',
        'mandatory' => '',
        'state' => '',
        'message' => ''
    );

    function __construct($options = array()) {
        $this->options = xUtil::array_merge($this->options, $this->options(), $options);
        $this->init();
    }

    function init() {}

    static function create($options) {

    }

    function render_label() {
        return vsprintf($this->template_label, $this->options);
    }

    function render_field() {
        return vsprintf($this->template_field, $this->options);
    }

    function render_message() {
        return vsprintf($this->template_message, $this->options);
    }

    function render() {
        return $this->render_label().$this->render_field().$this->render_message();
    }
}