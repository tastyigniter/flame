<?php

namespace Tests\Igniter\Main\Template;

use Igniter\Main\Classes\ThemeManager;
use Igniter\Main\Template\Page;

it('reads page settings from pages.yml', function () {
    $page = Page::load(resolve(ThemeManager::class)->getActiveTheme(), 'nested-page');

    expect($page->settings['title'])->toBe('Nested page');
})->skip('Undefined key title');
