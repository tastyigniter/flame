<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Table;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new Table)->rules(), 'table_name'));

    expect('required')->toBeIn(array_get((new Table)->rules(), 'min_capacity'));

    expect('required')->toBeIn(array_get((new Table)->rules(), 'max_capacity'));

    expect('required')->toBeIn(array_get((new Table)->rules(), 'extra_capacity'));

    expect('required')->toBeIn(array_get((new Table)->rules(), 'priority'));

    expect('required')->toBeIn(array_get((new Table)->rules(), 'is_joinable'));

    expect('required')->toBeIn(array_get((new Table)->rules(), 'table_status'));

    expect('required')->toBeIn(array_get((new Table)->rules(), 'locations'));
});

it('has rules for table_name input', function () {
    expect('between:2,255')->toBeIn(array_get((new Table)->rules(), 'table_name'));
});

it('has min character rule for min_capacity and max_capacity input', function () {
    expect('min:1')->toBeIn(array_get((new Table)->rules(), 'min_capacity'));
    expect('min:1')->toBeIn(array_get((new Table)->rules(), 'max_capacity'));
});

it('has rules for max_capacity input', function () {
    expect('lte:max_capacity')->toBeIn(array_get((new Table)->rules(), 'min_capacity'));
    expect('gte:min_capacity')->toBeIn(array_get((new Table)->rules(), 'max_capacity'));
});
