<?php

namespace Igniter\Main\Classes;

use Illuminate\Routing\Router;

class RouteRegistrar
{
    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Register routes for admin and frontend.
     *
     * @return void
     */
    public function all()
    {
        $this->forAssets();
        $this->forThemePages();
    }

    public function forAssets()
    {
        $this->router
            ->namespace('Igniter\System\Http\Controllers')
            ->middleware(config('igniter.routes.middleware'))
            ->domain(config('igniter.routes.domain'))
            ->name('igniter.main.assets')
            ->group(function (Router $router) {
                $uri = config('igniter.routes.assetsCombinerUri', '_assets').'/{asset}';
                $router->get($uri, 'AssetController');
            });
    }

    public function forThemePages()
    {
        $this->router
            ->middleware(config('igniter.routes.middleware'))
            ->domain(config('igniter.routes.domain'))
            ->name('igniter.theme.')
            ->group(function (Router $router) {
                foreach ($this->getThemePageRoutes() as $parts) {
                    $route = $router->pagic($parts['uri'], $parts['route'])
                        ->defaults('_file_', $parts['file']);

                    foreach ($parts['defaults'] ?? [] as $key => $value)
                        $route->defaults($key, $value);

                    foreach ($parts['constraints'] ?? [] as $key => $value)
                        $route->where($key, $value);
                }
            });
    }

    protected function getThemePageRoutes()
    {
        return resolve(\Igniter\Main\Classes\Router::class)->getRouteMap();
    }
}
