<?php

namespace Tests\System\Requests;

use Igniter\System\Requests\Country;

it('has required rule for inputs', function () {
    $rules = (new Country)->rules();

    expect('required')->toBeIn(array_get($rules, 'country_name'));

    expect('required')->toBeIn(array_get($rules, 'priority'));

    expect('required')->toBeIn(array_get($rules, 'iso_code_2'));

    expect('required')->toBeIn(array_get($rules, 'iso_code_3'));

    expect('required')->toBeIn(array_get($rules, 'status'));
});

it('has max characters rule for inputs', function () {
    $rules = (new Country)->rules();

    expect('between:2,128')->toBeIn(array_get($rules, 'country_name'));

    expect('size:2')->toBeIn(array_get($rules, 'iso_code_2'));

    expect('size:3')->toBeIn(array_get($rules, 'iso_code_3'));

    expect('min:2')->toBeIn(array_get($rules, 'format'));
});
