<?php

namespace Tests\Admin\Models;

use Igniter\Admin\Models\Location;

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

it('should be able to create and delete a location', function () {
    $location = Location::factory()->make();
    $location->save();

    $location->delete();

    $this->assertNull($location->fresh());
});

it('should be able to get and set a location option', function () {
    $location = Location::factory()->make();
    $location->save();

    $location->setOption('test_option', true);

    $this->assertNotNull($location->getOption('test_option'));
});

it('should create a latitude and longitude for the location when requested and address data is provided', function () {
    $location = Location::factory()->make();
    $location->setOption('auto_lat_lng', true);
    $location->location_address_1 = '53 Church Road';
    $location->location_city = 'London';
    $location->location_postcode = 'SE19 2TJ';
    $location->location_lat = null;
    $location->location_lng = null;
    $location->save();

    $this->assertNotNull($location->location_lat);
    $this->assertNotNull($location->location_lng);
});

it('should fail to create a location with a duplicate slug', function () {
    $location1 = Location::factory()->make();
    $location1->permalink_slug = 'test';
    $location1->save();

    $location2 = Location::factory()->make();
    $location2->permalink_slug = 'test';
    $location2->save();

    $this->assertFalse(true);
});

it('should filter locations by status', function () {
    $location1 = Location::factory()->make();
    $location1->location_status = true;
    $location1->save();

    $location2 = Location::factory()->make();
    $location2->location_status = false;
    $location2->save();

    $locations = Location::query()->isEnabled()->get();

    $this->assertCount(1, $locations);
});

it('should filter locations by offer delivery', function () {
    $location1 = Location::factory()->make();
    $location1->save();
    $location1->setOption('offer_delivery', true);

    $location2 = Location::factory()->make();
    $location2->save();
    $location2->setOption('offer_delivery', false);

    $locations = Location::query()->listFrontEnd([
        'hasDelivery' => true,
        'pageLimit' => null,
    ])->get();

    $this->assertCount(1, $locations);
});

it('should filter locations by offer collection', function () {
    $location1 = Location::factory()->make();
    $location1->save();
    $location1->setOption('offer_collection', false);

    $location2 = Location::factory()->make();
    $location2->save();
    $location2->setOption('offer_collection', true);

    $locations = Location::query()->listFrontEnd([
        'hasCollection' => true,
        'pageLimit' => null,
    ])->get();

    $this->assertCount(1, $locations);
});

it('should sort locations alphabetically by name ascending', function () {
    $location1 = Location::factory()->make();
    $location1->location_name = 'Test 1';
    $location1->save();

    $location2 = Location::factory()->make();
    $location2->location_name = 'A Test 2';
    $location2->save();

    $locations = Location::query()->listFrontEnd([
        'hasCollection' => true,
        'pageLimit' => null,
        'sort' => 'location_name asc',
    ])->get();

    $this->assertSame($locations->first()->location_name, $location2->location_name);
});

it('should sort locations alphabetically by name descending', function () {
    $location1 = Location::factory()->make();
    $location1->location_name = 'Test 1';
    $location1->save();

    $location2 = Location::factory()->make();
    $location2->location_name = 'A Test 2';
    $location2->save();

    $locations = Location::query()->listFrontEnd([
        'hasCollection' => true,
        'pageLimit' => null,
        'sort' => 'location_name desc',
    ])->get();

    $this->assertSame($locations->first()->location_name, $location1->location_name);
});

it('can be made default when enabled', function () {
    $location = Location::factory()->make();
    $location->location_status = true;
    $location->save();

    $location->makeDefault();

    $this->assertSame(Location::getDefault()->getKey(), $location->getKey());
});

it('cant be made default when not enabled', function () {
    try {

        $location = Location::factory()->make();
        $location->location_status = false;
        $location->save();

        $location->makeDefault();

        $this->assertNotSame(Location::getDefault()->getKey(), $location->getKey());

    } catch (\Exception $e) {
        $this->assertFalse(false);
    }
});
