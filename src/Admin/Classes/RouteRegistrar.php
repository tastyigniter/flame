<?php

namespace Igniter\Admin\Classes;

use Igniter\Admin\Http\Controllers\Login;
use Igniter\Flame\Igniter;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

class RouteRegistrar
{
    protected $router;

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
        $this->forAdminPages();
    }

    public function forAssets()
    {
        $this->router
            ->namespace('Igniter\System\Http\Controllers')
            ->middleware(config('igniter.routes.middleware'))
            ->domain(config('igniter.routes.domain'))
            ->prefix(Igniter::uri())
            ->name('igniter.admin.assets')
            ->group(function (Router $router) {
                $uri = config('igniter.routes.assetsCombinerUri', '_assets').'/{asset}';
                $router->get($uri, 'AssetController');
            });
    }

    public function forAdminPages()
    {
        $this->router
            ->middleware(config('igniter.routes.middleware'))
            ->domain(config('igniter.routes.domain'))
            ->prefix(Igniter::uri())
            ->group(function (Router $router) {
                $router->any('/login', [Login::class, 'index'])->name('igniter.admin.login');
                $router->any('/login/reset/{slug?}', [Login::class, 'reset'])->name('igniter.admin.reset');
            });

        $this->router
            ->middleware(config('igniter.routes.adminMiddleware'))
            ->domain(config('igniter.routes.domain'))
            ->prefix(Igniter::uri())
            ->group(function (Router $router) {
                foreach ($this->getAdminPages() as $class) {
                    [$name, $uri] = $this->guessRouteUri($class);

                    $router->name($name)->group(function (Router $router) use ($uri, $class) {
                        $router->any('/'.$uri.'/{slug?}', [$class, 'remap'])->where('slug', '(.*)?');
                    });
                }
            });
    }

    protected function getAdminPages()
    {
        return collect(Igniter::controllerPath())
            ->flatMap(function ($path, $namespace) {
                $result = [];
                foreach (File::allFiles($path) as $file) {
                    $result[] = (string)Str::of($namespace)
                        ->append('\\', $file->getRelativePathname())
                        ->replace(['/', '.php'], ['\\', '']);
                }

                return $result;
            })
            ->filter(fn ($class) => $this->isAdminPage($class));
    }

    protected function isAdminPage($class)
    {
        return is_subclass_of($class, AdminController::class) && !(new ReflectionClass($class))->isAbstract();
    }

    protected function guessRouteUri($class)
    {
        if (Str::startsWith($class, config('igniter.routes.coreNamespaces', []))) {
            $uri = strtolower($resource = snake_case(class_basename($class)));
            $name = strtolower(implode('.', array_slice(explode('\\', $class), 0, 2)).'.'.$resource);

            return [$name, $uri];
        }

        $resource = snake_case(class_basename($class));
        $uri = strtolower(implode('/', array_slice(explode('\\', $class), 0, 2)).'/'.$resource);
        $name = str_replace('/', '.', $uri);

        return [$name, $uri];
    }
}
