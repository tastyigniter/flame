<?php

namespace Tests\Admin\Models;

use Igniter\Admin\Models\Location;
use Illuminate\Support\Arr;

it('should fail to create a location when no name is provided', function () {
    try {
        $location = Location::factory()->make();
        $location->location_name = null;
        $location->save();
        $this->assertFalse(true);
    }
    catch (\Exception $e) {
        $this->assertFalse(false);
    }
});

it('should fail to create a location when no email is provided', function () {
    try {
        $location = Location::factory()->make();
        $location->location_email = null;
        $location->save();
        $this->assertFalse(true);
    }
    catch (\Exception $e) {
        $this->assertFalse(false);
    }
});

it('should fail to create a location when no address line 1 is provided', function () {
    try {
        $location = Location::factory()->make();
        $location->location_address_1 = null;
        $location->save();
        $this->assertFalse(true);
    }
    catch (\Exception $e) {
        $this->assertFalse(false);
    }
});

it('should create a latitude and longitude for the location when requested and address data is provided', function () {
    $location = Location::factory()->make();
    $location->setOption('auto_lat_lng', TRUE);
    $location->location_address_1 = '53 Church Road';
    $location->location_city = 'London';
    $location->location_postcode = 'SE19 2TJ';
    $location->location_lat = null;
    $location->location_lng = null;
    $location->save();

    $this->assertNotNull($location->location_lat);
    $this->assertNotNull($location->location_lng);
});
