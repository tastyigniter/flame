<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\User;

it('has required rule for inputs', function () {
    expect('required')->toBeIn(array_get((new User)->rules(), 'name'));

    expect('required')->toBeIn(array_get((new User)->rules(), 'email'));

    expect('required')->toBeIn(array_get((new User)->rules(), 'username'));

    expect('required_if:send_invite,0')->toBeIn(array_get((new User)->rules(), 'password'));

    expect('required')->toBeIn(array_get((new User)->rules(), 'user_role_id'));

    expect('required')->toBeIn(array_get((new User)->rules(), 'groups'));
});

it('has sometimes rule for inputs', function () {
    expect('sometimes')->toBeIn(array_get((new User)->rules(), 'password'));

    expect('sometimes')->toBeIn(array_get((new User)->rules(), 'user_role_id'));

    expect('sometimes')->toBeIn(array_get((new User)->rules(), 'groups'));
});

it('has max characters rule for inputs', function () {
    expect('between:2,128')->toBeIn(array_get((new User)->rules(), 'name'));

    expect('max:96')->toBeIn(array_get((new User)->rules(), 'email'));

    expect('email:filter')->toBeIn(array_get((new User)->rules(), 'email'));

    expect('between:2,32')->toBeIn(array_get((new User)->rules(), 'username'));

    expect('between:6,32')->toBeIn(array_get((new User)->rules(), 'password'));
});

it('has unique rule for inputs', function () {
    expect('unique:users,email')->toBeIn(array_get((new User)->rules(), 'email'));

    expect('unique:users,username')->toBeIn(array_get((new User)->rules(), 'username'));
});
