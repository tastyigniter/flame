<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Mealtime;

it('has required rule for inputs', function () {
    $rules = (new Mealtime)->rules();

    expect('required')->toBeIn(array_get($rules, 'mealtime_name'));
    expect('required')->toBeIn(array_get($rules, 'start_time'));
    expect('required')->toBeIn(array_get($rules, 'end_time'));
    expect('required')->toBeIn(array_get($rules, 'mealtime_status'));
});

it('has max characters rule for mealtime_name input', function () {
    $rules = (new Mealtime)->rules();

    expect('between:2,128')->toBeIn(array_get($rules, 'mealtime_name'));
});

it('has unique rule for mealtime_name input', function () {
    $rules = (new Mealtime)->rules();

    expect('unique:mealtimes')->toBeIn(array_get($rules, 'mealtime_name'));
});

it('has valid_time rule for start_time and end_time input', function () {
    $rules = (new Mealtime)->rules();

    expect('valid_time')->toBeIn(array_get($rules, 'start_time'));
    expect('valid_time')->toBeIn(array_get($rules, 'end_time'));
});
