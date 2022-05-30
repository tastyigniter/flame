<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Ingredient;

it('has rules for name input', function () {
    $rules = array_get((new Ingredient)->rules(), 'name');

    expect('required')->toBeIn($rules);

    expect('between:2,128')->toBeIn($rules);

    expect('unique:ingredients')->toBeIn($rules);
});

it('has rules for description input', function () {
    expect('min:2')->toBeIn(array_get((new Ingredient)->rules(), 'description'));
});
