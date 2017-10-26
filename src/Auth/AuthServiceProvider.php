<?php

namespace Igniter\Flame\ActivityLog;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as BaseAuthServiceProvider;

/**
 * Class AuthServiceProvider
 */
class AuthServiceProvider extends BaseAuthServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        // Intentionally left blank.
    }
}