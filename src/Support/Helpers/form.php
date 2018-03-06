<?php
// ------------------------------------------------------------------------

use Igniter\Flame\Html\FormBuilder;
use Igniter\Flame\Html\HtmlBuilder;

if (!function_exists('form_open')) {
    /**
     * Form Declaration
     * Creates the opening portion of the form.
     *
     * @param    string    the URI segments of the form destination
     * @param    array    a key/value pair of attributes
     * @param    array    a key/value pair hidden data
     *
     * @return    string
     */
    function form_open($action = null, $attributes = [])
    {
        if (is_string($action)) {
            $attributes['url'] = $action;
        }
        else {
            $attributes = $action;
        }

        $handler = null;
        if (isset($attributes['handler']))
            $handler = app(FormBuilder::class)->hidden('_handler', $attributes['handler']);

        return app(FormBuilder::class)->open($attributes).$handler;
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_open_multipart')) {
    /**
     * Form Declaration - Multipart type
     * Creates the opening portion of the form, but with "multipart/form-data".
     *
     * @param    string    the URI segments of the form destination
     * @param    array    a key/value pair of attributes
     * @param    array    a key/value pair hidden data
     *
     * @return    string
     */
    function form_open_multipart($action = '', $attributes = [])
    {
        $attributes['enctype'] = 'multipart/form-data';

        return form_open($action, $attributes);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_hidden')) {
    /**
     * Hidden Input Field
     * Generates hidden fields. You can pass a simple key/value string or
     * an associative array with multiple values.
     *
     * @param    string $name Field name
     * @param    mixed $value Field value
     * @param    array $options
     *
     * @return    string
     */
    function form_hidden($name, $value = null, $options = [])
    {
        return app(FormBuilder::class)->hidden($name, $value, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_input')) {
    /**
     * Text Input Field
     *
     * @param    mixed
     * @param    string
     * @param    mixed
     *
     * @return    string
     */
    function form_input($name, $value = null, $options = [])
    {
        return app(FormBuilder::class)->text($name, $value, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_password')) {
    /**
     * Password Field
     * Identical to the input function but adds the "password" type
     *
     * @param    string
     * @param array $options
     *
     * @return string
     */
    function form_password($name, $options = [])
    {
        return app(FormBuilder::class)->password($name, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_upload')) {
    /**
     * Upload Field
     * Identical to the input function but adds the "file" type
     *
     * @param    string
     * @param array $options
     *
     * @return    string
     */
    function form_upload($name, $options = [])
    {
        return app(FormBuilder::class)->file($name, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_textarea')) {
    /**
     * Textarea field
     *
     * @param $name
     * @param    string $value
     * @param array $options
     *
     * @return string
     */
    function form_textarea($name, $value = null, $options = [])
    {
        return app(FormBuilder::class)->textarea($name, $value, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_multiselect')) {
    /**
     * Multi-select menu
     *
     * @param    string
     * @param array $list
     * @param array $selected
     * @param array $selectAttributes
     * @param array $optionAttributes
     *
     * @return    string
     */
    function form_multiselect(
        $name, $list = [], $selected = [],
        array $selectAttributes = [],
        array $optionAttributes = []
    )
    {
        return app(FormBuilder::class)->select($name, $list, $selected, $selectAttributes, $optionAttributes);
    }
}

// --------------------------------------------------------------------

if (!function_exists('form_dropdown')) {
    /**
     * Drop-down Menu
     *
     * @param $name
     * @param array $list
     * @param    mixed $selected
     * @param array $selectAttributes
     * @param array $optionAttributes
     *
     * @return string
     */
    function form_dropdown($name, $list = [], $selected = [], array $selectAttributes = [], array $optionAttributes = [])
    {
        return app(FormBuilder::class)->select($name, $list, $selected, $selectAttributes, $optionAttributes);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_select')) {
    /**
     * Drop-down Menu
     *
     * @param $name
     * @param array $list
     * @param    mixed $selected
     * @param array $selectAttributes
     * @param array $optionAttributes
     *
     * @return string
     */
    function form_select($name, $list = [], $selected = [], array $selectAttributes = [], array $optionAttributes = [])
    {
        return app(FormBuilder::class)->select($name, $list, $selected, $selectAttributes, $optionAttributes);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_checkbox')) {
    /**
     * Checkbox Field
     *
     * @param $name
     * @param int $value
     * @param null $checked
     * @param array $options
     *
     * @return    string
     */
    function form_checkbox($name, $value = 1, $checked = null, $options = [])
    {
        return app(FormBuilder::class)->checkbox($name, $value, $checked, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_radio')) {
    /**
     * Radio Button
     *
     * @param    mixed
     * @param    string
     * @param    bool
     * @param    mixed
     *
     * @return    string
     */
    function form_radio($name, $value = null, $checked = null, $options = [])
    {
        return app(FormBuilder::class)->radio($name, $value, $checked, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_submit')) {
    /**
     * Submit Button
     *
     * @param    string
     * @param array $options
     *
     * @return    string
     */
    function form_submit($value = null, $options = [])
    {
        return app(FormBuilder::class)->submit($value, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_reset')) {
    /**
     * Reset Button
     *
     * @param    string
     * @param array $options
     *
     * @return    string
     */
    function form_reset($value, $options = [])
    {
        return app(FormBuilder::class)->reset($value, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_button')) {
    /**
     * Form Button
     *
     * @param    string
     * @param array $options
     *
     * @return    string
     */
    function form_button($value, $options = [])
    {
        return app(FormBuilder::class)->button($value, $options);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_label')) {
    /**
     * Form Label Tag
     *
     * @param $name
     * @param null $value
     * @param array $options
     * @param bool $escape_html
     *
     * @return    string
     */
    function form_label($name, $value = null, $options = [], $escape_html = TRUE)
    {
        return app(FormBuilder::class)->label($name, $value, $options, $escape_html);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_fieldset')) {
    /**
     * Fieldset Tag
     * Used to produce <fieldset><legend>text</legend>.  To close fieldset
     * use form_fieldset_close()
     *
     * @param    string    The legend text
     * @param    array    Additional attributes
     *
     * @return    string
     */
    function form_fieldset($legend_text = '', $options = [])
    {
        $fieldset = '<fieldset'.app(HtmlBuilder::class)->attributes($options).">\n";
        if ($legend_text !== '') {
            return $fieldset.'<legend>'.$legend_text."</legend>\n";
        }

        return $fieldset;
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_fieldset_close')) {
    /**
     * Fieldset Close Tag
     *
     * @param    string
     *
     * @return    string
     */
    function form_fieldset_close($extra = '')
    {
        return '</fieldset>'.$extra;
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_close')) {
    /**
     * Form Close Tag
     *
     * @param    string
     *
     * @return    string
     */
    function form_close($extra = '')
    {
        return '</form>'.$extra;
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_prep')) {
    /**
     * Form Prep
     * Formats text so that it can be safely placed in a form field in the event it has HTML tags.
     * @deprecated    3.0.0    An alias for html_escape()
     *
     * @param    string|string[] $str Value to escape
     *
     * @return    string|string[]    Escaped values
     */
    function form_prep($str)
    {
        return app(HtmlBuilder::class)->attributes($str);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('set_value')) {
    /**
     * Form Value
     * Grabs a value from the POST array for the specified field so you can
     * re-populate an input field or textarea. If Form Validation
     * is active it retrieves the info from the validation class
     *
     * @param    string $field Field name
     * @param    string $default Default value
     *
     * @return    string
     */
    function set_value($field, $default = '')
    {
        return app(FormBuilder::class)->getValueAttribute($field, $default);
    }
}

// ------------------------------------------------------------------------

if (!function_exists('set_select')) {
    /**
     * Set Select
     * Let's you set the selected value of a <select> menu via data in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     *
     * @param    string
     * @param    string
     * @param    bool
     *
     * @return    string
     */
    function set_select($field, $value = '', $default = FALSE)
    {
        if (($input = set_value($field, FALSE)) === null) {
            return ($default === TRUE) ? ' selected="selected"' : '';
        }

        $value = (string)$value;
        if (is_array($input)) {
            // Note: in_array('', array(0)) returns TRUE, do not use it
            foreach ($input as &$v) {
                if ($value === $v) {
                    return ' selected="selected"';
                }
            }

            return '';
        }

        return ($input === $value) ? ' selected="selected"' : '';
    }
}

// ------------------------------------------------------------------------

if (!function_exists('set_checkbox')) {
    /**
     * Set Checkbox
     * Let's you set the selected value of a checkbox via the value in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     *
     * @param    string
     * @param    string
     * @param    bool
     *
     * @return    string
     */
    function set_checkbox($field, $value = '', $default = FALSE)
    {
        // Form inputs are always strings ...
        $value = (string)$value;
        $input = set_value($field, FALSE);

        if (is_array($input)) {
            // Note: in_array('', array(0)) returns TRUE, do not use it
            foreach ($input as &$v) {
                if ($value === $v) {
                    return ' checked="checked"';
                }
            }

            return '';
        }
        else if (is_string($input)) {
            return ($input === $value) ? ' checked="checked"' : '';
        }

        return ($default === TRUE) ? ' checked="checked"' : '';
    }
}

// ------------------------------------------------------------------------

if (!function_exists('set_radio')) {
    /**
     * Set Radio
     * Let's you set the selected value of a radio field via info in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     *
     * @param    string $field
     * @param    string $value
     * @param    bool $default
     *
     * @return    string
     */
    function set_radio($field, $value = '', $default = FALSE)
    {
        // Form inputs are always strings ...
        $value = (string)$value;
        $input = set_value($field, FALSE);

        if (is_array($input)) {
            // Note: in_array('', array(0)) returns TRUE, do not use it
            foreach ($input as &$v) {
                if ($value === $v) {
                    return ' checked="checked"';
                }
            }

            return '';
        }
        else if (is_string($input)) {
            return ($input === $value) ? ' checked="checked"' : '';
        }

        return ($default === TRUE) ? ' checked="checked"' : '';
    }
}

// ------------------------------------------------------------------------

if (!function_exists('form_error')) {
    /**
     * Form Error
     * Returns the error for a specific form field. This is a helper for the
     * form validation class.
     *
     * @param    string
     * @param    string
     * @param    string
     *
     * @return    string
     */
    function form_error($field = null, $prefix = '', $suffix = '')
    {
        $errors = (Config::get('session.driver') AND Session::has('errors'))
            ? Session::get('errors')
            : new \Illuminate\Support\ViewErrorBag;

        if (is_null($field))
            return $errors;

        if (!$errors->has($field)) {
            return null;
        }

        return $prefix.$errors->first($field).$suffix;
    }
}

// ------------------------------------------------------------------------

//if (!function_exists('validation_errors')) {
//    /**
//     * Validation Error String
//     * Returns all the errors associated with a form submission. This is a helper
//     * function for the form validation class.
//     *
//     * @param    string
//     * @param    string
//     *
//     * @return    string
//     */
//    function validation_errors($prefix = '', $suffix = '')
//    {
////        if (FALSE === ($OBJ =& _get_validation_object())) {
//        return '';
////        }
//
////        return $OBJ->error_string($prefix, $suffix);
//    }
//}

// ------------------------------------------------------------------------
//
//if (!function_exists('_parse_form_attributes')) {
//    /**
//     * Parse the form attributes
//     * Helper function used by some of the form helpers
//     *
//     * @param    array $attributes List of attributes
//     * @param    array $default Default values
//     *
//     * @return    string
//     */
//    function _parse_form_attributes($attributes, $default)
//    {
//        if (is_array($attributes)) {
//            foreach ($default as $key => $val) {
//                if (isset($attributes[$key])) {
//                    $default[$key] = $attributes[$key];
//                    unset($attributes[$key]);
//                }
//            }
//
//            if (count($attributes) > 0) {
//                $default = array_merge($default, $attributes);
//            }
//        }
//
//        $att = '';
//
//        foreach ($default as $key => $val) {
//            if ($key === 'value') {
//                $val = html_escape($val);
//            }
//            elseif ($key === 'name' && !strlen($default['name'])) {
//                continue;
//            }
//
//            $att .= $key.'="'.$val.'" ';
//        }
//
//        return $att;
//    }
//}
//
//// ------------------------------------------------------------------------
//
//if (!function_exists('_attributes_to_string')) {
//    /**
//     * Attributes To String
//     * Helper function used by some of the form helpers
//     *
//     * @param    mixed
//     *
//     * @return    string
//     */
//    function _attributes_to_string($attributes)
//    {
//        if (empty($attributes)) {
//            return '';
//        }
//
//        if (is_object($attributes)) {
//            $attributes = (array)$attributes;
//        }
//
//        if (is_array($attributes)) {
//            $atts = '';
//
//            foreach ($attributes as $key => $val) {
//                $atts .= ' '.$key.'="'.$val.'"';
//            }
//
//            return $atts;
//        }
//
//        if (is_string($attributes)) {
//            return ' '.$attributes;
//        }
//
//        return FALSE;
//    }
//}
//
//// ------------------------------------------------------------------------
//
//if (!function_exists('_get_validation_object')) {
//    /**
//     * Validation Object
//     * Determines what the form validation class was instantiated as, fetches
//     * the object and returns it.
//     * @return    mixed
//     */
//    function &_get_validation_object()
//    {
//        $CI =& get_instance();
//
//        // We set this as a variable since we're returning by reference.
//        $return = FALSE;
//
//        if (FALSE !== ($object = $CI->load->is_loaded('Form_validation'))) {
//            if (!isset($CI->$object) OR !is_object($CI->$object)) {
//                return $return;
//            }
//
//            return $CI->$object;
//        }
//
//        return $return;
//    }
//}
