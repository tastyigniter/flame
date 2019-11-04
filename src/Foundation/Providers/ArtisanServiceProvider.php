<?php

namespace Igniter\Flame\Foundation\Providers;

use Igniter\Flame\Foundation\Console\KeyGenerateCommand;
use Igniter\Flame\Foundation\Console\SeedCommand;
use Illuminate\Console\Scheduling\ScheduleFinishCommand;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Foundation\Providers\ArtisanServiceProvider as BaseServiceProvider;

class ArtisanServiceProvider extends BaseServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'CacheClear' => 'command.cache.clear',
        'CacheForget' => 'command.cache.forget',
        'ClearCompiled' => 'command.clear-compiled',
//        'ClearResets' => 'command.auth.resets.clear',
        'ConfigCache' => 'command.config.cache',
        'ConfigClear' => 'command.config.clear',
        'Down' => 'command.down',
        'Environment' => 'command.environment',
        'KeyGenerate' => 'command.key.generate',
//        'Migrate' => 'command.migrate',
//        'MigrateFresh' => 'command.migrate.fresh',
//        'MigrateInstall' => 'command.migrate.install',
//        'MigrateRefresh' => 'command.migrate.refresh',
//        'MigrateReset' => 'command.migrate.reset',
//        'MigrateRollback' => 'command.migrate.rollback',
//        'MigrateStatus' => 'command.migrate.status',
        'Optimize' => 'command.optimize',
        'OptimizeClear' => 'command.optimize.clear',
        'PackageDiscover' => 'command.package.discover',
        'Preset' => 'command.preset',
        'QueueFailed' => 'command.queue.failed',
        'QueueFlush' => 'command.queue.flush',
        'QueueForget' => 'command.queue.forget',
        'QueueListen' => 'command.queue.listen',
        'QueueRestart' => 'command.queue.restart',
        'QueueRetry' => 'command.queue.retry',
        'QueueWork' => 'command.queue.work',
        'RouteCache' => 'command.route.cache',
        'RouteClear' => 'command.route.clear',
        'RouteList' => 'command.route.list',
        'Seed' => 'command.seed',
        'ScheduleFinish' => ScheduleFinishCommand::class,
        'ScheduleRun' => ScheduleRunCommand::class,
//        'StorageLink' => 'command.storage.link',
        'Up' => 'command.up',
        'ViewClear' => 'command.view.clear',
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
//        'AppName' => 'command.app.name',
//        'AuthMake' => 'command.auth.make',
        'CacheTable' => 'command.cache.table',
//        'ConsoleMake' => 'command.console.make',
//        'ControllerMake' => 'command.controller.make',
//        'EventGenerate' => 'command.event.generate',
//        'EventMake' => 'command.event.make',
//        'ExceptionMake' => 'command.exception.make',
//        'FactoryMake' => 'command.factory.make',
//        'JobMake' => 'command.job.make',
//        'ListenerMake' => 'command.listener.make',
//        'MailMake' => 'command.mail.make',
//        'MiddlewareMake' => 'command.middleware.make',
//        'MigrateMake' => 'command.migrate.make',
//        'ModelMake' => 'command.model.make',
//        'NotificationMake' => 'command.notification.make',
        'NotificationTable' => 'command.notification.table',
//        'PolicyMake' => 'command.policy.make',
//        'ProviderMake' => 'command.provider.make',
        'QueueFailedTable' => 'command.queue.failed-table',
        'QueueTable' => 'command.queue.table',
//        'RequestMake' => 'command.request.make',
//        'ResourceMake' => 'command.resource.make',
//        'RuleMake' => 'command.rule.make',
//        'SeederMake' => 'command.seeder.make',
        'SessionTable' => 'command.session.table',
        'Serve' => 'command.serve',
        'TestMake' => 'command.test.make',
        'VendorPublish' => 'command.vendor.publish',
    ];

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerKeyGenerateCommand()
    {
        $this->app->singleton('command.key.generate', function ($app) {
            return new KeyGenerateCommand($app['files']);
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton('command.seed', function ($app) {
            return new SeedCommand($app['db']);
        });
    }
}