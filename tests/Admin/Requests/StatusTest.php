<?php

namespace Tests\Admin\Requests;

use Igniter\Admin\Requests\Status;

it('has required rule for inputs', function () {
    $rules = (new Status)->rules();

    expect('required')->toBeIn(array_get($rules, 'status_name'));
    expect('required')->toBeIn(array_get($rules, 'status_for'));
    expect('required')->toBeIn(array_get($rules, 'notify_customer'));
});

it('has max characters rule for inputs', function () {
    $rules = (new Status)->rules();

    expect('between:2,32')->toBeIn(array_get($rules, 'status_name'));
    expect('max:7')->toBeIn(array_get($rules, 'status_color'));
    expect('max:1028')->toBeIn(array_get($rules, 'status_comment'));
});

it('has in:order,reservation rule for inputs', function () {
    $rules = (new Status)->rules();

    expect('in:order,reservation')->toBeIn(array_get($rules, 'status_for'));
});
