<?php

namespace Igniter\Admin\Helpers;

use Igniter\Flame\Exception\SystemException;
use Igniter\Flame\Igniter;
use Igniter\Flame\Support\RouterHelper;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;

/**
 * Admin Helper
 * @see \Igniter\Admin\Facades\Admin
 */
class Admin
{
    /**
     * Returns the admin URI segment.
     */
    public function uri()
    {
        return Igniter::uri();
    }

    /**
     * Generate an absolute URL in context of the Admin
     *
     * @param string $path
     * @param array $parameters
     * @param bool|null $secure
     *
     * @return string
     */
    public function url($path = null, $parameters = [], $secure = null)
    {
        return URL::to($this->uri().'/'.$path, $parameters, $secure);
    }

    /**
     * Returns the base admin URL from which this request is executed.
     *
     * @param string $path
     *
     * @return string
     */
    public function baseUrl($path = null)
    {
        $adminUri = $this->uri();
        $baseUrl = Request::getBaseUrl();

        if ($path === null) {
            return $baseUrl.'/'.$adminUri;
        }

        $path = RouterHelper::normalizeUrl($path);

        return $baseUrl.'/'.$adminUri.$path;
    }

    /**
     * Create a new redirect response to a given admin path.
     *
     * @param string $path
     * @param int $status
     * @param array $headers
     * @param bool|null $secure
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect($path, $status = 302, $headers = [], $secure = null)
    {
        return Redirect::to($this->uri().'/'.$path, $status, $headers, $secure);
    }

    /**
     * Create a new admin redirect response, while putting the current URL in the session.
     *
     * @param $path
     * @param int $status
     * @param array $headers
     * @param null $secure
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectGuest($path, $status = 302, $headers = [], $secure = null)
    {
        return Redirect::guest($this->uri().'/'.$path, $status, $headers, $secure);
    }

    /**
     * Create a new redirect response to the previously intended admin location.
     *
     * @param $path
     * @param int $status
     * @param array $headers
     * @param null $secure
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectIntended($path = '/', $status = 302, $headers = [], $secure = null)
    {
        $path = $path == '/' ? $path : '/'.$path;

        return Redirect::intended($this->uri().$path, $status, $headers, $secure);
    }

    public function hasAjaxHandler()
    {
        return !empty(request()->header('X-IGNITER-REQUEST-HANDLER'));
    }

    /**
     * Returns the AJAX handler for the current request, if available.
     * @return string
     */
    public function getAjaxHandler()
    {
        if (request()->ajax() && $handler = request()->header('X-IGNITER-REQUEST-HANDLER'))
            return trim($handler);

        if ($handler = post('_handler'))
            return trim($handler);

        return null;
    }

    public function validateAjaxHandler($handler)
    {
        if (!preg_match('/^(?:\w+\:{2})?on[A-Z]{1}[\w+]*$/', $handler)) {
            throw new SystemException(sprintf(lang('igniter::admin.alert_invalid_ajax_handler_name'), $handler));
        }
    }

    public function validateAjaxHandlerPartials()
    {
        if (!$partials = trim(request()->header('X-IGNITER-REQUEST-PARTIALS', '')))
            return [];

        $partials = explode('&', $partials);

        foreach ($partials as $partial) {
            if (!preg_match('/^(?:\w+\:{2}|@)?[a-z0-9\_\-\.\/]+$/i', $partial)) {
                throw new SystemException(sprintf(lang('igniter::admin.alert_invalid_ajax_partial_name'), $partial));
            }
        }

        return $partials;
    }
}
