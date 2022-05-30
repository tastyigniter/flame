<?php

namespace Tests\Main\Classes;

use Igniter\Main\Classes\ThemeManager;

function path()
{
    return realpath(__DIR__.'/../../_fixtures/tests-theme');
}

function bootTheme()
{
    return resolve(ThemeManager::class)->loadTheme(path())->boot();
}

function themeManager()
{
    $manager = resolve(ThemeManager::class);
    $manager->loadTheme(path())->boot();

    return $manager;
}

it('loads a single theme', function () {
    expect(bootTheme())->getPath()->toStartWith(path());
});

it('has active theme', function () {
})->skip();

it('finds a theme file', function () {
    expect(themeManager()->findFile('_pages/components.blade.php', 'tests-theme'))
        ->toStartWith(path());
});

it('fails when theme file does not exist', function () {
    expect(themeManager()->findFile('_pages/compone.blade.php', 'tests-theme'))
        ->toBeFalse();
});

it('writes a theme file', function () {
})->skip();

it('renames a theme file', function () {
    $oldFile = '_pages/components.blade.php';
    $newFile = '_pages/compon.blade.php';

    expect(themeManager()->renameFile($oldFile, $newFile, 'tests-theme'))->toBeTrue();

    themeManager()->renameFile($newFile, $oldFile, 'tests-theme');
});

it('deletes a theme file', function () {
})->skip();

it('extracts a theme archive', function () {
})->skip();

it('deletes a theme directory', function () {
})->skip();

it('installs a theme', function () {
})->skip();

it('creates a child theme', function () {
})->skip();

it('validates a theme configuration', function () {
})->skip();
