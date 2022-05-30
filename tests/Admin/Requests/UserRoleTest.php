<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\UserRole;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new UserRole)->rules(), 'name'));

    expect('required')->toBeIn(array_get((new UserRole)->rules(), 'permissions'));

    expect('required')->toBeIn(array_get((new UserRole)->rules(), 'permissions.*'));
});

it('has max characters rule for inputs', function () {
    expect('between:2,32')->toBeIn(array_get((new UserRole)->rules(), 'code'));

    expect('between:2,128')->toBeIn(array_get((new UserRole)->rules(), 'name'));
});

it('has alpha_dash rule for inputs', function () {
    expect('alpha_dash')->toBeIn(array_get((new UserRole)->rules(), 'code'));
});

it('has unique:user_roles rule for inputs', function () {
    expect('unique:user_roles')->toBeIn(array_get((new UserRole)->rules(), 'name'));
});
