<?php

namespace Tests\Admin\Requests;

use Igniter\Main\Requests\CustomerGroup;

it('has rules for group_name field', function () {
    $rules = array_get((new CustomerGroup)->rules(), 'group_name');

    expect('required')->toBeIn($rules);

    expect('between:2,32')->toBeIn($rules);
});

it('has rules for description field', function () {
    $rules = array_get((new CustomerGroup)->rules(), 'description');

    expect('string')->toBeIn($rules);

    expect('between:2,512')->toBeIn($rules);
});
