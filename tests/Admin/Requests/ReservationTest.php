<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Reservation;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new Reservation)->rules(), 'location_id'));

    expect('required')->toBeIn(array_get((new Reservation)->rules(), 'first_name'));

    expect('required')->toBeIn(array_get((new Reservation)->rules(), 'last_name'));

    expect('required')->toBeIn(array_get((new Reservation)->rules(), 'reserve_date'));

    expect('required')->toBeIn(array_get((new Reservation)->rules(), 'reserve_time'));

    expect('required')->toBeIn(array_get((new Reservation)->rules(), 'guest_num'));
});

it('has max characters rule for inputs', function () {
    expect('between:1,48')->toBeIn(array_get((new Reservation)->rules(), 'first_name'));

    expect('between:1,48')->toBeIn(array_get((new Reservation)->rules(), 'last_name'));

    expect('max:96')->toBeIn(array_get((new Reservation)->rules(), 'email'));
});

it('has valid_date and valid_time rule for inputs', function () {
    expect('valid_date')->toBeIn(array_get((new Reservation)->rules(), 'reserve_date'));

    expect('valid_time')->toBeIn(array_get((new Reservation)->rules(), 'reserve_time'));
});
