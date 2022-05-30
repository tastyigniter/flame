<?php

namespace Tests\Admin\Classes;

use Tests\Fixtures\Controllers\TestController;
use Tests\Fixtures\Widgets\TestWidget;

it('has defined paths to locate widget partials', function () {
    $controller = resolve(TestController::class);

    $widget = $controller->makeWidget(TestWidget::class);

    expect('tests.fixtures::_partials.widgets/testwidget')->toBeIn($widget->partialPath);
    expect('tests.fixtures::_partials.widgets')->toBeIn($widget->partialPath);
});

it('has defined paths to locate widget asset files', function () {
    $controller = resolve(TestController::class);

    $widget = $controller->makeWidget(TestWidget::class);

    expect('igniter::css/widgets')->toBeIn($widget->assetPath);
});
