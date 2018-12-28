<?php

namespace Igniter\Flame\Auth\Models;

use Carbon\Carbon;
use Exception;
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

    public function updateRememberToken($token)
    {
        $this->setRememberToken($token);
        $this->save();
    }

    //
    // Password
    //

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

    //
    // Reset
    //

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

    //
    // Activation
    //

    public function getActivationCode()
    {
        $this->activation_code = $activationCode = $this->generateActivationCode();

        $this->save();

        return $activationCode;
    }

    /**
     * Attempts to activate the given user by checking the activate code. If the user is activated already, an Exception is thrown.
     * @param string $activationCode
     * @return bool
     */
    public function completeActivation($activationCode)
    {
        if ($this->is_activated) {
            throw new Exception('User is already active!');
        }

        if ($activationCode == $this->activation_code) {
            $this->activation_code = null;
            $this->status = TRUE;
            $this->is_activated = TRUE;
            $this->date_activated = $this->freshTimestamp();
            $this->save();

            return TRUE;
        }

        return FALSE;
    }

    protected function generateActivationCode()
    {
        $random = str_random(42);
        while ($this->newQuery()->where('activation_code', $random)->count() > 0) {
            $random = str_random(42);
        }

        return $random;
    }
}