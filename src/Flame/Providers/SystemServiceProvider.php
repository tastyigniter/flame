<?php

namespace Igniter\Flame\Providers;

use Igniter\Admin\Classes\PermissionManager;
use Igniter\Flame\Flash\FlashBag;
use Igniter\Flame\Igniter;
use Igniter\Flame\Setting\Facades\Setting;
use Igniter\Flame\Translation\Drivers\Database;
use Igniter\System\Classes;
use Igniter\System\Console;
use Igniter\System\Exception\ErrorHandler;
use Igniter\System\Libraries;
use Igniter\System\Models\Settings;
use Igniter\System\Template\Extension\BladeExtension;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class SystemServiceProvider extends AppServiceProvider
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerSingletons();
        $this->registerFacadeAliases();

        // Register all extensions
        resolve(Classes\ExtensionManager::class)->registerExtensions();

        $this->registerSchedule();
        $this->registerConsole();
        $this->registerErrorHandler();
        $this->registerMailer();
        $this->registerPaginator();
        $this->registerPermissions();
        $this->registerSystemSettings();
        $this->registerBladeDirectives();
    }

    /**
     * Bootstrap the module events.
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom($this->root.'/resources/views/system', 'igniter.system');
        $this->loadAnonymousComponentFrom('igniter.system::_components.', 'igniter.system');
        $this->loadResourcesFrom($this->root.'/resources', 'igniter');

        $this->defineEloquentMorphMaps();
        $this->resolveFlashSessionKey();

        resolve(Classes\ExtensionManager::class)->bootExtensions();

        $this->updateTimezone();
        $this->setConfiguration();
        $this->extendValidator();
        $this->addTranslationDriver();
        $this->defineQueryMacro();
    }

    protected function updateTimezone()
    {
        date_default_timezone_set(Setting::get('timezone', Config::get('app.timezone', 'UTC')));
    }

    /**
     * Register singletons
     */
    protected function registerSingletons()
    {
        $this->app->singleton('assets', function () {
            return new Libraries\Assets();
        });

        $this->app->singleton('country', function ($app) {
            return new Libraries\Country;
        });

        $this->app->instance('path.uploads', base_path(Config::get('igniter.system.assets.media.path', 'assets/media/uploads')));

        $this->app->singleton(Classes\ComponentManager::class);
        $this->tapSingleton(Classes\ComposerManager::class);
        $this->tapSingleton(Classes\ExtensionManager::class);
        $this->tapSingleton(Classes\HubManager::class);
        $this->tapSingleton(Classes\LanguageManager::class);
        $this->app->singleton(Classes\MailManager::class);
        $this->tapSingleton(Classes\UpdateManager::class);
    }

    protected function registerFacadeAliases()
    {
        $loader = AliasLoader::getInstance();

        foreach ([
            'Assets' => \Igniter\System\Facades\Assets::class,
            'Country' => \Igniter\System\Facades\Country::class,
        ] as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    /**
     * Register command line specifics
     */
    protected function registerConsole()
    {
        // Allow extensions to use the scheduler
        Event::listen('console.schedule', function ($schedule) {
            $extensions = resolve(Classes\ExtensionManager::class)->getExtensions();
            foreach ($extensions as $extension) {
                if (method_exists($extension, 'registerSchedule')) {
                    $extension->registerSchedule($schedule);
                }
            }
        });

        // Allow system based cache clearing
        Event::listen('cache:cleared', function () {
            \Igniter\System\Helpers\CacheHelper::clearInternal();
        });

        Event::listen(\Illuminate\Console\Events\CommandFinished::class, function ($event) {
            if ($event->command === 'clear-compiled')
                \Igniter\System\Helpers\CacheHelper::clearCompiled();
        });

        foreach (
            [
                'igniter.util' => Console\Commands\IgniterUtil::class,
                'igniter.up' => Console\Commands\IgniterUp::class,
                'igniter.down' => Console\Commands\IgniterDown::class,
                'igniter.package-discover' => Console\Commands\IgniterPackageDiscover::class,
                'igniter.install' => Console\Commands\IgniterInstall::class,
                'igniter.update' => Console\Commands\IgniterUpdate::class,
                'igniter.passwd' => Console\Commands\IgniterPasswd::class,
                'extension.install' => Console\Commands\ExtensionInstall::class,
                'extension.refresh' => Console\Commands\ExtensionRefresh::class,
                'extension.remove' => Console\Commands\ExtensionRemove::class,
                'theme.install' => Console\Commands\ThemeInstall::class,
                'theme.remove' => Console\Commands\ThemeRemove::class,
                'theme.vendor-publish' => Console\Commands\ThemeVendorPublish::class,
            ] as $command => $class
        ) {
            $this->registerConsoleCommand($command, $class);
        }
    }

    /*
     * Error handling for uncaught Exceptions
     */
    protected function registerErrorHandler()
    {
        Event::listen('exception.beforeRender', function ($exception, $httpCode, $request) {
            if ($result = (new ErrorHandler)->handleException($exception))
                return $result;
        });
    }

    /**
     * Extends the validator with custom rules
     */
    protected function extendValidator()
    {
        Validator::extend('trim', function ($attribute, $value, $parameters, $validator) {
            return trim($value);
        });

        Validator::extend('valid_date', function ($attribute, $value, $parameters, $validator) {
            return !(!preg_match('/^(0[1-9]|[1-2][0-9]|3[0-1])-(0[1-9]|1[0-2])-[0-9]{4}$/', $value)
                && !preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/', $value));
        });

        Validator::extend('valid_time', function ($attribute, $value, $parameters, $validator) {
            return !(!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)
                && !preg_match('/^(1[012]|[1-9]):[0-5][0-9](\s)?(?i)(am|pm)$/', $value));
        });
    }

    protected function registerMailer()
    {
        resolve(Classes\MailManager::class)->registerCallback(function (Classes\MailManager $manager) {
            $manager->registerMailLayouts([
                'default' => 'igniter.system::_mail.layouts.default',
            ]);

            $manager->registerMailPartials([
                'header' => 'igniter.system::_mail.partials.header',
                'footer' => 'igniter.system::_mail.partials.footer',
                'button' => 'igniter.system::_mail.partials.button',
                'panel' => 'igniter.system::_mail.partials.panel',
                'table' => 'igniter.system::_mail.partials.table',
                'subcopy' => 'igniter.system::_mail.partials.subcopy',
                'promotion' => 'igniter.system::_mail.partials.promotion',
            ]);

            $manager->registerMailVariables(
                File::getRequire(File::symbolizePath('igniter::models/system/mailvariables.php'))
            );
        });

        Event::listen('mailer.beforeRegister', function () {
            resolve(Classes\MailManager::class)->applyMailerConfigValues();
        });
    }

    protected function registerPaginator()
    {
        Paginator::useBootstrap();

        Paginator::defaultView('igniter.system::_partials/pagination/default');
        Paginator::defaultSimpleView('igniter.system::_partials/pagination/simple_default');

        Paginator::currentPathResolver(function () {
            return url()->current();
        });

        Paginator::currentPageResolver(function ($pageName = 'page') {
            $page = Request::get($pageName);
            if (filter_var($page, FILTER_VALIDATE_INT) !== false && (int)$page >= 1) {
                return $page;
            }

            return 1;
        });
    }

    protected function addTranslationDriver()
    {
        if (Igniter::hasDatabase()) {
            $this->app['translation.loader']->addDriver(Database::class);
        }
    }

    protected function setConfiguration()
    {
        Event::listen('currency.beforeRegister', function () {
            app('config')->set('currency.default', setting('default_currency_code'));
            app('config')->set('currency.converter', setting('currency_converter.api', 'openexchangerates'));
            app('config')->set('currency.converters.openexchangerates.apiKey', setting('currency_converter.oer.apiKey'));
            app('config')->set('currency.converters.fixerio.apiKey', setting('currency_converter.fixerio.apiKey'));
            app('config')->set('currency.ratesCacheDuration', setting('currency_converter.refreshInterval'));
            app('config')->set('currency.model', \Igniter\System\Models\Currency::class);
        });

        $this->app->resolving('translator.localization', function ($localization, $app) {
            $app['config']->set('localization.locale', setting('default_language', $app['config']['app.locale']));
            $app['config']->set('localization.supportedLocales', setting('supported_languages', []) ?: ['en']);
            $app['config']->set('localization.detectBrowserLocale', (bool)setting('detect_language', false));
        });

        $this->app->resolving('geocoder', function ($geocoder, $app) {
            $app['config']->set('geocoder.default', setting('default_geocoder'));

            $region = $app['country']->getCountryCodeById(setting('country_id'));
            $app['config']->set('geocoder.providers.google.region', $region);
            $app['config']->set('geocoder.providers.nominatim.region', $region);

            $app['config']->set('geocoder.providers.google.apiKey', setting('maps_api_key'));
            $app['config']->set('geocoder.precision', setting('geocoder_boundary_precision', 8));
        });

        Event::listen(CommandStarting::class, function () {
            config()->set('system.activityRecordsTTL', (int)setting('activity_log_timeout', 60));
        });
    }

    protected function defineEloquentMorphMaps()
    {
        Relation::morphMap([
            'activities' => \Igniter\System\Models\Activity::class,
            'countries' => \Igniter\System\Models\Country::class,
            'currencies' => \Igniter\System\Models\Currency::class,
            'extensions' => \Igniter\System\Models\Extension::class,
            'languages' => \Igniter\System\Models\Language::class,
            'mail_layouts' => \Igniter\System\Models\MailLayout::class,
            'mail_templates' => \Igniter\System\Models\MailTemplate::class,
            'pages' => \Igniter\System\Models\Page::class,
            'settings' => \Igniter\System\Models\Settings::class,
            'themes' => \Igniter\Main\Models\Theme::class,
        ]);
    }

    protected function defineQueryMacro()
    {
        \Illuminate\Database\Query\Builder::macro('toRawSql', function () {
            return array_reduce($this->getBindings(), function ($sql, $binding) {
                return preg_replace('/\?/', is_numeric($binding) ? $binding : "'".$binding."'", $sql, 1);
            }, $this->toSql());
        });

        \Illuminate\Database\Eloquent\Builder::macro('toRawSql', function () {
            return $this->getQuery()->toRawSql();
        });
    }

    protected function registerSchedule()
    {
        Event::listen('console.schedule', function (Schedule $schedule) {
            // Check for system updates every 12 hours
            $schedule->call(function () {
                resolve(Classes\UpdateManager::class)->requestUpdateList(true);
            })->name('System Updates Checker')->cron('0 */12 * * *')->evenInMaintenanceMode();

            // Cleanup activity log
            $schedule->command('activitylog:cleanup')->name('Activity Log Cleanup')->daily();
        });
    }

    protected function registerPermissions()
    {
        resolve(PermissionManager::class)->registerCallback(function ($manager) {
            $manager->registerPermissions('System', [
                'Admin.Activities' => [
                    'label' => 'igniter::system.permissions.activities', 'group' => 'igniter::system.permissions.name',
                ],
                'Admin.Extensions' => [
                    'label' => 'igniter::system.permissions.extensions', 'group' => 'igniter::system.permissions.name',
                ],
                'Admin.MailTemplates' => [
                    'label' => 'igniter::system.permissions.mail_templates', 'group' => 'igniter::system.permissions.name',
                ],
                'Site.Countries' => [
                    'label' => 'igniter::system.permissions.countries', 'group' => 'igniter::system.permissions.name',
                ],
                'Site.Currencies' => [
                    'label' => 'igniter::system.permissions.currencies', 'group' => 'igniter::system.permissions.name',
                ],
                'Site.Languages' => [
                    'label' => 'igniter::system.permissions.languages', 'group' => 'igniter::system.permissions.name',
                ],
                'Site.Settings' => [
                    'label' => 'igniter::system.permissions.settings', 'group' => 'igniter::system.permissions.name',
                ],
                'Site.Updates' => [
                    'label' => 'igniter::system.permissions.updates', 'group' => 'igniter::system.permissions.name',
                ],
                'Admin.SystemLogs' => [
                    'label' => 'igniter::system.permissions.system_logs', 'group' => 'igniter::system.permissions.name',
                ],
            ]);
        });
    }

    protected function registerSystemSettings()
    {
        Settings::registerCallback(function (Settings $manager) {
            $manager->registerSettingItems('core', [
                'general' => [
                    'label' => 'igniter::system.settings.text_tab_general',
                    'description' => 'igniter::system.settings.text_tab_desc_general',
                    'icon' => 'fa fa-sliders',
                    'priority' => 0,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/general'),
                    'form' => 'generalsettings',
                    'request' => \Igniter\System\Requests\GeneralSettings::class,
                ],
                'site' => [
                    'label' => 'igniter::system.settings.text_tab_site',
                    'description' => 'igniter::system.settings.text_tab_desc_site',
                    'icon' => 'fa fa-globe',
                    'priority' => 2,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/site'),
                    'form' => 'sitesettings',
                    'request' => 'Igniter\System\Requests\SiteSettings',
                ],
                'mail' => [
                    'label' => 'lang:igniter::system.settings.text_tab_mail',
                    'description' => 'lang:igniter::system.settings.text_tab_desc_mail',
                    'icon' => 'fa fa-envelope',
                    'priority' => 4,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/mail'),
                    'form' => 'mailsettings',
                    'request' => \Igniter\System\Requests\MailSettings::class,
                ],
                'advanced' => [
                    'label' => 'lang:igniter::system.settings.text_tab_server',
                    'description' => 'lang:igniter::system.settings.text_tab_desc_server',
                    'icon' => 'fa fa-cog',
                    'priority' => 7,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/advanced'),
                    'form' => 'advancedsettings',
                    'request' => \Igniter\System\Requests\AdvancedSettings::class,
                ],
            ]);
        });
    }

    protected function registerBladeDirectives()
    {
        $this->callAfterResolving('blade.compiler', function ($compiler, $app) {
            (new BladeExtension)->register();
        });
    }

    protected function resolveFlashSessionKey()
    {
        $this->app->resolving('flash', function (FlashBag $flash) {
            $flash->setSessionKey(Igniter::runningInAdmin() ? 'flash_data_admin' : 'flash_data_main');
        });
    }
}
