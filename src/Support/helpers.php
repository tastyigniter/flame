<?php

use Igniter\Flame\ActivityLog\ActivityLogger;
use Igniter\Flame\Support\Str;
use Igniter\Flame\Support\StringParser;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Arr;

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

if (!function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * @param string $id
     * @param array $parameters
     * @param string $locale
     * @return string
     */
    function trans($id = null, $parameters = [], $locale = null)
    {
        return app('translator')->get($id, $parameters, $locale);
    }
}

if (!function_exists('array_add')) {
    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    function array_add($array, $key, $value)
    {
        return Arr::add($array, $key, $value);
    }
}

if (!function_exists('array_collapse')) {
    /**
     * Collapse an array of arrays into a single array.
     *
     * @param array $array
     * @return array
     */
    function array_collapse($array)
    {
        return Arr::collapse($array);
    }
}

if (!function_exists('array_divide')) {
    /**
     * Divide an array into two arrays. One with keys and the other with values.
     *
     * @param array $array
     * @return array
     */
    function array_divide($array)
    {
        return Arr::divide($array);
    }
}

if (!function_exists('array_dot')) {
    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param array $array
     * @param string $prepend
     * @return array
     */
    function array_dot($array, $prepend = '')
    {
        return Arr::dot($array, $prepend);
    }
}

if (!function_exists('array_except')) {
    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param array $array
     * @param array|string $keys
     * @return array
     */
    function array_except($array, $keys)
    {
        return Arr::except($array, $keys);
    }
}

if (!function_exists('array_first')) {
    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param array $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    function array_first($array, callable $callback = null, $default = null)
    {
        return Arr::first($array, $callback, $default);
    }
}

if (!function_exists('array_flatten')) {
    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param array $array
     * @param int $depth
     * @return array
     */
    function array_flatten($array, $depth = INF)
    {
        return Arr::flatten($array, $depth);
    }
}

if (!function_exists('array_forget')) {
    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param array $array
     * @param array|string $keys
     * @return void
     */
    function array_forget(&$array, $keys)
    {
        Arr::forget($array, $keys);
    }
}

if (!function_exists('array_get')) {
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param \ArrayAccess|array $array
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    function array_get($array, $key, $default = null)
    {
        return Arr::get($array, $key, $default);
    }
}

if (!function_exists('array_has')) {
    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param \ArrayAccess|array $array
     * @param string|array $keys
     * @return bool
     */
    function array_has($array, $keys)
    {
        return Arr::has($array, $keys);
    }
}

if (!function_exists('array_last')) {
    /**
     * Return the last element in an array passing a given truth test.
     *
     * @param array $array
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    function array_last($array, callable $callback = null, $default = null)
    {
        return Arr::last($array, $callback, $default);
    }
}

if (!function_exists('array_only')) {
    /**
     * Get a subset of the items from the given array.
     *
     * @param array $array
     * @param array|string $keys
     * @return array
     */
    function array_only($array, $keys)
    {
        return Arr::only($array, $keys);
    }
}

if (!function_exists('array_pluck')) {
    /**
     * Pluck an array of values from an array.
     *
     * @param array $array
     * @param string|array $value
     * @param string|array|null $key
     * @return array
     */
    function array_pluck($array, $value, $key = null)
    {
        return Arr::pluck($array, $value, $key);
    }
}

if (!function_exists('array_prepend')) {
    /**
     * Push an item onto the beginning of an array.
     *
     * @param array $array
     * @param mixed $value
     * @param mixed $key
     * @return array
     */
    function array_prepend($array, $value, $key = null)
    {
        return Arr::prepend($array, $value, $key);
    }
}

if (!function_exists('array_pull')) {
    /**
     * Get a value from the array, and remove it.
     *
     * @param array $array
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function array_pull(&$array, $key, $default = null)
    {
        return Arr::pull($array, $key, $default);
    }
}

if (!function_exists('array_random')) {
    /**
     * Get a random value from an array.
     *
     * @param array $array
     * @param int|null $num
     * @return mixed
     */
    function array_random($array, $num = null)
    {
        return Arr::random($array, $num);
    }
}

if (!function_exists('array_set')) {
    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return array
     */
    function array_set(&$array, $key, $value)
    {
        return Arr::set($array, $key, $value);
    }
}

if (!function_exists('array_sort')) {
    /**
     * Sort the array by the given callback or attribute name.
     *
     * @param array $array
     * @param callable|string|null $callback
     * @return array
     */
    function array_sort($array, $callback = null)
    {
        return Arr::sort($array, $callback);
    }
}

if (!function_exists('array_sort_recursive')) {
    /**
     * Recursively sort an array by keys and values.
     *
     * @param array $array
     * @return array
     */
    function array_sort_recursive($array)
    {
        return Arr::sortRecursive($array);
    }
}

if (!function_exists('array_where')) {
    /**
     * Filter the array using the given callback.
     *
     * @param array $array
     * @param callable $callback
     * @return array
     */
    function array_where($array, callable $callback)
    {
        return Arr::where($array, $callback);
    }
}

if (!function_exists('array_wrap')) {
    /**
     * If the given value is not an array, wrap it in one.
     *
     * @param mixed $value
     * @return array
     */
    function array_wrap($value)
    {
        return Arr::wrap($value);
    }
}

if (!function_exists('camel_case')) {
    /**
     * Convert a value to camel case.
     *
     * @param string $value
     * @return string
     */
    function camel_case($value)
    {
        return Str::camel($value);
    }
}

if (!function_exists('ends_with')) {
    /**
     * Determine if a given string ends with a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    function ends_with($haystack, $needles)
    {
        return Str::endsWith($haystack, $needles);
    }
}

if (!function_exists('kebab_case')) {
    /**
     * Convert a string to kebab case.
     *
     * @param string $value
     * @return string
     */
    function kebab_case($value)
    {
        return Str::kebab($value);
    }
}

if (!function_exists('snake_case')) {
    /**
     * Convert a string to snake case.
     *
     * @param string $value
     * @param string $delimiter
     * @return string
     */
    function snake_case($value, $delimiter = '_')
    {
        return Str::snake($value, $delimiter);
    }
}

if (!function_exists('starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    function starts_with($haystack, $needles)
    {
        return Str::startsWith($haystack, $needles);
    }
}

if (!function_exists('str_after')) {
    /**
     * Return the remainder of a string after a given value.
     *
     * @param string $subject
     * @param string $search
     * @return string
     */
    function str_after($subject, $search)
    {
        return Str::after($subject, $search);
    }
}

if (!function_exists('str_before')) {
    /**
     * Get the portion of a string before a given value.
     *
     * @param string $subject
     * @param string $search
     * @return string
     */
    function str_before($subject, $search)
    {
        return Str::before($subject, $search);
    }
}

if (!function_exists('str_contains')) {
    /**
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     * @return bool
     */
    function str_contains($haystack, $needles)
    {
        return Str::contains($haystack, $needles);
    }
}

if (!function_exists('str_finish')) {
    /**
     * Cap a string with a single instance of a given value.
     *
     * @param string $value
     * @param string $cap
     * @return string
     */
    function str_finish($value, $cap)
    {
        return Str::finish($value, $cap);
    }
}

if (!function_exists('str_is')) {
    /**
     * Determine if a given string matches a given pattern.
     *
     * @param string|array $pattern
     * @param string $value
     * @return bool
     */
    function str_is($pattern, $value)
    {
        return Str::is($pattern, $value);
    }
}

if (!function_exists('str_limit')) {
    /**
     * Limit the number of characters in a string.
     *
     * @param string $value
     * @param int $limit
     * @param string $end
     * @return string
     */
    function str_limit($value, $limit = 100, $end = '...')
    {
        return Str::limit($value, $limit, $end);
    }
}

if (!function_exists('str_plural')) {
    /**
     * Get the plural form of an English word.
     *
     * @param string $value
     * @param int $count
     * @return string
     */
    function str_plural($value, $count = 2)
    {
        return Str::plural($value, $count);
    }
}

if (!function_exists('str_random')) {
    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param int $length
     * @return string
     *
     * @throws \RuntimeException
     */
    function str_random($length = 16)
    {
        return Str::random($length);
    }
}

if (!function_exists('str_replace_array')) {
    /**
     * Replace a given value in the string sequentially with an array.
     *
     * @param string $search
     * @param array $replace
     * @param string $subject
     * @return string
     */
    function str_replace_array($search, array $replace, $subject)
    {
        return Str::replaceArray($search, $replace, $subject);
    }
}

if (!function_exists('str_replace_first')) {
    /**
     * Replace the first occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    function str_replace_first($search, $replace, $subject)
    {
        return Str::replaceFirst($search, $replace, $subject);
    }
}

if (!function_exists('str_replace_last')) {
    /**
     * Replace the last occurrence of a given value in the string.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    function str_replace_last($search, $replace, $subject)
    {
        return Str::replaceLast($search, $replace, $subject);
    }
}

if (!function_exists('str_singular')) {
    /**
     * Get the singular form of an English word.
     *
     * @param string $value
     * @return string
     */
    function str_singular($value)
    {
        return Str::singular($value);
    }
}

if (!function_exists('str_slug')) {
    /**
     * Generate a URL friendly "slug" from a given string.
     *
     * @param string $title
     * @param string $separator
     * @param string $language
     * @return string
     */
    function str_slug($title, $separator = '-', $language = 'en')
    {
        return Str::slug($title, $separator, $language);
    }
}

if (!function_exists('str_start')) {
    /**
     * Begin a string with a single instance of a given value.
     *
     * @param string $value
     * @param string $prefix
     * @return string
     */
    function str_start($value, $prefix)
    {
        return Str::start($value, $prefix);
    }
}

if (!function_exists('studly_case')) {
    /**
     * Convert a value to studly caps case.
     *
     * @param string $value
     * @return string
     */
    function studly_case($value)
    {
        return Str::studly($value);
    }
}

if (!function_exists('title_case')) {
    /**
     * Convert a value to title case.
     *
     * @param string $value
     * @return string
     */
    function title_case($value)
    {
        return Str::title($value);
    }
}
