<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Location;

it('has required rule for location_name, location_email and ...', function () {
    $rules = (new Location)->rules();

    expect('required')->toBeIn(array_get($rules, 'location_name'));

    expect('required')->toBeIn(array_get($rules, 'location_email'));

    expect('required')->toBeIn(array_get($rules, 'location_address_1'));

    expect('required')->toBeIn(array_get($rules, 'location_country_id'));

    expect('required')->toBeIn(array_get($rules, 'options.auto_lat_lng'));
});

it('has sometimes rule for inputs', function () {
    $rules = (new Location)->rules();

    expect('sometimes')->toBeIn(array_get($rules, 'location_telephone'));

    expect('sometimes')->toBeIn(array_get($rules, 'location_lat'));

    expect('sometimes')->toBeIn(array_get($rules, 'location_lng'));
});

it('has max characters rule for inputs', function () {
    $rules = (new Location)->rules();

    expect('max:96')->toBeIn(array_get($rules, 'location_email'));

    expect('between:2,128')->toBeIn(array_get($rules, 'location_address_1'));

    expect('max:128')->toBeIn(array_get($rules, 'location_address_2'));

    expect('max:128')->toBeIn(array_get($rules, 'location_city'));

    expect('max:128')->toBeIn(array_get($rules, 'location_state'));

    expect('max:15')->toBeIn(array_get($rules, 'location_postcode'));

    expect('max:3028')->toBeIn(array_get($rules, 'description'));

    expect('max:255')->toBeIn(array_get($rules, 'permalink_slug'));

    expect('max:128')->toBeIn(array_get($rules, 'options.gallery.title'));

    expect('max:255')->toBeIn(array_get($rules, 'options.gallery.description'));
});
