<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Category;

it('has rules for name input', function () {
    $rules = array_get((new Category)->rules(), 'name');

    expect('required')->toBeIn($rules);

    expect('between:2,128')->toBeIn($rules);

    expect('unique:categories')->toBeIn($rules);
});

it('has rules for permalink slug input', function () {
    $rules = array_get((new Category)->rules(), 'permalink_slug');

    expect('alpha_dash')->toBeIn($rules);

    expect('max:255')->toBeIn($rules);
});
