<?php

namespace Tests;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            \Igniter\Flame\ServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $viewPaths = $app['config']->get('view.paths');
        $viewPaths[] = __DIR__.'/_fixtures/views/';

        $app['config']->set('view.paths', $viewPaths);
    }

    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $configs = [
            'auth', 'cart', 'currency', 'geocoder', 'system',
        ];

        foreach ($configs as $config) {
            $app['config']->set("igniter.$config", require(__DIR__."/../config/{$config}.php"));
        }
    }
}
