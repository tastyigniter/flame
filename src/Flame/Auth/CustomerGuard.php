<?php

namespace Igniter\Flame\Auth;

/**
 * Customer Class
 */
class CustomerGuard extends \Illuminate\Auth\SessionGuard
{
    use GuardHelpers;

    public function customer()
    {
        return $this->user();
    }

    public function isLogged()
    {
        return $this->check();
    }

    public function getId()
    {
        return $this->user->customer_id;
    }

    public function getFullName()
    {
        return $this->user->full_name;
    }

    public function getFirstName()
    {
        return $this->user->first_name;
    }

    public function getLastName()
    {
        return $this->user->last_name;
    }

    public function getEmail()
    {
        return strtolower($this->user->email);
    }

    public function getTelephone()
    {
        return $this->user->telephone;
    }

    public function getAddressId()
    {
        return $this->user->address_id;
    }

    public function getGroupId()
    {
        return $this->user->customer_group_id;
    }

    /**
     * Registers a user by giving the required credentials
     *
     * @param array $credentials
     *
     * @param bool $activate
     * @return \Igniter\Main\Models\Customer
     * @throws \Exception
     */
    public function register(array $attributes, $activate = false)
    {
        $model = $this->createModel();
        $model->fill($attributes);
        $model->save();

        if ($activate) {
            $model->completeActivation($model->getActivationCode());
        }

        // Prevents subsequent saves to this model object
        $model->password = null;

        return $this->user = $model;
    }
}
