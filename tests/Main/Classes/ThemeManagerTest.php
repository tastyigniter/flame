<?php

namespace Tests\Main\Classes;

use Igniter\Main\Classes\ThemeManager;

function testThemePath()
{
    return realpath(__DIR__.'/../../_fixtures/tests-theme');
}

function testBootTheme()
{
    return resolve(ThemeManager::class)->loadTheme(testThemePath())->boot();
}

function themeManager()
{
    $manager = resolve(ThemeManager::class);
    $manager->loadTheme(testThemePath())->boot();

    return $manager;
}

it('loads a single theme', function () {
    expect(testBootTheme())->getPath()->toStartWith(testThemePath());
});

it('has active theme', function () {
})->skip();

it('finds a theme file', function () {
    expect(themeManager()->findFile('_pages/components.blade.php', 'tests-theme'))
        ->toStartWith(testThemePath());
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
