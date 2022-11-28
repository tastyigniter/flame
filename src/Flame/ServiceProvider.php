<?php

namespace Igniter\Flame;

use Igniter\Flame\Exception\ErrorHandler;
use Igniter\Flame\Filesystem\Filesystem;
use Igniter\Flame\Support\ClassLoader;
use Igniter\System\Classes\PackageManifest;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Router;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $root = __DIR__.'/../..';

    protected $providers = [
        \Igniter\Flame\ActivityLog\ActivityLogServiceProvider::class,
        \Igniter\Flame\Auth\AuthServiceProvider::class,
        \Igniter\Flame\Currency\CurrencyServiceProvider::class,
        \Igniter\Flame\Providers\ConsoleSupportServiceProvider::class,
        \Igniter\Flame\Database\DatabaseServiceProvider::class,
        \Igniter\Flame\Filesystem\FilesystemServiceProvider::class,
        \Igniter\Flame\Flash\FlashServiceProvider::class,
        \Igniter\Flame\Geolite\GeoliteServiceProvider::class,
        \Igniter\Flame\Html\HtmlServiceProvider::class,
        \Igniter\Flame\Mail\MailServiceProvider::class,
        \Igniter\Flame\Providers\MacroServiceProvider::class,
        \Igniter\Flame\Pagic\PagicServiceProvider::class,
        \Igniter\Flame\Router\RoutingServiceProvider::class,
        \Igniter\Flame\Scaffold\ScaffoldServiceProvider::class,
        \Igniter\Flame\Setting\SettingServiceProvider::class,
        \Igniter\Flame\Translation\TranslationServiceProvider::class,
        \Igniter\Flame\Providers\UrlServiceProvider::class,

        \Igniter\Flame\Providers\SystemServiceProvider::class,
    ];

    protected $configFiles = [
        'auth', 'currency', 'geocoder', 'routes', 'system',
    ];

    public function register()
    {
        $this->mergeConfigFiles();

        $this->loadTranslationsFrom($this->root.'/resources/lang/', 'igniter');

        $this->bindPathsInContainer();

        $this->app[\Illuminate\Contracts\Http\Kernel::class]
            ->pushMiddleware(\Igniter\Flame\Setting\Middleware\SaveSetting::class);

        $this->app->make(Router::class)->middlewareGroup('web', [
            \Igniter\Flame\Translation\Middleware\Localization::class,
        ]);

        $this->registerSingletons();
        $this->registerProviders();
        $this->registerFacadeAliases();
        $this->registerErrorHandler();
    }

    public function boot()
    {
        $this->publishConfigFiles();

        $this->publishes([
            $this->root.'/resources/lang' => lang_path('/vendor/igniter'),
        ], 'igniter-translations');
    }

    protected function mergeConfigFiles()
    {
        collect($this->configFiles)->each(function ($config) {
            $this->mergeConfigFrom($this->root.'/config/'.$config.'.php', 'igniter.'.$config);
        });
    }

    protected function publishConfigFiles()
    {
        collect($this->configFiles)->each(function ($config) {
            $this->publishes([$this->root.'/config/'.$config.'.php' => config_path('igniter/'.$config.'.php')], 'igniter');
        });
    }

    protected function registerProviders()
    {
        collect($this->providers)->each(function ($provider) {
            $this->app->register($provider);
        });
    }

    protected function registerSingletons()
    {
        $this->app->singleton(PackageManifest::class, function () {
            return new PackageManifest(
                new Filesystem,
                $this->app->basePath(),
                Igniter::getCachedAddonsPath()
            );
        });

        $this->app->instance(ClassLoader::class, $loader = new ClassLoader(
            new Filesystem,
            $this->app->basePath(),
            Igniter::getCachedClassesPath()
        ));

        $loader->register();
        $loader->addDirectories(['extensions']);

        $this->app['events']->listen(RouteMatched::class, function () use ($loader) {
            $loader->build();
        });
    }

    protected function registerFacadeAliases()
    {
        $loader = AliasLoader::getInstance();

        foreach ([
            'Flash' => \Igniter\Flame\Flash\Facades\Flash::class,
            'Form' => \Igniter\Flame\Html\FormFacade::class,
            'Html' => \Igniter\Flame\Html\HtmlFacade::class,
            'Model' => \Igniter\Flame\Database\Model::class,
            'Parameter' => \Igniter\Flame\Setting\Facades\Parameter::class,
            'Setting' => \Igniter\Flame\Setting\Facades\Setting::class,
            'SystemException' => \Igniter\Flame\Exception\SystemException::class,
            'ApplicationException' => \Igniter\Flame\Exception\ApplicationException::class,
            'AjaxException' => \Igniter\Flame\Exception\AjaxException::class,
            'ValidationException' => \Igniter\Flame\Exception\ValidationException::class,
        ] as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    /*
     * Error handling for uncaught Exceptions
     */
    protected function registerErrorHandler()
    {
        $this->callAfterResolving(ExceptionHandler::class, function ($handler) {
            new ErrorHandler($handler);
        });
    }

    protected function bindPathsInContainer()
    {
        $this->app->instance('path.themes', Igniter::themesPath());
        $this->app->instance('path.temp', Igniter::tempPath());
    }
}
