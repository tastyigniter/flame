<?php

use Carbon\Carbon;
use Igniter\Admin\Facades\Admin;
use Igniter\Flame\ActivityLog\ActivityLogger;
use Igniter\Flame\Support\StringParser;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        traceLog('assets_url() has been deprecated. Use $model->getThumb()');
    }
}

if (!function_exists('igniter_path')) {
    function igniter_path($path = '')
    {
        return dirname(__DIR__, 4).($path ? '/'.$path : $path);
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
        return app(UrlGenerator::class)->asset(trim(config('igniter.system.themesDir'), '/').'/'.$uri, $secure);
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
        traceLog('Deprecated function. No longer supported. Use __DIR__');
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
     * @return string
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
        $inputData = request()->input();
        if (is_null($name))
            return $inputData;

        // Array field name, eg: field[key][key2][key3]
        $name = implode('.', name_to_array($name));

        return array_get($inputData, $name, $default);
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
        $postData = request()->post();
        if (is_null($name))
            return $postData;

        // Array field name, eg: field[key][key2][key3]
        $name = implode('.', name_to_array($name));

        return array_get($postData, $name, $default);
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
        $inputData = request()->input();
        if (is_null($name))
            return $inputData;

        // Array field name, eg: field[key][key2][key3]
        $name = implode('.', name_to_array($name));

        return array_get($inputData, $name, $default);
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
    function lang($key, $replace = [], $locale = null, $fallback = true)
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
    function currency($amount = null, $from = null, $to = null, $format = true)
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
    function currency_format($amount = null, $currency = null, $include_symbol = true)
    {
        return app('currency')->format($amount, $currency, $include_symbol);
    }
}

if (!function_exists('currency_json')) {
    /**
     * Convert value to a currency array
     *
     * @param float $amount
     * @param string $currency
     *
     * @return array
     */
    function currency_json($amount = null, $currency = null)
    {
        return app('currency')->formatToJson($amount, $currency);
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

if (!function_exists('normalize_uri')) {
    /**
     * Adds leading slash from a URL.
     *
     * @param string $uri URI to normalize.
     *
     * @return string Returns normalized URL.
     */
    function normalize_uri($uri)
    {
        if (substr($uri, 0, 1) != '/')
            $uri = '/'.$uri;

        if (!strlen($uri))
            $uri = '/';

        return $uri;
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

if (!function_exists('controller')) {
    /**
     * Get the page controller
     * @return Igniter\Main\Classes\MainController
     */
    function controller()
    {
        return \Igniter\Main\Classes\MainController::getController() ?? new \Igniter\Main\Classes\MainController;
    }
}

if (!function_exists('page_url')) {
    /**
     * Page URL
     * Returns the full URL (including segments) of the page where this
     * function is placed
     *
     * @param string $uri
     * @param array $params
     *
     * @return string
     */
    function page_url($uri = null, array $params = [])
    {
        return controller()->pageUrl($uri, $params);
    }
}

if (!function_exists('site_url')) {
    /**
     * Site URL
     * Create a local URL based on your basepath. Segments can be passed via the
     * first parameter either as a string or an array.
     *
     * @param string $uri
     * @param array $params
     *
     * @return string
     */
    function site_url($uri = null, $params = [])
    {
        return controller()->url($uri, $params);
    }
}

if (!function_exists('restaurant_url')) {
    /**
     * Restaurant URL
     * Returns the full URL (including segments) of the local restaurant if any,
     * else locations URL is returned
     *
     * @param string $uri
     * @param array $params
     *
     * @return string
     */
    function restaurant_url($uri = null, array $params = [])
    {
        return controller()->pageUrl($uri, $params);
    }
}

if (!function_exists('admin_url')) {
    /**
     * Admin URL
     * Create a local URL based on your admin path.
     * Segments can be passed in as a string.
     *
     * @param string $uri
     * @param array $params
     *
     * @return    string
     */
    function admin_url($uri = '', array $params = [])
    {
        return Admin::url($uri, $params);
    }
}

if (!function_exists('uploads_url')) {
    /**
     * Media Uploads URL
     * Returns the full URL (including segments) of the assets media uploads directory
     *
     * @param null $path
     * @return string
     */
    function uploads_url($path = null)
    {
        return resolve(\Igniter\Main\Classes\MediaLibrary::class)->getMediaUrl($path);
    }
}

if (!function_exists('strip_class_basename')) {
    function strip_class_basename($class = '', $chop = null)
    {
        $basename = class_basename($class);

        if (is_null($chop))
            return $basename;

        if (!ends_with($basename, $chop))
            return $basename;

        return substr($basename, 0, -strlen($chop));
    }
}

if (!function_exists('mdate')) {
    /**
     * Convert MySQL Style Datecodes
     * This function is identical to PHPs date() function,
     * except that it allows date codes to be formatted using
     * the MySQL style, where each code letter is preceded
     * with a percent sign:  %Y %m %d etc...
     * The benefit of doing dates this way is that you don't
     * have to worry about escaping your text letters that
     * match the date codes.
     *
     * @param string $format
     * @param string $time
     *
     * @return int
     */
    function mdate($format = null, $time = null)
    {
        if (is_null($time) && $format) {
            $time = $format;
            $format = null;
        }

        if (is_null($format))
            $format = lang('igniter::system.php.date_format');

        if (is_null($time))
            return null;

        if (empty($time))
            $time = time();

        if (str_contains($format, '%'))
            $format = str_replace(
                '%\\',
                '',
                preg_replace('/([a-z]+?)/i', '\\\\\\1', $format)
            );

        return date($format, $time);
    }
}

if (!function_exists('convert_php_to_moment_js_format')) {
    /**
     * Convert PHP Date formats to Moment JS Date Formats
     *
     * @param string $format
     *
     * @return string
     */
    function convert_php_to_moment_js_format($format)
    {
        $replacements = [
            'd' => 'DD',
            'D' => 'ddd',
            'j' => 'D',
            'l' => 'dddd',
            'N' => 'E',
            'S' => 'o',
            'w' => 'e',
            'z' => 'DDD',
            'W' => 'W',
            'F' => 'MMMM',
            'm' => 'MM',
            'M' => 'MMM',
            'n' => 'M',
            't' => '',
            'L' => '',
            'o' => 'YYYY',
            'Y' => 'YYYY',
            'y' => 'YY',
            'a' => 'a',
            'A' => 'A',
            'B' => '',
            'g' => 'h',
            'G' => 'H',
            'h' => 'hh',
            'H' => 'HH',
            'i' => 'mm',
            's' => 'ss',
            'u' => 'SSS',
            'e' => 'zz',
            'I' => '',
            'O' => '',
            'P' => '',
            'T' => '',
            'Z' => '',
            'c' => '',
            'r' => '',
            'U' => 'X',
        ];

        foreach ($replacements as $from => $to) {
            $replacements['\\'.$from] = '['.$from.']';
        }

        return strtr($format, $replacements);
    }
}

if (!function_exists('time_elapsed')) {
    /**
     * Get time elapsed
     * Returns a time elapsed of seconds, minutes, hours, days in this format:
     *    10 days, 14 hours, 36 minutes, 47 seconds, now
     *
     * @param string $datetime
     * @return string
     */
    function time_elapsed($datetime)
    {
        return make_carbon($datetime)->diffForHumans();
    }
}

if (!function_exists('day_elapsed')) {
    /**
     * Get day elapsed
     * Returns a day elapsed as today, yesterday or date d/M/y:
     *    Today or Yesterday or 12 Jan 15
     *
     * @param string $datetime
     *
     * @return string
     */
    function day_elapsed($datetime, $full = true)
    {
        $datetime = make_carbon($datetime);
        $time = $datetime->isoFormat(lang('igniter::system.moment.time_format'));
        $date = $datetime->isoFormat(lang('igniter::system.moment.date_format'));

        if ($datetime->isToday()) {
            $date = lang('igniter::system.date.today');
        }
        elseif ($datetime->isYesterday()) {
            $date = lang('igniter::system.date.yesterday');
        }
        elseif ($datetime->isTomorrow()) {
            $date = lang('igniter::system.date.tomorrow');
        }

        return $full ? sprintf(lang('igniter::system.date.full'), $date, $time) : $date;
    }
}

if (!function_exists('time_range')) {
    /**
     * Date range
     * Returns a list of time within a specified period.
     *
     * @param int $unix_start UNIX timestamp of period start time
     * @param int $unix_end UNIX timestamp of period end time
     * @param int $interval Specifies the second interval
     * @param string $time_format Output time format, same as in date()
     *
     * @return    array
     */
    function time_range($unix_start, $unix_end, $interval, $time_format = '%H:%i')
    {
        if ($unix_start == '' || $unix_end == '' || $interval == '') {
            return null;
        }

        $interval = ctype_digit($interval) ? $interval.' mins' : $interval;

        $start_time = strtotime($unix_start);
        $end_time = strtotime($unix_end);

        $current = time();
        $add_time = strtotime('+'.$interval, $current);
        $diff = $add_time - $current;

        $times = [];
        while ($start_time < $end_time) {
            $times[] = mdate($time_format, $start_time);
            $start_time += $diff;
        }
        $times[] = mdate($time_format, $start_time);

        return $times;
    }
}

if (!function_exists('parse_date_format')) {
    /**
     * @param string $format The time format
     *
     * @return string $format The date format
     */
    function parse_date_format($format)
    {
        if (str_contains($format, '%')) {
            $format = str_replace(
                '%\\',
                '',
                preg_replace('/([a-z]+?)/i', '\\\\\\1', $format)
            );
        }

        return $format;
    }
}

if (!function_exists('make_carbon')) {
    /**
     * Converts mixed inputs to a Carbon object.
     *
     * @param $value
     * @param bool $throwException
     *
     * @return \Carbon\Carbon
     */
    function make_carbon($value, $throwException = true)
    {
        if ($value instanceof Carbon) {
            // Do nothing
        }
        elseif ($value instanceof DateTime) {
            $value = Carbon::instance($value);
        }
        elseif (is_numeric($value)) {
            $value = Carbon::createFromTimestamp($value);
        }
        elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value)) {
            $value = Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }
        else {
            try {
                $value = Carbon::parse($value);
            }
            catch (Exception $ex) {
            }
        }

        if (!$value instanceof Carbon && $throwException) {
            throw new InvalidArgumentException('Invalid date value supplied to DateTime helper.');
        }

        return $value;
    }
}

if (!function_exists('is_single_location')) {
    /**
     * Is Single Location Mode
     * Test to see system config multi location mode is set to single.
     * @return bool
     */
    function is_single_location()
    {
        return config('igniter.system.locationMode', setting('site_location_mode')) === \Igniter\Admin\Models\Location::LOCATION_CONTEXT_SINGLE;
    }
}

if (!function_exists('log_message')) {
    /**
     * Error Logging Interface
     * We use this as a simple mechanism to access the logging
     * class and send messages to be logged.
     *
     * @param string $level the error level: 'error', 'debug' or 'info'
     * @param string $message the error message
     *
     * @return    void
     */
    function log_message($level, $message)
    {
        Log::$level($message);
    }
}

if (!function_exists('traceLog')) {
    function traceLog()
    {
        $messages = func_get_args();

        foreach ($messages as $message) {
            $level = 'info';

            if ($message instanceof Exception) {
                $level = 'error';
            }
            elseif (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            Log::$level($message);
        }
    }
}

if (!function_exists('sort_array')) {
    /**
     * Sort an array by key
     *
     * @param array $array
     * @param string $sort_key
     * @param array $option
     *
     * @return array
     */
    function sort_array($array = [], $sort_key = 'priority', $option = SORT_ASC)
    {
        if (!empty($array)) {
            foreach ($array as $key => $value) {
                $sort_array[$key] = $value[$sort_key] ?? 0;
            }

            array_multisort($sort_array, $option, $array);
        }

        return $array;
    }
}

if (!function_exists('name_to_id')) {
    /**
     * Converts a HTML array string to an identifier string.
     * HTML: user[location][city]
     * Result: user-location-city
     *
     * @param $string String to process
     *
     * @return string
     */
    function name_to_id($string)
    {
        return rtrim(str_replace('--', '-', str_replace(['[', ']'], '-', str_replace('_', '-', $string))), '-');
    }
}

if (!function_exists('name_to_array')) {
    /**
     * Converts a HTML named array string to a PHP array. Empty values are removed.
     * HTML: user[location][city]
     * PHP:  ['user', 'location', 'city']
     *
     * @param $string String to process
     *
     * @return array
     */
    function name_to_array($string)
    {
        $result = [$string];

        if (strpbrk($string, '[]') === false)
            return $result;

        if (preg_match('/^([^\]]+)(?:\[(.+)\])+$/', $string, $matches)) {
            if (count($matches) < 2)
                return $result;

            $result = explode('][', $matches[2]);
            array_unshift($result, $matches[1]);
        }

        return array_filter($result, function ($val) {
            return strlen($val);
        });
    }
}

if (!function_exists('convert_camelcase_to_underscore')) {
    /**
     * Convert CamelCase to underscore Camel_Case
     * Converts a StringWithCamelCase into string_with_underscore. Strings can be passed via the
     * first parameter either as a string or an array.
     *
     * @param string $string
     * @param bool $lowercase
     *
     * @return string CamelCase
     */
    function convert_camelcase_to_underscore($string = '', $lowercase = false)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
        $ret = $matches[0];

        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }

        $string = implode('_', $ret);

        return (!$lowercase) ? $string : strtolower($string);
    }
}

if (!function_exists('convert_underscore_to_camelcase')) {
    /**
     * Current URL
     * Converts a string_with_underscore into StringWithCamelCase. Strings can be passed via the
     * first parameter either as a string or an array.
     * @return    string
     */
    function convert_underscore_to_camelcase($string = '')
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }
}

if (!function_exists('contains_substring')) {
    /**
     * Determine if a given string contains a given substring.
     *
     * @param string $haystack
     * @param string|array $needles
     *
     * @return bool
     */
    function contains_substring($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('is_lang_key')) {
    /**
     * Determine if a given string matches a language key.
     *
     * @param string $line
     *
     * @return bool
     */
    function is_lang_key($line)
    {
        if (!is_string($line)) {
            return false;
        }

        if (strpos($line, '::') !== false) {
            return true;
        }

        if (starts_with($line, 'lang:')) {
            return true;
        }

        return false;
    }
}

if (!function_exists('generate_extension_icon')) {
    function generate_extension_icon($icon)
    {
        if (is_string($icon))
            $icon = starts_with($icon, ['//', 'http://', 'https://'])
                ? ['url' => $icon]
                : ['class' => 'fa '.$icon];

        $icon = array_merge([
            'class' => 'fa fa-plug',
            'color' => '',
            'image' => null,
            'backgroundColor' => '',
            'backgroundImage' => null,
        ], $icon);

        $styles = [];
        if (strlen($color = array_get($icon, 'color')))
            $styles[] = "color:$color;";

        if (strlen($backgroundColor = array_get($icon, 'backgroundColor')))
            $styles[] = "background-color:$backgroundColor;";

        if (is_array($backgroundImage = array_get($icon, 'backgroundImage')))
            $styles[] = "background-image:url('data:$backgroundImage[0];base64,$backgroundImage[1]');";

        $icon['styles'] = implode(' ', $styles);

        return $icon;
    }
}

if (!function_exists('array_replace_key')) {
    function array_replace_key($array, $oldKey, $newKey)
    {
        $keys = array_keys($array);

        if (($keyIndex = array_search($oldKey, $keys, true)) !== false) {
            $keys[$keyIndex] = $newKey;
        }

        return array_combine($keys, array_values($array));
    }
}
