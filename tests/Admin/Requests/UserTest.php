<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\User;

it('has required rule for inputs', function () {
    $rules = (new User)->rules();

    expect('required')->toBeIn(array_get($rules, 'name'));
    expect('required')->toBeIn(array_get($rules, 'email'));
    expect('required')->toBeIn(array_get($rules, 'username'));
    expect('required_if:send_invite,0')->toBeIn(array_get($rules, 'password'));
    expect('required')->toBeIn(array_get($rules, 'user_role_id'));
    expect('required')->toBeIn(array_get($rules, 'groups'));
});

it('has sometimes rule for inputs', function () {
    $rules = (new User)->rules();

    expect('sometimes')->toBeIn(array_get($rules, 'password'));
    expect('sometimes')->toBeIn(array_get($rules, 'user_role_id'));
    expect('sometimes')->toBeIn(array_get($rules, 'groups'));
});

it('has max characters rule for inputs', function () {
    $rules = (new User)->rules();

    expect('between:2,128')->toBeIn(array_get($rules, 'name'));
    expect('max:96')->toBeIn(array_get($rules, 'email'));
    expect('email:filter')->toBeIn(array_get($rules, 'email'));
    expect('between:2,32')->toBeIn(array_get($rules, 'username'));
    expect('between:6,32')->toBeIn(array_get($rules, 'password'));
});

it('has unique rule for inputs', function () {
    $rules = (new User)->rules();

    expect('unique:admin_users,email')->toBeIn(array_get($rules, 'email'));
    expect('unique:admin_users,username')->toBeIn(array_get($rules, 'username'));
});
