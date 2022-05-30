<?php

namespace Igniter\Flame\Providers;

use Igniter\Admin\Classes\PermissionManager;
use Igniter\Admin\Classes\Widgets;
use Igniter\Flame\Igniter;
use Igniter\Flame\Pagic\Cache\FileSystem as FileCache;
use Igniter\Flame\Pagic\Environment;
use Igniter\Flame\Pagic\Loader;
use Igniter\Flame\Pagic\Parsers\FileParser;
use Igniter\Flame\Setting\Facades\Setting;
use Igniter\Main\Classes\Router;
use Igniter\Main\Classes\RouteRegistrar;
use Igniter\Main\Classes\Theme;
use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;
use Igniter\System\Classes\ComponentManager;
use Igniter\System\Libraries\Assets;
use Igniter\System\Models\Settings;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class MainServiceProvider extends AppServiceProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom($this->root.'/resources/views/main', 'igniter.main');

        View::share('site_name', Setting::get('site_name'));
        View::share('site_logo', Setting::get('site_logo'));

        resolve(ThemeManager::class)->bootThemes();

        $this->bootMenuItemEvents();
        $this->defineRoutes();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSingletons();
        $this->registerFacadeAliases();
        $this->registerComponents();

        if (!Igniter::runningInAdmin()) {
            $this->registerAssets();
            $this->registerPagicParser();
            $this->registerCombinerEvent();
        }
        else {
            $this->registerFormWidgets();
            $this->registerPermissions();
            $this->registerSystemSettings();
        }
    }

    /**
     * Register components.
     */
    protected function registerComponents()
    {
        ComponentManager::instance()->registerComponents(function ($manager) {
            $manager->registerComponent(\Igniter\Main\Components\ViewBag::class, 'viewBag');
        });
    }

    protected function registerSingletons()
    {
        App::singleton('main.auth', function () {
            return resolve('auth')->guard(config('igniter.auth.guards.web', 'web'));
        });

        App::singleton(ThemeManager::class, function () {
            return tap(new ThemeManager, function ($manager) {
                $manager->initialize();
            });
        });

        $this->app->when(Router::class)
            ->needs(Theme::class)
            ->give(function () {
                return $this->app[ThemeManager::class]->getActiveTheme();
            });
    }

    protected function registerFacadeAliases()
    {
        $loader = AliasLoader::getInstance();

        foreach ([
            'Auth' => \Igniter\Main\Facades\Auth::class,
        ] as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    protected function registerAssets()
    {
        Assets::registerCallback(function (Assets $manager) {
            if (Igniter::runningInAdmin())
                return;

            $manager->registerSourcePath(Igniter::themesPath());

            resolve(ThemeManager::class)->addAssetsFromActiveThemeManifest($manager);
        });
    }

    protected function registerCombinerEvent()
    {
        if ($this->app->runningInConsole() || Igniter::runningInAdmin())
            return;

        Event::listen('assets.combiner.beforePrepare', function (Assets $combiner, $assets) {
            resolve(ThemeManager::class)->applyAssetVariablesOnCombinerFilters(
                array_flatten($combiner->getFilters())
            );
        });
    }

    /**
     * Registers events for menu items.
     */
    protected function bootMenuItemEvents()
    {
        Event::listen('pages.menuitem.listTypes', function () {
            return [
                'theme-page' => 'igniter::main.pages.text_theme_page',
            ];
        });

        Event::listen('pages.menuitem.getTypeInfo', function ($type) {
            return Page::getMenuTypeInfo($type);
        });

        Event::listen('pages.menuitem.resolveItem', function ($item, $url, $theme) {
            if ($item->type == 'theme-page')
                return Page::resolveMenuItem($item, $url, $theme);
        });
    }

    protected function registerFormWidgets()
    {
        Widgets::instance()->registerFormWidgets(function (Widgets $manager) {
            $manager->registerFormWidget(\Igniter\Main\FormWidgets\Components::class, [
                'label' => 'Components',
                'code' => 'components',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\MapArea::class, [
                'label' => 'Map Area',
                'code' => 'maparea',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\MapView::class, [
                'label' => 'Map View',
                'code' => 'mapview',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\MediaFinder::class, [
                'label' => 'Media finder',
                'code' => 'mediafinder',
            ]);

            $manager->registerFormWidget(\Igniter\Main\FormWidgets\TemplateEditor::class, [
                'label' => 'Template editor',
                'code' => 'templateeditor',
            ]);
        });
    }

    protected function registerPermissions()
    {
        PermissionManager::instance()->registerCallback(function ($manager) {
            $manager->registerPermissions('System', [
                'Admin.MediaManager' => [
                    'label' => 'igniter::main.permissions.media_manager', 'group' => 'igniter::main.permissions.name',
                ],
                'Site.Themes' => [
                    'label' => 'igniter::main.permissions.themes', 'group' => 'igniter::main.permissions.name',
                ],
            ]);
        });
    }

    protected function registerSystemSettings()
    {
        Settings::registerCallback(function (Settings $manager) {
            $manager->registerSettingItems('core', [
                'media' => [
                    'label' => 'igniter::main.settings.text_tab_media_manager',
                    'description' => 'igniter::main.settings.text_tab_desc_media_manager',
                    'icon' => 'fa fa-image',
                    'priority' => 5,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/media'),
                    'form' => 'igniter::models/main/mediasettings',
                    'request' => \Igniter\Main\Requests\MediaSettings::class,
                ],
            ]);
        });
    }

    protected function registerPagicParser()
    {
        FileParser::setCache(new FileCache(config('igniter.system.parsedTemplateCachePath')));

        App::singleton('pagic.environment', function () {
            return new Environment(new Loader, [
                'cache' => new FileCache(config('view.compiled')),
            ]);
        });
    }

    protected function defineRoutes()
    {
        if (app()->routesAreCached())
            return;

        Route::group([], function ($router) {
            (new RouteRegistrar($router))->all();
        });
    }
}
