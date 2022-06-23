<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Reservation;

it('has required rule for inputs', function () {
    $rules = (new Reservation)->rules();

    expect('required')->toBeIn(array_get($rules, 'location_id'));
    expect('required')->toBeIn(array_get($rules, 'first_name'));
    expect('required')->toBeIn(array_get($rules, 'last_name'));
    expect('required')->toBeIn(array_get($rules, 'reserve_date'));
    expect('required')->toBeIn(array_get($rules, 'reserve_time'));
    expect('required')->toBeIn(array_get($rules, 'guest_num'));
});

it('has max characters rule for inputs', function () {
    $rules = (new Reservation)->rules();

    expect('between:1,48')->toBeIn(array_get($rules, 'first_name'));
    expect('between:1,48')->toBeIn(array_get($rules, 'last_name'));
    expect('max:96')->toBeIn(array_get($rules, 'email'));
});

it('has valid_date and valid_time rule for inputs', function () {
    $rules = (new Reservation)->rules();

    expect('valid_date')->toBeIn(array_get($rules, 'reserve_date'));
    expect('valid_time')->toBeIn(array_get($rules, 'reserve_time'));
});
