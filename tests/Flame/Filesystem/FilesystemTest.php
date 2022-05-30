<?php

namespace Tests;

use Igniter\Flame\Igniter;

it('symbolizes path', function () {
    Igniter::loadResourcesFrom(__DIR__.'/../../_fixtures', 'tests.fixtures');

    $path = resolve('files')->symbolizePath('tests.fixtures::js/app.js');

    expect($path)->toEndWith('_fixtures/js/app.js');
});
