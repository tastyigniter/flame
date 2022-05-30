<?php

namespace Tests;

use Igniter\Flame\Igniter;

it('checks for admin routes', function () {
    $this->get('/admin');
    expect(Igniter::runningInAdmin())->toBeTrue();

    $this->get('/admin-login');
    expect(Igniter::runningInAdmin())->toBeFalse();

    $this->get('/admin/login');
    expect(Igniter::runningInAdmin())->toBeTrue();
});
