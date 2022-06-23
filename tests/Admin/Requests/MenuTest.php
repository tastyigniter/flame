<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Menu;

it('has rules for menu_name', function () {
    $rules = (new Menu)->rules();

    expect('required')->toBeIn(array_get($rules, 'menu_name'));
    expect('between:2,255')->toBeIn(array_get($rules, 'menu_name'));
    expect('unique:menus')->toBeIn(array_get($rules, 'menu_name'));
});

it('has rules for menu_price', function () {
    $rules = (new Menu)->rules();

    expect('required')->toBeIn(array_get($rules, 'menu_price'));
    expect('min:0')->toBeIn(array_get($rules, 'menu_price'));
});

it('has rules for menu_description', function () {
    $rules = (new Menu)->rules();

    expect('between:2,1028')->toBeIn(array_get($rules, 'menu_description'));
});
