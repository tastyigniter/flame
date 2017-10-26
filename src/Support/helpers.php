<?php

use Igniter\Flame\Setting\SettingManager;
use Igniter\Flame\Support\StrHelper;
use Igniter\Flame\Support\StringParser;

if (!function_exists('setting')) {
    /**
     * @param null $key
     * @param null $default
     *
     * @return mixed
     */
    function setting($key = null, $default = null)
    {
        $settingConfig = app(SettingManager::class)->driver('config');

        if (is_null($key))
            return $settingConfig;

        return $settingConfig->get($key, $default);
    }
}

if (!function_exists('params')) {
    /**
     * @param null $key
     * @param null $default
     *
     * @return mixed
     */
    function params($key = null, $default = null)
    {
        $settingPrefs = app(SettingManager::class)->driver('prefs');

        if (is_null($key))
            return $settingPrefs;

        return $settingPrefs->get($key, $default);
    }
}

if (!function_exists('parse_values')) {
    /**
     * Determine if a given string contains a given substring.
     *
     * @param  array $columns Expected key names to parse
     * @param  string $string URL template
     *
     * @return bool
     */
    function parse_values(array $columns, $string)
    {
        return (new StringParser)->parse($string, $columns);
    }
}

if (!function_exists('activity')) {
    /**
     * @return \Igniter\Flame\ActivityLog\ActivityLogger
     */
    function activity()
    {
        return App::make('\Igniter\Flame\ActivityLog\ActivityLogger');
    }
}

if (!function_exists('input')) {
    /**
     * Returns an input parameter or the default value.
     * Supports HTML Array names.
     * <pre>
     * $value = input('value', 'not found');
     * $name = input('contact[name]');
     * $name = input('contact[location][city]');
     * </pre>
     * Booleans are converted from strings
     *
     * @param string $name
     * @param string $default
     *
     * @return string|array
     */
    function input($name = null, $default = null)
    {
        if ($name === null)
            return Input::all();

        // Array field name, eg: field[key][key2][key3]
        $name = implode('.', name_to_array($name));

        return Input::get($name, $default);
    }
}

if (!function_exists('post')) {
    /**
     * Identical function to input(), however restricted to $_POST values.
     *
     * @param null $name
     * @param null $default
     *
     * @return mixed
     */
    function post($name = null, $default = null)
    {
        if ($name === null)
            return $_POST;

        // Array field name, eg: field[key][key2][key3]
        $name = implode('.', name_to_array($name));

        return array_get($_POST, $name, $default);
    }
}

if (!function_exists('get')) {
    /**
     * Identical function to input(), however restricted to $_GET values.
     *
     * @param null $name
     * @param null $default
     *
     * @return mixed
     */
    function get($name = null, $default = null)
    {
        if ($name === null)
            return $_GET;

        // Array field name, eg: field[key][key2][key3]
        $name = implode('.', name_to_array($name));

        return array_get($_GET, $name, $default);
    }
}

if (!function_exists('lang')) {
    /**
     * Get the translation for the given key.
     *
     * @param $key
     * @param array $replace
     * @param null $locale
     * @param bool $fallback
     *
     * @return mixed
     */
    function lang($key, $replace = [], $locale = null, $fallback = TRUE)
    {
        return Lang::get($key, $replace, $locale, $fallback);
    }
}

if (!function_exists('get_class_id')) {
    /**
     * Generates a class ID from either an object or a string of the class name.
     *
     * @param $name
     *
     * @return string
     */
    function get_class_id($name)
    {
        return StrHelper::getClassId($name);
    }
}

if (!function_exists('normalize_class_name')) {
    /**
     * Removes the starting slash from a class namespace \
     *
     * @param $name
     *
     * @return string
     */
    function normalize_class_name($name)
    {
        return StrHelper::normalizeClassName($name);
    }
}

if (!function_exists('currency')) {
    /**
     * Convert given number.
     *
     * @param float $amount
     * @param string $from
     * @param string $to
     * @param bool $format
     *
     * @return \Torann\Currency\Currency|string
     */
    function currency($amount = null, $from = null, $to = null, $format = TRUE)
    {
        if (is_null($amount)) {
            return app('currency');
        }

        return app('currency')->convert($amount, $from, $to, $format);
    }
}

if (!function_exists('currency_format')) {
    /**
     * Append or Prepend the default currency symbol to amounts
     *
     * @param float $amount
     * @param string $currency
     * @param bool $include_symbol
     *
     * @return string
     */
    function currency_format($amount = null, $currency = null, $include_symbol = TRUE)
    {
        return app('currency')->format($amount, $currency, $include_symbol);
    }
}

if (!function_exists('flash')) {
    /**
     * Arrange for a flash message.
     *
     * @param  string|null $message
     * @param  string $level
     *
     * @return \Igniter\Flame\Flash\FlashBag
     */
    function flash($message = null, $level = 'info')
    {
        $flashBag = app('flash');

        if (!is_null($message)) {
            return $flashBag->message($message, $level);
        }

        return $flashBag;
    }
}
