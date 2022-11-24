<?php

namespace Tests\System\Requests;

use Igniter\System\Requests\MailLayout;

it('has required rule for inputs', function () {
    $rules = (new MailLayout)->rules();

    expect('required')->toBeIn(array_get($rules, 'name'));

    expect('required')->toBeIn(array_get($rules, 'code'));
});

it('has regex rule for code input', function () {
    expect('regex:/^[a-z-_\.\:]+$/i')->toBeIn(array_get((new MailLayout())->rules(), 'code'));
});

it('has max characters rule for code input', function () {
    expect('between:2,32')->toBeIn(array_get((new MailLayout)->rules(), 'name'));
});
