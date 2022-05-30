<?php

namespace Tests\Main\Classes;

use Igniter\Flame\Igniter;
use Igniter\Main\Classes\Router;
use Igniter\Main\Classes\ThemeManager;

function path()
{
    return realpath(__DIR__.'/../../_fixtures/tests-theme');
}

function bootTheme()
{
    return resolve(ThemeManager::class)->loadTheme(path())->boot();
}

function defineEnvironment($app)
{
    Igniter::useThemesPath(dirname(path()));
}

beforeEach(function () {
    $case = $this;
    config('igniter.system.defaultTheme', 'tests-theme');
    ThemeManager::addDirectory(path());
});

it('finds a theme page', function () {
    $router = new Router(bootTheme());

    $route = route('igniter.theme.components');

    $page = $router->findPage('components', []);
});

it('rewrites page path to url')->skip();
