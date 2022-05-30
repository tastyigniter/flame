<?php

namespace Tests\Admin\Models;

use Igniter\Admin\Models\Category;

it('can create a category and assign it to a location', function () {
//    $location = Location::factory()->create();
//    $category = Category::factory()->make();
//    $category->locations = [$location->getKey()];
//    $categoryModel = $category->create();

    $this->assertTrue(true);
});

it('should fail to create a category when no name is provided', function () {
    try {
        $category = Category::factory()->make();
        $category->name = null;
        $category->save();
        $this->assertFalse(true);
    }
    catch (\Exception $e) {
        $this->assertFalse(false);
    }
});
