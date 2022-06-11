<?php

namespace Tests\Main\Classes;

use Igniter\Main\Classes\ThemeManager;

it('loads test theme', function () {
    expect(resolve(ThemeManager::class)->findTheme('tests-theme'))
        ->getPath()
        ->toStartWith(testThemePath());
});

it('has active theme', function () {
    expect(resolve(ThemeManager::class)->getActiveTheme())
        ->getName()
        ->toEqual('tests-theme');
});

it('finds a theme file', function () {
    expect(resolve(ThemeManager::class)
        ->findFile('_pages/components.blade.php', 'tests-theme'))
        ->toStartWith(testThemePath());
});

it('fails when theme file does not exist', function () {
    expect(resolve(ThemeManager::class)
        ->findFile('_pages/compone.blade.php', 'tests-theme'))
        ->toBeFalse();
});

it('writes a theme file', function () {
})->skip();

it('renames a theme file', function () {
    $manager = resolve(ThemeManager::class);

    $oldFile = '_pages/components.blade.php';
    $newFile = '_pages/compon.blade.php';

    expect($manager->renameFile($oldFile, $newFile, 'tests-theme'))->toBeTrue();

    $manager->renameFile($newFile, $oldFile, 'tests-theme');
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
