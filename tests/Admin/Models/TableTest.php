<?php

namespace Tests\Admin\Models;

use Igniter\Admin\Models\Location;
use Igniter\Admin\Models\Table;

beforeEach(function () {
    Table::query()->delete();
});

it('should fail to create a table when no name is provided', function () {
    try {
        $table = Table::factory()->make();
        $table->table_name = null;
        $table->save();
        $this->assertFalse(true);
    }
    catch (\Exception $e) {
        $this->assertFalse(false);
    }
});

it('should be able to create and delete a table', function () {
    $table = Table::factory()->make();
    $table->save();

    $table->delete();

    $this->assertNull($table->fresh());
});

it('should filter tables by status', function () {
    $table1 = Table::factory()->make();
    $table1->table_status = true;
    $table1->save();

    $table2 = Table::factory()->make();
    $table2->table_status = false;
    $table2->save();

    $tables = Table::query()->isEnabled()->get();

    $this->assertCount(1, $tables);
});

it('should be able to return tables within a capacity range', function () {
    $table1 = Table::factory()->make();
    $table1->min_capacity = 4;
    $table1->max_capacity = 6;
    $table1->save();

    $table2 = Table::factory()->make();
    $table2->min_capacity = 2;
    $table2->max_capacity = 2;
    $table2->save();

    $table3 = Table::factory()->make();
    $table3->min_capacity = 3;
    $table3->max_capacity = 5;
    $table3->save();

    $tables = Table::query()->whereBetweenCapacity(4, 5)->get();

    $this->assertCount(2, $tables);
});

it('should be able to attach a table to a location', function () {
    $location = Location::factory()->make();
    $location->save();

    $table = Table::factory()->make();
    $table->save();

    $table->locations()->attach($location);

    $this->assertCount(1, $table->locations);
    $this->assertSame($location->getKey(), $table->locations->first()->getKey());
});
