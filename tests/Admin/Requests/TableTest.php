<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Table;

it('has required rule for inputs', function () {
    $rules = (new Table)->rules();

    expect('required')->toBeIn(array_get($rules, 'table_name'));

    expect('required')->toBeIn(array_get($rules, 'min_capacity'));

    expect('required')->toBeIn(array_get($rules, 'max_capacity'));

    expect('required')->toBeIn(array_get($rules, 'extra_capacity'));

    expect('required')->toBeIn(array_get($rules, 'priority'));

    expect('required')->toBeIn(array_get($rules, 'is_joinable'));

    expect('required')->toBeIn(array_get($rules, 'table_status'));

    expect('required')->toBeIn(array_get($rules, 'locations'));
});

it('has rules for table_name input', function () {
    $rules = (new Table)->rules();

    expect('between:2,255')->toBeIn(array_get($rules, 'table_name'));
    expect('unique:tables')->toBeIn(array_get($rules, 'table_name'));
});

it('has min character rule for min_capacity and max_capacity input', function () {
    $rules = (new Table)->rules();

    expect('min:1')->toBeIn(array_get($rules, 'min_capacity'));
    expect('min:1')->toBeIn(array_get($rules, 'max_capacity'));
});

it('has rules for max_capacity input', function () {
    $rules = (new Table)->rules();

    expect('lte:max_capacity')->toBeIn(array_get($rules, 'min_capacity'));
    expect('gte:min_capacity')->toBeIn(array_get($rules, 'max_capacity'));
});
