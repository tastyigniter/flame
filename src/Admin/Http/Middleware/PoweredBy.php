<?php

namespace Igniter\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class PoweredBy
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (config('igniter.system.sendPoweredByHeader') && $response instanceof Response) {
            $response->header('X-Powered-By', 'TastyIgniter');
        }

        return $response;
    }
}
