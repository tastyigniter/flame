<?php

namespace Igniter\Flame\Auth\Models;

use Igniter\Flame\Database\Model;

class User extends Model
{
    const REMEMBER_TOKEN_NAME = 'remember_token';

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        $name = $this->getAuthIdentifierName();

        return $this->attributes[$name];
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->attributes['password'];
    }

    /**
     * Get the token value for the "remember me" session.
     */
    public function getRememberToken()
    {
        return $this->attributes[$this->getRememberTokenName()];
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $token
     */
    public function setRememberToken($token)
    {
        $this->attributes[$this->getRememberTokenName()] = $token;
    }

    /**
     * Get the column name for the "remember me" token.
     */
    public function getRememberTokenName()
    {
        return static::REMEMBER_TOKEN_NAME;
    }

    //
    //
    //

    public function updateRememberToken($token)
    {
        $this->setRememberToken($token);
        $this->save();
    }

    public function hasShaPassword($plainPassword)
    {
        $salt = $this->attributes['salt'];

        if (is_null($salt))
            return FALSE;

        $hashedPassword = $this->attributes['password'];
        $shaPassword = sha1($salt.sha1($salt.sha1($plainPassword)));

        return ($hashedPassword === $shaPassword);
    }

    public function updateHashPassword($hashedPassword)
    {
        $this->password = $hashedPassword;
        $this->salt = null;

        return $this->save();
    }

    /**
     * Sets the reset password columns to NULL
     *
     * @param string $code
     *
     * @return bool
     */
    public function clearResetPasswordCode($code)
    {
        if (is_null($code))
            return FALSE;

        $query = $this->newQuery()->where('reset_code', $code);

        if ($row = $query->isEnabled()->first()) {
            $query->update([
                'reset_code' => null,
                'reset_time' => null,
            ]);

            return TRUE;
        }

        return FALSE;
    }

    public function getReminderEmail()
    {
    }
}