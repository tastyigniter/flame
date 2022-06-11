<?php

namespace Igniter\Flame\Providers;

use Igniter\Admin\ActivityTypes;
use Igniter\Admin\Classes;
use Igniter\Admin\Facades\AdminLocation;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Helpers\Admin as AdminHelper;
use Igniter\Admin\Models\Order;
use Igniter\Admin\Models\Reservation;
use Igniter\Admin\Requests\Location;
use Igniter\Flame\ActivityLog\Models\Activity;
use Igniter\Flame\Igniter;
use Igniter\System\Classes\MailManager;
use Igniter\System\Libraries\Assets;
use Igniter\System\Models\Settings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

class AdminServiceProvider extends AppServiceProvider
{
    /**
     * Bootstrap the service provider.
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom($this->root.'/resources/views/admin', 'igniter.admin');
        $this->loadAnonymousComponentFrom('igniter.admin::_components.', 'igniter.admin');

        $this->publishes([
            $this->root.'/public' => public_path('vendor/igniter'),
        ], ['igniter-assets', 'laravel-assets']);

        $this->defineRoutes();

        $this->defineEloquentMorphMaps();

        if (Igniter::runningInAdmin()) {
            $this->replaceNavMenuItem();
            $this->extendLocationOptionsFields();
        }
    }

    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->registerSingletons();
        $this->registerFacadeAliases();

        $this->registerActivityTypes();
        $this->registerMailTemplates();
        $this->registerSchedule();

        $this->registerSystemSettings();
        $this->registerPermissions();

        if (Igniter::runningInAdmin()) {
            $this->registerAssets();
            $this->registerDashboardWidgets();
            $this->registerBulkActionWidgets();
            $this->registerFormWidgets();
            $this->registerMainMenuItems();
            $this->registerNavMenuItems();
            $this->registerOnboardingSteps();
        }
    }

    /**
     * Register singletons
     */
    protected function registerSingletons()
    {
        $this->app->singleton('admin.helper', function () {
            return new AdminHelper;
        });

        $this->app->singleton('admin.auth', function () {
            return resolve('auth')->guard(config('igniter.auth.guards.admin', 'web'));
        });

        $this->app->singleton('admin.menu', function ($app) {
            return new Classes\Navigation('igniter.admin::_partials');
        });

        $this->app->singleton('admin.template', function ($app) {
            return new Classes\Template;
        });

        $this->app->singleton('admin.location', function ($app) {
            return new \Igniter\Admin\Classes\Location;
        });

        $this->app->singleton(Classes\OnboardingSteps::class);
        $this->app->singleton(Classes\PaymentGateways::class);
        $this->app->singleton(Classes\PermissionManager::class);
        $this->app->singleton(Classes\Widgets::class);
    }

    protected function registerFacadeAliases()
    {
        $loader = AliasLoader::getInstance();

        foreach ([
            'Admin' => \Igniter\Admin\Facades\Admin::class,
            'AdminAuth' => \Igniter\Admin\Facades\AdminAuth::class,
            'AdminLocation' => \Igniter\Admin\Facades\AdminLocation::class,
            'AdminMenu' => \Igniter\Admin\Facades\AdminMenu::class,
            'Template' => \Igniter\Admin\Facades\Template::class,
        ] as $alias => $class) {
            $loader->alias($alias, $class);
        }
    }

    protected function registerMailTemplates()
    {
        resolve(MailManager::class)->registerCallback(function (MailManager $manager) {
            $manager->registerMailTemplates([
                'igniter.admin::_mail.order_update' => 'lang:igniter::system.mail_templates.text_order_update',
                'igniter.admin::_mail.reservation_update' => 'lang:igniter::system.mail_templates.text_reservation_update',
                'igniter.admin::_mail.password_reset' => 'lang:igniter::system.mail_templates.text_password_reset_alert',
                'igniter.admin::_mail.password_reset_request' => 'lang:igniter::system.mail_templates.text_password_reset_request_alert',
            ]);
        });

        Event::listen('mail.templates.getDummyData', function ($templateCode) {
            return match ($templateCode) {
                'igniter.admin::_mail.order_update' => optional(Order::first())->mailGetData(),
                'igniter.admin::_mail.reservation_update' => optional(Reservation::first())->mailGetData(),
                'igniter.admin::_mail.password_reset' => ['staff_name' => 'Staff name'],
                'igniter.admin::_mail.password_reset_request' => [
                    'staff_name' => 'Staff name',
                    'reset_link' => admin_url('login/reset?code='),
                ],
            };
        });
    }

    protected function registerAssets()
    {
        Assets::registerCallback(function (Assets $manager) {
            $manager->registerSourcePath(public_path('vendor/igniter'));

            $manager->addFromManifest($this->root.'/resources/views/admin/_meta/assets.json', 'admin');
        });
    }

    /*
     * Register dashboard widgets
     */
    protected function registerDashboardWidgets()
    {
        resolve(Classes\Widgets::class)->registerDashboardWidgets(function (Classes\Widgets $manager) {
            $manager->registerDashboardWidget(\Igniter\System\DashboardWidgets\Activities::class, [
                'label' => 'Recent activities',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Igniter\System\DashboardWidgets\Cache::class, [
                'label' => 'Cache Usage',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Igniter\System\DashboardWidgets\News::class, [
                'label' => 'Latest News',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Igniter\Admin\DashboardWidgets\Statistics::class, [
                'label' => 'Statistics widget',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Igniter\Admin\DashboardWidgets\Onboarding::class, [
                'label' => 'Onboarding widget',
                'context' => 'dashboard',
            ]);

            $manager->registerDashboardWidget(\Igniter\Admin\DashboardWidgets\Charts::class, [
                'label' => 'Charts widget',
                'context' => 'dashboard',
            ]);
        });
    }

    protected function registerBulkActionWidgets()
    {
        resolve(Classes\Widgets::class)->registerBulkActionWidgets(function (Classes\Widgets $manager) {
            $manager->registerBulkActionWidget(\Igniter\Admin\BulkActionWidgets\Status::class, [
                'code' => 'status',
            ]);

            $manager->registerBulkActionWidget(\Igniter\Admin\BulkActionWidgets\Delete::class, [
                'code' => 'delete',
            ]);
        });
    }

    /**
     * Register widgets
     */
    protected function registerFormWidgets()
    {
        resolve(Classes\Widgets::class)->registerFormWidgets(function (Classes\Widgets $manager) {
            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\CodeEditor::class, [
                'label' => 'Code editor',
                'code' => 'codeeditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\ColorPicker::class, [
                'label' => 'Color picker',
                'code' => 'colorpicker',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\Connector::class, [
                'label' => 'Connector',
                'code' => 'connector',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\DataTable::class, [
                'label' => 'Data Table',
                'code' => 'datatable',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\DatePicker::class, [
                'label' => 'Date picker',
                'code' => 'datepicker',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\MarkdownEditor::class, [
                'label' => 'Markdown Editor',
                'code' => 'markdowneditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\MenuOptionEditor::class, [
                'label' => 'Menu Option Editor',
                'code' => 'menuoptioneditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\PermissionEditor::class, [
                'label' => 'Permission Editor',
                'code' => 'permissioneditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\RecordEditor::class, [
                'label' => 'Record Editor',
                'code' => 'recordeditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\Relation::class, [
                'label' => 'Relationship',
                'code' => 'relation',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\Repeater::class, [
                'label' => 'Repeater',
                'code' => 'repeater',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\RichEditor::class, [
                'label' => 'Rich editor',
                'code' => 'richeditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\StatusEditor::class, [
                'label' => 'Status Editor',
                'code' => 'statuseditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\ScheduleEditor::class, [
                'label' => 'Schedule Editor',
                'code' => 'scheduleeditor',
            ]);

            $manager->registerFormWidget(\Igniter\Admin\FormWidgets\StockEditor::class, [
                'label' => 'Stock Editor',
                'code' => 'stockeditor',
            ]);
        });
    }

    /**
     * Register admin top menu navigation items
     */
    protected function registerMainMenuItems()
    {
        AdminMenu::registerCallback(function (Classes\Navigation $manager) {
            $menuItems = [
                'preview' => [
                    'icon' => 'fa-store',
                    'attributes' => [
                        'class' => 'nav-link front-end',
                        'title' => 'lang:igniter::admin.side_menu.storefront',
                        'href' => root_url(),
                        'target' => '_blank',
                    ],
                ],
                'activity' => [
                    'label' => 'lang:igniter::admin.text_activity_title',
                    'icon' => 'fa-bell',
                    'badge' => 'badge-danger',
                    'type' => 'dropdown',
                    'badgeCount' => [\Igniter\System\Models\Activity::class, 'unreadCount'],
                    'markAsRead' => [\Igniter\System\Models\Activity::class, 'markAllAsRead'],
                    'options' => [\Igniter\System\Models\Activity::class, 'listMenuActivities'],
                    'partial' => 'activities.latest',
                    'viewMoreUrl' => admin_url('activities'),
                    'permission' => 'Admin.Activities',
                    'attributes' => [
                        'class' => 'nav-link',
                        'href' => '',
                        'data-bs-toggle' => 'dropdown',
                        'data-bs-auto-close' => 'outside',
                    ],
                ],
                'settings' => [
                    'type' => 'partial',
                    'path' => 'settings_menu',
                    'badgeCount' => [\Igniter\System\Models\Settings::class, 'updatesCount'],
                    'options' => [\Igniter\System\Models\Settings::class, 'listMenuSettingItems'],
                    'permission' => 'Site.Settings',
                ],
                'locations' => [
                    'type' => 'partial',
                    'path' => 'locations/picker',
                    'options' => ['Igniter\Admin\Classes\UserPanel', 'listLocations'],
                ],
                'user' => [
                    'type' => 'partial',
                    'path' => 'user_menu',
                    'options' => [\Igniter\Admin\Classes\UserPanel::class, 'listMenuLinks'],
                ],
            ];

            if (AdminLocation::listLocations()->isEmpty())
                unset($menuItems['locations']);

            $manager->registerMainItems($menuItems);
        });
    }

    /**
     * Register admin menu navigation items
     */
    protected function registerNavMenuItems()
    {
        AdminMenu::registerCallback(function (Classes\Navigation $manager) {
            $manager->registerNavItems([
                'dashboard' => [
                    'priority' => 0,
                    'class' => 'dashboard admin',
                    'href' => admin_url('dashboard'),
                    'icon' => 'fa-tachometer-alt',
                    'title' => lang('igniter::admin.side_menu.dashboard'),
                ],
                'restaurant' => [
                    'priority' => 10,
                    'class' => 'restaurant',
                    'icon' => 'fa-gem',
                    'title' => lang('igniter::admin.side_menu.restaurant'),
                    'child' => [
                        'locations' => [
                            'priority' => 10,
                            'class' => 'locations',
                            'href' => admin_url('locations'),
                            'title' => lang('igniter::admin.side_menu.location'),
                            'permission' => 'Admin.Locations',
                        ],
                        'menus' => [
                            'priority' => 20,
                            'class' => 'menus',
                            'href' => admin_url('menus'),
                            'title' => lang('igniter::admin.side_menu.menu'),
                            'permission' => 'Admin.Menus',
                        ],
                        'categories' => [
                            'priority' => 30,
                            'class' => 'categories',
                            'href' => admin_url('categories'),
                            'title' => lang('igniter::admin.side_menu.category'),
                            'permission' => 'Admin.Categories',
                        ],
                        'mealtimes' => [
                            'priority' => 40,
                            'class' => 'mealtimes',
                            'href' => admin_url('mealtimes'),
                            'title' => lang('igniter::admin.side_menu.mealtimes'),
                            'permission' => 'Admin.Mealtimes',
                        ],
                        'tables' => [
                            'priority' => 50,
                            'class' => 'tables',
                            'href' => admin_url('tables'),
                            'title' => lang('igniter::admin.side_menu.table'),
                            'permission' => 'Admin.Tables',
                        ],
                    ],
                ],
                'sales' => [
                    'priority' => 30,
                    'class' => 'sales',
                    'icon' => 'fa-file-invoice',
                    'title' => lang('igniter::admin.side_menu.sale'),
                    'child' => [
                        'orders' => [
                            'priority' => 10,
                            'class' => 'orders',
                            'href' => admin_url('orders'),
                            'title' => lang('igniter::admin.side_menu.order'),
                            'permission' => 'Admin.Orders',
                        ],
                        'reservations' => [
                            'priority' => 20,
                            'class' => 'reservations',
                            'href' => admin_url('reservations'),
                            'title' => lang('igniter::admin.side_menu.reservation'),
                            'permission' => 'Admin.Reservations',
                        ],
                        'statuses' => [
                            'priority' => 40,
                            'class' => 'statuses',
                            'href' => admin_url('statuses'),
                            'title' => lang('igniter::admin.side_menu.status'),
                            'permission' => 'Admin.Statuses',
                        ],
                        'payments' => [
                            'priority' => 50,
                            'class' => 'payments',
                            'href' => admin_url('payments'),
                            'title' => lang('igniter::admin.side_menu.payment'),
                            'permission' => 'Admin.Payments',
                        ],
                    ],
                ],
                'marketing' => [
                    'priority' => 40,
                    'class' => 'marketing',
                    'icon' => 'fa-bullseye',
                    'title' => lang('igniter::admin.side_menu.marketing'),
                    'child' => [],
                ],
                'customers' => [
                    'priority' => 100,
                    'class' => 'customers',
                    'icon' => 'fa-user',
                    'href' => admin_url('customers'),
                    'title' => lang('igniter::admin.side_menu.customer'),
                    'permission' => 'Admin.Customers',
                ],
                'design' => [
                    'priority' => 200,
                    'class' => 'design',
                    'icon' => 'fa-paint-brush',
                    'title' => lang('igniter::admin.side_menu.design'),
                    'child' => [
                        'themes' => [
                            'priority' => 10,
                            'class' => 'themes',
                            'href' => admin_url('themes'),
                            'title' => lang('igniter::admin.side_menu.theme'),
                            'permission' => 'Site.Themes',
                        ],
                        'mail_templates' => [
                            'priority' => 20,
                            'class' => 'mail_templates',
                            'href' => admin_url('mail_templates'),
                            'title' => lang('igniter::admin.side_menu.mail_template'),
                            'permission' => 'Admin.MailTemplates',
                        ],
                    ],
                ],
                'localisation' => [
                    'priority' => 300,
                    'class' => 'localisation',
                    'icon' => 'fa-globe',
                    'title' => lang('igniter::admin.side_menu.localisation'),
                    'child' => [
                        'languages' => [
                            'priority' => 10,
                            'class' => 'languages',
                            'href' => admin_url('languages'),
                            'title' => lang('igniter::admin.side_menu.language'),
                            'permission' => 'Site.Languages',
                        ],
                        'currencies' => [
                            'priority' => 20,
                            'class' => 'currencies',
                            'href' => admin_url('currencies'),
                            'title' => lang('igniter::admin.side_menu.currency'),
                            'permission' => 'Site.Currencies',
                        ],
                        'countries' => [
                            'priority' => 30,
                            'class' => 'countries',
                            'href' => admin_url('countries'),
                            'title' => lang('igniter::admin.side_menu.country'),
                            'permission' => 'Site.Countries',
                        ],
                    ],
                ],
                'tools' => [
                    'priority' => 400,
                    'class' => 'tools',
                    'icon' => 'fa-wrench',
                    'title' => lang('igniter::admin.side_menu.tool'),
                    'child' => [
                        'media_manager' => [
                            'priority' => 10,
                            'class' => 'media_manager',
                            'href' => admin_url('media_manager'),
                            'title' => lang('igniter::admin.side_menu.media_manager'),
                            'permission' => 'Admin.MediaManager',
                        ],
                    ],
                ],
                'system' => [
                    'priority' => 999,
                    'class' => 'system',
                    'icon' => 'fa-cog',
                    'title' => lang('igniter::admin.side_menu.system'),
                    'child' => [
                        'users' => [
                            'priority' => 0,
                            'class' => 'users',
                            'href' => admin_url('users'),
                            'title' => lang('igniter::admin.side_menu.user'),
                            'permission' => 'Admin.Staffs',
                        ],
                        'extensions' => [
                            'priority' => 10,
                            'class' => 'extensions',
                            'href' => admin_url('extensions'),
                            'title' => lang('igniter::admin.side_menu.extension'),
                            'permission' => 'Admin.Extensions',
                        ],
                        'settings' => [
                            'priority' => 20,
                            'class' => 'settings',
                            'href' => admin_url('settings'),
                            'title' => lang('igniter::admin.side_menu.setting'),
                            'permission' => 'Site.Settings',
                        ],
                        'updates' => [
                            'priority' => 30,
                            'class' => 'updates',
                            'href' => admin_url('updates'),
                            'title' => lang('igniter::admin.side_menu.updates'),
                            'permission' => 'Site.Updates',
                        ],
                        'system_logs' => [
                            'priority' => 50,
                            'class' => 'system_logs',
                            'href' => admin_url('system_logs'),
                            'title' => lang('igniter::admin.side_menu.system_logs'),
                            'permission' => 'Admin.SystemLogs',
                        ],
                    ],
                ],
            ]);
        });
    }

    protected function replaceNavMenuItem()
    {
        AdminMenu::registerCallback(function (Classes\Navigation $manager) {
            // Change nav menu if single location mode is activated
            if (AdminLocation::check()) {
                $manager->mergeNavItem('locations', [
                    'href' => admin_url('locations/settings'),
                    'title' => lang('igniter::admin.locations.text_form_name'),
                ], 'restaurant');
            }
        });
    }

    protected function defineEloquentMorphMaps()
    {
        Relation::morphMap([
            'addresses' => \Igniter\Admin\Models\Address::class,
            'assignable_logs' => \Igniter\Admin\Models\AssignableLog::class,
            'categories' => \Igniter\Admin\Models\Category::class,
            'customer_groups' => \Igniter\Main\Models\CustomerGroup::class,
            'customers' => \Igniter\Main\Models\Customer::class,
            'ingredients' => \Igniter\Admin\Models\Ingredient::class,
            'location_areas' => \Igniter\Admin\Models\LocationArea::class,
            'locations' => \Igniter\Admin\Models\Location::class,
            'mealtimes' => \Igniter\Admin\Models\Mealtime::class,
            'menu_categories' => \Igniter\Admin\Models\MenuCategory::class,
            'menu_item_option_values' => \Igniter\Admin\Models\MenuItemOptionValue::class,
            'menu_option_values' => \Igniter\Admin\Models\MenuOptionValue::class,
            'menu_options' => \Igniter\Admin\Models\MenuOption::class,
            'menus' => \Igniter\Admin\Models\Menu::class,
            'menus_specials' => \Igniter\Admin\Models\MenuSpecial::class,
            'orders' => \Igniter\Admin\Models\Order::class,
            'payment_logs' => \Igniter\Admin\Models\PaymentLog::class,
            'payments' => \Igniter\Admin\Models\Payment::class,
            'reservations' => \Igniter\Admin\Models\Reservation::class,
            'status_history' => \Igniter\Admin\Models\StatusHistory::class,
            'statuses' => \Igniter\Admin\Models\Status::class,
            'stocks' => \Igniter\Admin\Models\Stock::class,
            'stock_history' => \Igniter\Admin\Models\StockHistory::class,
            'tables' => \Igniter\Admin\Models\Table::class,
            'user_groups' => \Igniter\Admin\Models\UserGroup::class,
            'users' => \Igniter\Admin\Models\User::class,
            'working_hours' => \Igniter\Admin\Models\WorkingHour::class,
        ]);
    }

    protected function registerOnboardingSteps()
    {
        Classes\OnboardingSteps::registerCallback(function (Classes\OnboardingSteps $manager) {
            $manager->registerSteps([
                'admin::settings' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_settings',
                    'description' => 'igniter::admin.dashboard.onboarding.help_settings',
                    'icon' => 'fa-gears',
                    'url' => admin_url('settings'),
                    'complete' => [\Igniter\System\Models\Settings::class, 'onboardingIsComplete'],
                ],
                'admin::locations' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_locations',
                    'description' => 'igniter::admin.dashboard.onboarding.help_locations',
                    'icon' => 'fa-store',
                    'url' => admin_url('locations'),
                    'complete' => [\Igniter\Admin\Models\Location::class, 'onboardingIsComplete'],
                ],
                'admin::themes' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_themes',
                    'description' => 'igniter::admin.dashboard.onboarding.help_themes',
                    'icon' => 'fa-paint-brush',
                    'url' => admin_url('themes'),
                    'complete' => [\Igniter\Main\Models\Theme::class, 'onboardingIsComplete'],
                ],
                'admin::extensions' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_extensions',
                    'description' => 'igniter::admin.dashboard.onboarding.help_extensions',
                    'icon' => 'fa-plug',
                    'url' => admin_url('extensions'),
                    'complete' => [\Igniter\System\Models\Extension::class, 'onboardingIsComplete'],
                ],
                'admin::payments' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_payments',
                    'description' => 'igniter::admin.dashboard.onboarding.help_payments',
                    'icon' => 'fa-credit-card',
                    'url' => admin_url('payments'),
                    'complete' => [\Igniter\Admin\Models\Payment::class, 'onboardingIsComplete'],
                ],
                'admin::menus' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_menus',
                    'description' => 'igniter::admin.dashboard.onboarding.help_menus',
                    'icon' => 'fa-cutlery',
                    'url' => admin_url('menus'),
                ],
                'admin::mail' => [
                    'label' => 'igniter::admin.dashboard.onboarding.label_mail',
                    'description' => 'igniter::admin.dashboard.onboarding.help_mail',
                    'icon' => 'fa-envelope',
                    'url' => admin_url('settings/edit/mail'),
                ],
            ]);
        });
    }

    protected function registerActivityTypes()
    {
        Activity::registerCallback(function (Activity $manager) {
            $manager->registerActivityTypes([
                ActivityTypes\AssigneeUpdated::class => [
                    ActivityTypes\AssigneeUpdated::ORDER_ASSIGNED_TYPE,
                    ActivityTypes\AssigneeUpdated::RESERVATION_ASSIGNED_TYPE,
                ],
                ActivityTypes\StatusUpdated::class => [
                    ActivityTypes\StatusUpdated::ORDER_UPDATED_TYPE,
                    ActivityTypes\StatusUpdated::RESERVATION_UPDATED_TYPE,
                ],
            ]);
        });
    }

    protected function registerPermissions()
    {
        resolve(Classes\PermissionManager::class)->registerCallback(function ($manager) {
            $manager->registerPermissions('Admin', [
                'Admin.Dashboard' => [
                    'label' => 'igniter::admin.permissions.dashboard', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Allergens' => [
                    'label' => 'igniter::admin.permissions.allergens', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Categories' => [
                    'label' => 'igniter::admin.permissions.categories', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Menus' => [
                    'label' => 'igniter::admin.permissions.menus', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Mealtimes' => [
                    'label' => 'igniter::admin.permissions.mealtimes', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Locations' => [
                    'label' => 'igniter::admin.permissions.locations', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Tables' => [
                    'label' => 'igniter::admin.permissions.tables', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Orders' => [
                    'label' => 'igniter::admin.permissions.orders', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.DeleteOrders' => [
                    'label' => 'igniter::admin.permissions.delete_orders', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.AssignOrders' => [
                    'label' => 'igniter::admin.permissions.assign_orders', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Reservations' => [
                    'label' => 'igniter::admin.permissions.reservations', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.DeleteReservations' => [
                    'label' => 'igniter::admin.permissions.delete_reservations', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.AssignReservations' => [
                    'label' => 'igniter::admin.permissions.assign_reservations', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Payments' => [
                    'label' => 'igniter::admin.permissions.payments', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.CustomerGroups' => [
                    'label' => 'igniter::admin.permissions.customer_groups', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Customers' => [
                    'label' => 'igniter::admin.permissions.customers', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Impersonate' => [
                    'label' => 'igniter::admin.permissions.impersonate_staff', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.ImpersonateCustomers' => [
                    'label' => 'igniter::admin.permissions.impersonate_customers', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.StaffGroups' => [
                    'label' => 'igniter::admin.permissions.user_groups', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Staffs' => [
                    'label' => 'igniter::admin.permissions.staffs', 'group' => 'igniter::admin.permissions.name',
                ],
                'Admin.Statuses' => [
                    'label' => 'igniter::admin.permissions.statuses', 'group' => 'igniter::admin.permissions.name',
                ],
            ]);
        });
    }

    protected function registerSchedule()
    {
        Event::listen('console.schedule', function (Schedule $schedule) {
            // Check for assignables to assign every minute
            if (Classes\Allocator::isEnabled()) {
                $schedule->call(function () {
                    Classes\Allocator::allocate();
                })->name('Assignables Allocator')->withoutOverlapping(5)->runInBackground()->everyMinute();
            }

            $schedule->call(function () {
                Classes\UserState::clearExpiredStatus();
            })->name('Clear user custom away status')->withoutOverlapping(5)->runInBackground()->everyMinute();
        });
    }

    protected function registerSystemSettings()
    {
        Settings::registerCallback(function (Settings $manager) {
            $manager->registerSettingItems('core', [
                'setup' => [
                    'label' => 'lang:igniter::admin.settings.text_tab_setup',
                    'description' => 'lang:igniter::admin.settings.text_tab_desc_setup',
                    'icon' => 'fa fa-file-invoice',
                    'priority' => 1,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/setup'),
                    'form' => 'setupsettings',
                    'request' => \Igniter\Admin\Requests\SetupSettings::class,
                ],
                'tax' => [
                    'label' => 'lang:igniter::admin.settings.text_tab_tax',
                    'description' => 'lang:igniter::admin.settings.text_tab_desc_tax',
                    'icon' => 'fa fa-file',
                    'priority' => 6,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/tax'),
                    'form' => 'taxsettings',
                    'request' => 'Igniter\Admin\Requests\TaxSettings',
                ],
                'user' => [
                    'label' => 'lang:igniter::admin.settings.text_tab_user',
                    'description' => 'lang:igniter::admin.settings.text_tab_desc_user',
                    'icon' => 'fa fa-user',
                    'priority' => 3,
                    'permission' => ['Site.Settings'],
                    'url' => admin_url('settings/edit/user'),
                    'form' => 'usersettings',
                    'request' => \Igniter\Admin\Requests\UserSettings::class,
                ],
            ]);
        });
    }

    protected function extendLocationOptionsFields()
    {
        Event::listen('admin.locations.defineOptionsFormFields', function () {
            return [
                'guest_order' => [
                    'label' => 'lang:igniter::system.settings.label_guest_order',
                    'accordion' => 'lang:igniter::admin.locations.text_tab_general_options',
                    'type' => 'radiotoggle',
                    'comment' => 'lang:igniter::admin.locations.help_guest_order',
                    'default' => -1,
                    'options' => [
                        -1 => 'lang:igniter::admin.text_use_default',
                        0 => 'lang:igniter::admin.text_no',
                        1 => 'lang:igniter::admin.text_yes',
                    ],
                ],
            ];
        });

        Event::listen('system.formRequest.extendValidator', function ($formRequest, $dataHolder) {
            if (!$formRequest instanceof Location)
                return;

            $dataHolder->attributes = array_merge($dataHolder->attributes, [
                'guest_order' => lang('igniter::admin.locations.label_guest_order'),
            ]);

            $dataHolder->rules = array_merge($dataHolder->rules, [
                'guest_order' => ['integer'],
            ]);
        });
    }

    protected function defineRoutes()
    {
        if (app()->routesAreCached())
            return;

        Route::group([], function ($router) {
            (new Classes\RouteRegistrar($router))->all();
        });
    }
}
