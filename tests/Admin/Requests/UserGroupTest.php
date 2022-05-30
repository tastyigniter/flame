<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\UserGroup;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new UserGroup)->rules(), 'user_group_name'));

    expect('required')->toBeIn(array_get((new UserGroup)->rules(), 'auto_assign'));

    expect('required_if:auto_assign,true')->toBeIn(array_get((new UserGroup)->rules(), 'auto_assign_mode'));

    expect('required_if:auto_assign_mode,2')->toBeIn(array_get((new UserGroup)->rules(), 'auto_assign_limit'));

    expect('required_if:auto_assign,true')->toBeIn(array_get((new UserGroup)->rules(), 'auto_assign_availability'));
});

it('has max characters rule for inputs', function () {
    expect('between:2,128')->toBeIn(array_get((new UserGroup)->rules(), 'user_group_name'));
    expect('max:2')->toBeIn(array_get((new UserGroup)->rules(), 'auto_assign_mode'));
    expect('max:99')->toBeIn(array_get((new UserGroup)->rules(), 'auto_assign_limit'));
});
