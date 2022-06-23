<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\UserRole;

it('has required rule for inputs', function () {
    $rules = (new UserRole)->rules();

    expect('required')->toBeIn(array_get($rules, 'name'));
    expect('required')->toBeIn(array_get($rules, 'permissions'));
    expect('required')->toBeIn(array_get($rules, 'permissions.*'));
});

it('has max characters rule for inputs', function () {
    $rules = (new UserRole)->rules();

    expect('between:2,32')->toBeIn(array_get($rules, 'code'));
    expect('between:2,128')->toBeIn(array_get($rules, 'name'));
});

it('has alpha_dash rule for inputs', function () {
    $rules = (new UserRole)->rules();

    expect('alpha_dash')->toBeIn(array_get($rules, 'code'));
});

it('has unique:admin_user_roles rule for inputs', function () {
    $rules = (new UserRole)->rules();

    expect('unique:admin_user_roles')->toBeIn(array_get($rules, 'name'));
});
