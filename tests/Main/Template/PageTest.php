<?php

namespace Tests\Igniter\Main\Template;

use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;

it('reads page settings from pages.yml', function () {
    $manager = resolve(ThemeManager::class);
    $theme = $manager->loadTheme($path = realpath(__DIR__.'/../../_fixtures/theme'));
    $theme->boot();

    $page = Page::load($theme, 'components');
})->skip();
