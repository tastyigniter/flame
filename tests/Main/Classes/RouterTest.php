<?php

namespace Tests\Main\Classes;

use Igniter\Main\Classes\Router;

it('finds a theme page', function () {
    expect(resolve(Router::class)->findPage('components'))
        ->permalink
        ->toBe('/components');
});

it('rewrites page path to url', function () {
    expect(resolve(Router::class)->findByFile('nested-page', ['slug' => 'hello']))
        ->toBe('/nested/page/hello');
});
