<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Mealtime;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new Mealtime)->rules(), 'mealtime_name'));

    expect('required')->toBeIn(array_get((new Mealtime)->rules(), 'start_time'));

    expect('required')->toBeIn(array_get((new Mealtime)->rules(), 'end_time'));

    expect('required')->toBeIn(array_get((new Mealtime)->rules(), 'mealtime_status'));
});

it('has max characters rule for mealtime_name input', function () {
    expect('between:2,128')->toBeIn(array_get((new Mealtime)->rules(), 'mealtime_name'));
});

it('has valid_time rule for start_time and end_time input', function () {
    expect('valid_time')->toBeIn(array_get((new Mealtime)->rules(), 'start_time'));

    expect('valid_time')->toBeIn(array_get((new Mealtime)->rules(), 'end_time'));
});
