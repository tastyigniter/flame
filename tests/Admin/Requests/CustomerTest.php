<?php

namespace Tests\Admin\Requests;

use Igniter\Main\Requests\Customer;

it('has rules for first_name field', function () {
    $rules = array_get((new Customer)->rules(), 'first_name');

    expect('required')->toBeIn($rules);

    expect('between:1,48')->toBeIn($rules);
});

it('has rules for last_name field', function () {
    $rules = array_get((new Customer)->rules(), 'last_name');

    expect('required')->toBeIn($rules);

    expect('between:1,48')->toBeIn($rules);
});

it('has rules for email field', function () {
    $rules = array_get((new Customer)->rules(), 'email');

    expect('email:filter')->toBeIn($rules);

    expect('max:96')->toBeIn($rules);

    expect('unique:customers,email')->toBeIn($rules);
});
