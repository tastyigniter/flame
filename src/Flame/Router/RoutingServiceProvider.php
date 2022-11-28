<?php

namespace Igniter\Flame\Router;

use Igniter\Flame\Igniter;
use Igniter\Flame\Mixins\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RoutingServiceProvider extends ServiceProvider
{
    public function register()
    {
        Igniter::loadControllersFrom(igniter_path('src/Admin/Http/Controllers'), 'Igniter\\Admin\\Http\\Controllers');
        Igniter::loadControllersFrom(igniter_path('src/Main/Http/Controllers'), 'Igniter\\Main\\Http\\Controllers');
        Igniter::loadControllersFrom(igniter_path('src/System/Http/Controllers'), 'Igniter\\System\\Http\\Controllers');

        $this->registerMiddlewareGroups();
    }

    public function boot()
    {
        Route::mixin(new Router);
    }

    protected function registerMiddlewareGroups()
    {
        Route::middlewareGroup('igniter', config('igniter.routes.middleware', []));
        Route::middlewareGroup('igniter:admin', config('igniter.routes.adminMiddleware', []));
    }
}
