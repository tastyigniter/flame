<?php

namespace Tests\Admin\Classes;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use Tests\Fixtures\Controllers\TestController;

it('has defined paths to locate layouts', function () {
    $controller = resolve(TestController::class);

    expect('igniter.admin::_layouts')->toBeIn($controller->layoutPath);
    expect('tests.fixtures::_layouts')->toBeIn($controller->layoutPath);
    expect('tests.fixtures::')->not()->toBeIn($controller->layoutPath);
});

it('has defined paths to locate views', function () {
    $controller = resolve(TestController::class);

    expect('igniter.admin::')->toBeIn($controller->viewPath);
    expect('tests.fixtures::testcontroller')->toBeIn($controller->viewPath);
    expect('tests.fixtures::')->toBeIn($controller->viewPath);
});

it('has defined paths to locate partials', function () {
    $controller = resolve(TestController::class);

    expect('igniter.admin::_partials')->toBeIn($controller->partialPath);
    expect('tests.fixtures::_partials')->toBeIn($controller->partialPath);
    expect('tests.fixtures::')->not()->toBeIn($controller->partialPath);
});

it('has defined paths to locate model config files', function () {
    $controller = resolve(TestController::class);

    expect('igniter::models/admin')->toBeIn($controller->configPath);
    expect('igniter::models/system')->toBeIn($controller->configPath);
    expect('igniter::models/main')->toBeIn($controller->configPath);
    expect('tests.fixtures::models')->toBeIn($controller->configPath);
});

it('has defined paths to locate asset files', function () {
    $controller = resolve(TestController::class);

    expect('tests.fixtures::')->toBeIn($controller->assetPath);
    expect('igniter::')->toBeIn($controller->assetPath);
    expect('igniter::js')->toBeIn($controller->assetPath);
    expect('igniter::css')->toBeIn($controller->assetPath);
});

it('can find (default) layout', function () {
    $controller = resolve(TestController::class);
    $viewPath = $controller->getViewPath('default', $controller->layoutPath);

    expect($viewPath)->toEndWith('admin/_layouts/default.blade.php');
});

it('can find (edit) view', function () {
    $controller = resolve(TestController::class);
    $viewPath = $controller->getViewPath('edit', $controller->viewPath);

    expect($viewPath)->toEndWith('admin/edit.blade.php');
});

it('can find (flash) partial', function () {
    $controller = resolve(TestController::class);
    $partialPath = $controller->getViewPath('flash', $controller->partialPath);

    expect($partialPath)->toEndWith('admin/_partials/flash.blade.php');
});

it('can find controller config file', function () {
    $controller = resolve(TestController::class);
    $viewPath = $controller->getConfigPath('status.php');

    expect($viewPath)->toEndWith('models/admin/status.php');
});

it('can find asset file', function () {
    $controller = resolve(TestController::class);

    expect($controller->getAssetPath('app.js'))->toEndWith('js/app.js');
    expect($controller->getAssetPath('$/igniter/js/vendor.js'))->toEndWith('igniter/js/vendor.js');
});

it('runs the requested controller action', function () {
    get('admin/login')->assertStatus(200);
});

it('runs the requested controller handler', function () {
    post('admin/login', ['_handler' => 'onLogin'])->assertSessionHas('admin_errors');
});
