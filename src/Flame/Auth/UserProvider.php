<?php

namespace Igniter\Flame\Auth;

use Igniter\Flame\Database\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;

class UserProvider implements \Illuminate\Contracts\Auth\UserProvider
{
    protected $config;

    /**
     * CustomerProvider constructor.
     */
    public function __construct($config = null)
    {
        $this->config = $config;
    }

    public function retrieveById($identifier)
    {
        return $this->createModelQuery()->find($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        $query = $this->createModelQuery();
        $model = $query->getModel();

        return $query
            ->where($model->getAuthIdentifierName(), $identifier)
            ->where($model->getRememberTokenName(), $token)
            ->first();
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        $user->setRememberToken($token);

        $timestamps = $user->timestamps;

        $user->timestamps = false;

        $user->save();

        $user->timestamps = $timestamps;
    }

    public function retrieveByCredentials(array $credentials)
    {
        $query = $this->createModelQuery();

        foreach ($credentials as $key => $value) {
            if (!contains_substring($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $plain = $credentials['password'];

        // Backward compatibility to turn SHA1 passwords to BCrypt
        if ($user->hasShaPassword($plain)) {
            $user->updateHashPassword($plain);
        }

        return Hash::check($plain, $user->getAuthPassword());
    }

    public function register(array $attributes, $activate = false)
    {
        return $this->createModel()->register($attributes, $activate);
    }

    /**
     * Prepares a query derived from the user model.
     */
    protected function createModelQuery()
    {
        $model = $this->createModel();
        $query = $model->newQuery();

        $model->extendUserQuery($query);

        return $query;
    }

    protected function createModel(): Model
    {
        return new $this->config['model'];
    }
}
