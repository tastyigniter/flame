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
        // Check for a user defined language and set user language
        if (!app()->runningInConsole()) {
            app('translator.localization')->loadLocale();
        }

        return $next($request);
    }
}