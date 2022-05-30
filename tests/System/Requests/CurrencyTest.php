<?php

namespace Tests\System\Requests;

use Igniter\System\Requests\Currency;

it('has required rule for inputs', function () {
    $rules = (new Currency)->rules();

    expect('required')->toBeIn(array_get($rules, 'currency_name'));

    expect('required')->toBeIn(array_get($rules, 'currency_code'));

    expect('required')->toBeIn(array_get($rules, 'country_id'));

    expect('required')->toBeIn(array_get($rules, 'currency_status'));
});

it('has max characters rule for inputs', function () {
    $rules = (new Currency)->rules();

    expect('between:2,32')->toBeIn(array_get($rules, 'currency_name'));

    expect('size:3')->toBeIn(array_get($rules, 'currency_code'));

    expect('size:1')->toBeIn(array_get($rules, 'symbol_position'));

    expect('size:1')->toBeIn(array_get($rules, 'thousand_sign'));

    expect('size:1')->toBeIn(array_get($rules, 'decimal_sign'));

    expect('max:10')->toBeIn(array_get($rules, 'decimal_position'));
});
