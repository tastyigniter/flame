<?php

namespace Igniter\Flame\Translation\Middleware;

use Closure;
use Illuminate\Http\Request;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Don't redirect the console
        if (app()->runningInConsole()) {
            return $next($request);
        }

        // Check for a user defined currency
        if (is_null($locale = $this->getUserLocale($request))) {
            $locale = $this->getDefaultLocale();
        }

        // Set user currency
        $this->setUserLocale($locale, $request);

        return $next($request);
    }

    /**
     * Get the user selected locale.
     *
     * @param Request $request
     *
     * @return string|null
     */
    protected function getUserLocale(Request $request)
    {
        $localization = app('translator.localization');

        // Check request for locale
        $params = explode('/', $request->path());
        if (isset($params[0]) AND $localization->isValid($params[0])) {
            return $params[0];
        }

        // Get locale from session
        $sessionLocale = $request->getSession()->get('igniter.flame.translation.locale');
        if ($sessionLocale AND $localization->isValid($sessionLocale)) {
            return $sessionLocale;
        }

        // Get locale from user browser
        $browserLocale = substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
        if ($browserLocale AND $localization->isValid($browserLocale)) {
            return $browserLocale;
        }

        return null;
    }

    /**
     * Get the application default locale.
     *
     * @return string
     */
    protected function getDefaultLocale()
    {
        return config('app.locale');
    }

    /**
     * Set the user locale.
     *
     * @param $locale
     * @param Request $request
     *
     * @return string
     */
    private function setUserLocale($locale, $request)
    {
        // Set user selection globally
        app()->setLocale($locale);

        // Save it for later too!
        $request->getSession()->put(['igniter.flame.translation.locale' => $locale]);

        return $locale;
    }
}