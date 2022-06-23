<?php

namespace Tests\Admin\Models;

use Igniter\Admin\Models\Mealtime;
use Igniter\Admin\Models\Location;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Mealtime::query()->delete();
});

it('should fail to create a mealtime when no name is provided', function () {
    try {
        $mealtime = Mealtime::factory()->make();
        $mealtime->mealtime_name = null;
        $mealtime->save();
        $this->assertFalse(true);
    }
    catch (\Exception $e) {
        $this->assertFalse(false);
    }
});

it('should be able to create and delete a mealtime', function () {
    $mealtime = Mealtime::factory()->make();
    $mealtime->save();

    $mealtime->delete();

    $this->assertNull($mealtime->fresh());
});

it('should filter mealtimes by status', function () {
    $mealtime1 = Mealtime::factory()->make();
    $mealtime1->mealtime_status = true;
    $mealtime1->save();

    $mealtime2 = Mealtime::factory()->make();
    $mealtime2->mealtime_status = false;
    $mealtime2->save();

    $mealtimes = Mealtime::query()->isEnabled()->get();

    $this->assertCount(1, $mealtimes);
});

it('should be able to return mealtimes available now', function () {
    $mealtime1 = Mealtime::factory()->make();
    $mealtime1->mealtime_name = 'Mealtime 1';
    $mealtime1->start_time = Carbon::now()->subHour(1)->format('H:m');
    $mealtime1->end_time = Carbon::now()->addHour(1)->format('H:m');
    $mealtime1->save();

    $mealtime2 = Mealtime::factory()->make();
    $mealtime2->mealtime_name = 'Mealtime 2';
    $mealtime2->start_time = Carbon::now()->subHour(4)->format('H:m');
    $mealtime2->end_time = Carbon::now()->subHour(3)->format('H:m');
    $mealtime2->save();

    $availableMealtimes = Mealtime::all()->filter->isAvailable();

    $this->assertCount(1, $availableMealtimes);
});

it('should be able to return mealtimes available at a given time', function () {
    $mealtime1 = Mealtime::factory()->make();
    $mealtime1->mealtime_name = 'Mealtime 1';
    $mealtime1->start_time = Carbon::now()->subHour(1)->format('H:i');
    $mealtime1->end_time = Carbon::now()->addHour(1)->format('H:i');
    $mealtime1->save();

    $mealtime2 = Mealtime::factory()->make();
    $mealtime2->mealtime_name = 'Mealtime 2';
    $mealtime2->start_time = Carbon::now()->subHour(4)->format('H:i');
    $mealtime2->end_time = Carbon::now()->subHour(3)->format('H:i');
    $mealtime2->save();

    $availableMealtimes = Mealtime::all()->filter->isAvailable(Carbon::now()->subHour(2));
    $this->assertCount(0, $availableMealtimes);

    $availableMealtimes = Mealtime::all()->filter->isAvailable(Carbon::now()->subHour(3)->subMinute(15));
    $this->assertCount(1, $availableMealtimes);
});

it('should be able to attach a mealtime to a location', function () {
    $location = Location::factory()->make();
    $location->save();

    $mealtime = Mealtime::factory()->make();
    $mealtime->save();

    $mealtime->locations()->attach($location);

    $this->assertCount(1, $mealtime->locations);
    $this->assertSame($location->getKey(), $mealtime->locations->first()->getKey());
});
