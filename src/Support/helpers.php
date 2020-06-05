<?php

use Igniter\Flame\ActivityLog\ActivityLogger;
use Igniter\Flame\Support\Str;
use Igniter\Flame\Support\StringParser;
use Illuminate\Routing\UrlGenerator;

if (!function_exists('current_url')) {
    /**
     * Current URL
     * Returns the full URL (including segments and query string) of the page where this
     * function is placed
     * @return    string
     */
    function current_url()
    {
        return app(UrlGenerator::class)->current();
    }
}

if (!function_exists('assets_url')) {
    /**
     * Assets URL
     * Returns the full URL (including segments) of the assets directory
     *
     * @param string $uri
     * @param null $secure
     *
     * @return string
     */
    function assets_url($uri = null, $secure = null)
    {
        return app(UrlGenerator::class)->asset(trim(config('system.assetsDir'), '/').'/'.$uri, $secure);
    }
}

if (!function_exists('uploads_path')) {
    /**
     * Get the path to the uploads folder.
     *
     * @param string $path
     * @return string
     */
    function uploads_path($path = '')
    {
        return app('path.uploads').($path ? '/'.$path : $path);
    }
}

if (!function_exists('image_url')) {
    /**
     * Image Assets URL
     * Returns the full URL (including segments) of the assets image directory
     *
     * @param string $uri
     * @param null $protocol
     *
     * @return string
     */
    function image_url($uri = null, $protocol = null)
    {
        traceLog('image_url() has been deprecated, use assets_url() instead.');

        return app(UrlGenerator::class)->asset('assets/images/'.$uri, $protocol);
    }
}

if (!function_exists('image_path')) {
    /**
     * Get the path to the assets image folder.
     *
     * @param string $path The path to prepend
     *
     * @return    string
     */
    function image_path($path = '')
    {
        traceLog('image_path() has been deprecated, use assets_path() instead.');

        return assets_path('images').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (!function_exists('theme_url')) {
    /**
     * Theme URL
     * Create a local URL based on your theme path.
     * Segments can be passed in as a string.
     *
     * @param string $uri
     * @param string $secure
     *
     * @return    string
     */
    function theme_url($uri = '', $secure = null)
    {
        return app(UrlGenerator::class)->asset(trim(config('system.themesDir'), '/').'/'.$uri, $secure);
    }
}

if (!function_exists('theme_path')) {
    /**
     * Theme Path
     * Create a local URL based on your theme path.
     * Segments can be passed in as a string.
     *
     * @param string $path
     *
     * @return    string
     */
    function theme_path($path = '')
    {
        return app('path.themes').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (!function_exists('referrer_url')) {
    /**
     * Referrer URL
     * Returns the full URL (including segments) of the page where this
     * function is placed
     * @return    string
     */
    function referrer_url()
    {
        return app(UrlGenerator::class)->previous();
    }
}

if (!function_exists('root_url')) {
    /**
     * Root URL
     * Create a local URL based on your root path.
     * Segments can be passed in as a string.
     *
     * @param string $uri
     * @param array $params
     *
     * @return    string
     */
    function root_url($uri = '', array $params = [])
    {
        return app(UrlGenerator::class)->to($uri, $params);
    }
}

if (!function_exists('extension_path')) {
    /**
     * Get the path to the extensions folder.
     *
     * @param string $path The path to prepend
     *
     * @return    string
     */
    function extension_path($path = '')
    {
        return app('path.extensions').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (!function_exists('assets_path')) {
    /**
     * Get the path to the assets folder.
     *
     * @param string $path The path to prepend
     *
     * @return    string
     */
    function assets_path($path = '')
    {
        return app('path.assets').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (!function_exists('temp_path')) {
    /**
     * Get the path to the downloads temp folder.
     *
     * @param string $path The path to prepend
     *
     * @return    string
     */
    function temp_path($path = '')
    {
        return app('path.temp').($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (!function_exists('setting')) {
    /**
     * @param null $key
     * @param null $default
     *
     * @return mixed
     */
    function setting($key = null, $default = null)
    {
        $settingConfig = app('system.setting');

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
        $settingParam = app('system.parameter');

        if (is_null($key))
            return $settingParam;

        return $settingParam->get($key, $default);
    }
}

if (!function_exists('parse_values')) {
    /**
     * Determine if a given string contains a given substring.
     *
     * @param array $columns Expected key names to parse
     * @param string $string URL template
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
        return App::make(ActivityLogger::class);
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
            return Request::all();

        // Array field name, eg: field[key][key2][key3]
        $name = implode('.', name_to_array($name));

        return Request::get($name, $default);
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
        return Str::getClassId($name);
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
        return Str::normalizeClassName($name);
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
     * @return \Igniter\Flame\Currency\Currency|string
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
     * @param string|null $message
     * @param string $level
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

if (!function_exists('array_undot')) {
    function array_undot($dottedArray)
    {
        $array = [];
        foreach ($dottedArray as $key => $value) {
            array_set($array, $key, $value);
        }

        return $array;
    }
}

if (!function_exists('collect')) {
    /**
     * Create a collection from the given value.
     *
     * @param mixed $value
     * @return \Illuminate\Support\Collection
     */
    function collect($value = null)
    {
        return new \Illuminate\Support\Collection($value);
    }
}