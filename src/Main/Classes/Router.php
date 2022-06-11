<?php

namespace Igniter\Main\Classes;

use Igniter\Flame\Support\Facades\File;
use Igniter\Flame\Support\RouterHelper;
use Igniter\Main\Template\Page as PageTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

/**
 * Router Class
 * The router parses page URL patterns and finds pages by URLs.
 *
 * The page URL format is explained below.
 * <pre>/pages/:page_id</pre>
 * Name of parameters should be compatible with PHP variable names. To make a parameter optional
 * add the question mark after its name:
 * <pre>/pages/:page_id?</pre>
 * By default parameters in the middle of the URL are required, for example:
 * <pre>/pages/:page_id?/comments - although the :post_id parameter is marked as optional,
 * it will be processed as required.</pre>
 * Optional parameters can have default values which are used as fallback values in case if the real
 * parameter value is not presented in the URL. Default values cannot contain the pipe symbols and question marks.
 * Specify the default value after the question mark:
 * <pre>/pages/category/:page_id?10 - The page_id parameter would be 10 for this URL: /pages/category</pre>
 * You can also add regular expression validation to parameters. To add a validation expression
 * add the pipe symbol after the parameter name (or the question mark) and specify the expression.
 * The forward slash symbol is not allowed in the expressions. Examples:
 * <pre>/pages/:page_id|^[0-9]+$/comments - this will match /pages/10/comments
 * /pages/:page_id|^[0-9]+$ - this will match /pages/3
 * /pages/:page_name?|^[a-z0-9\-]+$ - this will match /pages/my-page</pre>
 *
 * Based on october\cms\Router
 */
class Router
{
    /**
     * @var string Value to use when a required parameter is not specified
     */
    public static $defaultValue = 'default';

    /**
     * @var \Igniter\Main\Classes\Theme The Main theme object.
     */
    protected $theme;

    /**
     * @var string The last URL to be looked up using findByUrl().
     */
    protected $url;

    /**
     * @var array A list of parameters names and values extracted from the URL pattern and URL string.
     */
    protected $parameters = [];

    /**
     * @var array Contains the URL map - the list of page file names and corresponding URL patterns.
     */
    protected $urlMap = [];

    public function __construct(Theme $theme = null)
    {
        $this->theme = $theme;
    }

    /**
     * Finds a page by its route name. Returns the page object and sets the $parameters property.
     *
     * @param string $url The current request path.
     * @param array $parameters The current route parameters.
     *
     * @return \Igniter\Main\Template\Page|mixed Returns page object
     * or null if the page cannot be found.
     */
    public function findPage($url, $parameters = [])
    {
        $apiResult = event('router.beforeRoute', [$url, $this, $parameters], true);
        if ($apiResult !== null)
            return $apiResult;

        $fileName = array_get($parameters, '_file_', $url);

        if (!strlen(File::extension($fileName)))
            $fileName .= '.blade.php';

        for ($pass = 1; $pass <= 2; $pass++) {
            if (($page = PageTemplate::loadCached($this->theme, $fileName)) === null) {
                if ($pass == 1) {
                    $this->clearCache();
                    continue;
                }

                return null;
            }

            return $page;
        }
    }

    /**
     * Finds a URL by it's page. Returns the URL route for linking to the page and uses the supplied
     * parameters in it's address.
     *
     * @param string $fileName Page file name.
     * @param array $parameters Route parameters to consider in the URL.
     *
     * @return string A built URL matching the page route.
     */
    public function findByFile($fileName, $parameters = [])
    {
        if (!strlen(File::extension($fileName))) {
            $fileName .= '.blade.php';
        }

        return $this->url($fileName, $parameters);
    }

    public function getRouteMap()
    {
        return collect($this->getUrlMap())->map(function ($page) {
            return RouterHelper::convertToRouteProperties($page);
        });
    }

    /**
     * Autoloads the URL map only allowing a single execution.
     * @return array Returns the URL map.
     */
    public function getUrlMap()
    {
        if (!count($this->urlMap)) {
            $this->loadUrlMap();
        }

        return $this->urlMap;
    }

    /**
     * Loads the URL map - a list of page file names and corresponding URL patterns.
     * The URL map can is cached. The clearUrlMap() method resets the cache. By default
     * the map is updated every time when a page is saved in the back-end, or
     * when the interval defined with the system.urlMapCacheTtl expires.
     */
    protected function loadUrlMap()
    {
        if (!$this->theme)
            return;

        $cacheable = app()->routesAreCached() ? -1 : 0;

        $this->urlMap = Cache::remember($this->getUrlMapCacheKey(), $cacheable, function () {
            $map = [];
            $pages = $this->theme->listPages();
            foreach ($pages as $page) {
                if (!optional($page)->permalink)
                    continue;

                $map[] = [
                    'file' => $page->getFileName(),
                    'route' => str_replace('/', '-', $page->getBaseFileName()),
                    'pattern' => $page->permalink,
                ];
            }

            return $map;
        });
    }

    /**
     * Clears the router cache.
     */
    public function clearCache()
    {
        Cache::forget($this->getUrlMapCacheKey());
//        Cache::forget($this->getUrlListCacheKey());
    }

    /**
     * Returns the current routing parameters.
     * @return array
     */
    public function getParameters()
    {
        return request()->route()->parameters();
    }

    /**
     * Returns a routing parameter.
     *
     * @param $name
     * @param $default
     *
     * @return object|string|null
     */
    public function getParameter($name, $default = null)
    {
        return request()->route()->parameter($name, $default);
    }

    /**
     * Returns the caching URL key depending on the theme.
     *
     * @param string $keyName Specifies the base key name.
     *
     * @return string Returns the theme-specific key name.
     */
    protected function getCacheKey($keyName)
    {
        return md5($this->theme->getPath()).$keyName.Lang::getLocale();
    }

    /**
     * Returns the cache key name for the URL list.
     * @return string
     */
    protected function getUrlMapCacheKey()
    {
        return $this->getCacheKey('page-url-map');
    }

    /**
     * Tries to load a page file name corresponding to a specified URL from the cache.
     *
     * @param string $url Specifies the requested URL.
     * @param array &$urlList The URL list loaded from the cache
     *
     * @return mixed Returns the page file name if the URL exists in the cache. Otherwise returns null.
     */
    protected function getCachedUrlFileName($url, &$urlList)
    {
        $key = $this->getUrlListCacheKey();
        $urlList = Cache::get($key, false);

        if (
            $urlList &&
            ($urlList = @unserialize(@base64_decode($urlList))) &&
            is_array($urlList)
        ) {
            if (array_key_exists($url, $urlList)) {
                return $urlList[$url];
            }
        }

        return null;
    }

    /**
     * Builds a URL together by matching route name and supplied parameters
     *
     * @param string $name Name of the route previously defined.
     * @param array $parameters Parameter name => value items to fill in for given route.
     * @return string Full matched URL as string with given values put in place of named parameters
     */
    public function url($name, $parameters = [])
    {
        if (!$routeRule = collect($this->getUrlMap())->firstWhere('file', $name))
            return null;

        return $this->urlFromPattern($routeRule['pattern'], $parameters);
    }

    /**
     * Builds a URL together by matching route pattern and supplied parameters
     *
     * @param string $pattern Route pattern string, eg: /path/to/something/:parameter
     * @param array $parameters Parameter name => value items to fill in for given route.
     * @return string Full matched URL as string with given values put in place of named parameters
     */
    public function urlFromPattern($pattern, $parameters = [])
    {
        $patternSegments = RouterHelper::segmentizeUrl($pattern);

        /*
         * Normalize the parameters, colons (:) in key names are removed.
         */
        foreach ($parameters as $param => $value) {
            if (!starts_with($param, ':')) {
                continue;
            }
            $normalizedParam = substr($param, 1);
            $parameters[$normalizedParam] = $value;
            unset($parameters[$param]);
        }

        /*
         * Build the URL segments, remember the last populated index
         */
        $url = [];
        $lastPopulatedIndex = 0;

        foreach ($patternSegments as $index => $patternSegment) {
            /*
             * Static segment
             */
            if (!starts_with($patternSegment, ':')) {
                $url[] = $patternSegment;
            }
            /*
             * Dynamic segment
             */
            else {
                $paramName = RouterHelper::getParameterName($patternSegment);

                /*
                 * Determine whether it is optional
                 */
                $optional = RouterHelper::segmentIsOptional($patternSegment);

                /*
                 * Default value
                 */
                $defaultValue = RouterHelper::getSegmentDefaultValue($patternSegment);

                /*
                 * Check if parameter has been supplied and is not a default value
                 */
                $parameterExists = array_key_exists($paramName, $parameters) &&
                    strlen($parameters[$paramName]) &&
                    $parameters[$paramName] !== $defaultValue;

                /*
                 * Use supplied parameter value
                 */
                if ($parameterExists) {
                    $url[] = $parameters[$paramName];
                }
                /*
                 * Look for a specified default value
                 */
                elseif ($optional) {
                    $url[] = $defaultValue ?: static::$defaultValue;

                    // Do not set $lastPopulatedIndex
                    continue;
                }
                /*
                 * Non optional field, use the default value
                 */
                else {
                    $url[] = static::$defaultValue;
                }
            }

            $lastPopulatedIndex = $index;
        }

        /*
         * Trim the URL to only include populated segments
         */
        $url = array_slice($url, 0, $lastPopulatedIndex + 1);

        return RouterHelper::rebuildUrl($url);
    }
}
