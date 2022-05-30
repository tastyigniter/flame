<?php

namespace Igniter\Flame\Auth;

class UserGuard extends \Illuminate\Auth\SessionGuard
{
    use GuardHelpers;

    public function isLogged()
    {
        return $this->check();
    }

    public function isSuperUser()
    {
        return $this->user()->isSuperUser();
    }

    /**
     * @return \Igniter\Admin\Models\User|\Illuminate\Contracts\Auth\Authenticatable
     */
    public function staff()
    {
        return $this->user();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function locations()
    {
        return $this->user()->locations;
    }

    //
    //
    //

    public function getId()
    {
        return $this->id();
    }

    public function getUserName()
    {
        return $this->user()->username;
    }

    public function getUserEmail()
    {
        return $this->user()->email;
    }

    public function getStaffName()
    {
        return $this->user()->name;
    }

    public function getStaffEmail()
    {
        return $this->user()->email;
    }

    public function register(array $attributes, $activate = false)
    {
        return $this->getProvider()->register($attributes, $activate);
    }
}
