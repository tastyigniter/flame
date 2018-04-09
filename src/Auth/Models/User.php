<?php

namespace Igniter\Flame\Auth\Models;

use Carbon\Carbon;
use Hash;
use Igniter\Flame\Database\Model;

class User extends Model
{
    const REMEMBER_TOKEN_NAME = 'remember_token';

    protected static $resetExpiration = 1440;

    public function beforeLogin()
    {

    }

    public function afterLogin()
    {

    }

    public function setPasswordAttribute($value)
    {
        if ($this->exists AND empty($value)) {
            unset($this->attributes['password']);
        }
        else {
            $this->attributes['password'] = Hash::make($value);

            // Password has changed, log out all users
            $this->attributes['remember_token'] = null;
        }
    }

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
     * Generate a unique hash for this order.
     * @return string
     */
    protected function generateResetCode()
    {
        $random = str_random(42);
        while ($this->newQuery()->where('reset_code', $random)->count() > 0) {
            $random = str_random(42);
        }

        return $random;
    }

    /**
     * Sets the reset password columns to NULL
     */
    public function clearResetPasswordCode()
    {
        $this->reset_code = null;
        $this->reset_time = null;
        $this->save();
    }

    /**
     * Sets the new password on user requested reset
     *
     * @param $code
     * @param $password
     *
     * @return bool
     * @throws \Exception
     */
    public function completeResetPassword($code, $password)
    {
        if (!$this->checkResetPasswordCode($code))
            return FALSE;

        $this->password = $password;
        $this->reset_time = null;
        $this->reset_code = null;

        return $this->save();
    }

    /**
     * Checks if the provided user reset password code is valid without actually resetting the password.
     *
     * @param string $resetCode
     *
     * @return bool
     */
    public function checkResetPasswordCode($resetCode)
    {
        if ($this->reset_code != $resetCode)
            return FALSE;

        $expiration = self::$resetExpiration;
        if ($expiration > 0) {
            if (Carbon::now()->gte($this->reset_time->addMinutes($expiration))) {
                // Reset password request has expired, so clear code.
                $this->clearResetPasswordCode();

                return FALSE;
            }
        }

        return TRUE;
    }

    public function getReminderEmail()
    {
        return $this->email;
    }
}