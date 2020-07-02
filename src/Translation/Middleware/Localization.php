<?php

namespace Igniter\Flame\Translation\Middleware;

use Closure;
use Illuminate\Http\Request;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $localization = app('translator.localization');

        if (app()->runningInAdmin()) {
            if (!$localization->loadLocaleFromSession()) {
                $staff = app('admin.auth')->isLogged() ? app('admin.auth')->staff() : null;
                if ($staff AND $staffLocale = $staff->language) {
                    $localization->setLocale($staffLocale->code, TRUE);
                }
            }
        }
        else {
            if (!$localization->loadLocaleFromRequest()) {
                if (!$localization->loadLocaleFromBrowser()) {
                    if (!$localization->loadLocaleFromSession()) {
                        $localization->setLocale($localization->getDefaultLocale());
                    }
                }
            }
        }

        return $next($request);
    }
}