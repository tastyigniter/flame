<?php

namespace Igniter\Admin\Traits;

use Igniter\Admin\Facades\AdminAuth;
use Illuminate\Contracts\Auth\Access\Gate;

/**
 * Has Authentication Trait Class
 */
trait HasAuthentication
{
    /**
     * @var \Igniter\Admin\Models\User Stores the logged in admin user model.
     */
    protected $currentUser;

    public function checkUser()
    {
        return AdminAuth::check();
    }

    public function setUser($currentUser)
    {
        $this->currentUser = $currentUser;
    }

    public function getUser()
    {
        return $this->currentUser;
    }

    public function authorize($ability)
    {
        if (is_array($ability))
            $ability = implode(',', $ability);

        return app(Gate::class)->authorize($ability);
    }
}
