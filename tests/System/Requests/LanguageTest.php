<?php

namespace Tests\System\Requests;

use Igniter\System\Requests\Language;

it('has required rule for inputs', function () {
    $rules = (new Language)->rules();

    expect('required')->toBeIn(array_get($rules, 'name'));

    expect('required')->toBeIn(array_get($rules, 'code'));

    expect('required')->toBeIn(array_get($rules, 'status'));
});

it('has unique rule for code input', function () {
    expect('unique:languages')->toBeIn(array_get((new Language)->rules(), 'code'));
});

it('has max characters rule for code input', function () {
    expect('between:2,32')->toBeIn(array_get((new Language)->rules(), 'name'));
    expect('max:2500')->toBeIn(array_get((new Language)->rules(), 'translations.*.source'));
    expect('max:2500')->toBeIn(array_get((new Language)->rules(), 'translations.*.translation'));
});
