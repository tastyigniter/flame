<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Menu;

it('has rules for menu_name', function () {
    expect('required')->toBeIn(array_get((new Menu)->rules(), 'menu_name'));
    expect('between:2,255')->toBeIn(array_get((new Menu)->rules(), 'menu_name'));
});

it('has rules for menu_price', function () {
    expect('required')->toBeIn(array_get((new Menu)->rules(), 'menu_price'));
    expect('min:0')->toBeIn(array_get((new Menu)->rules(), 'menu_price'));
});

it('has rules for menu_description', function () {
    expect('between:2,1028')->toBeIn(array_get((new Menu)->rules(), 'menu_description'));
});
