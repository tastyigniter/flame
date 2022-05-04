<?php

namespace Igniter\Flame\Auth;

use Exception;
use Igniter\Flame\Auth\Models\User;
use Igniter\Flame\Auth\Models\User as UserModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * Auth Manager Class
 * Adapted from Ion Auth.
 * @link https://github.com/benedmunds/CodeIgniter-Ion-Auth
 */
class Manager
{
    const AUTH_KEY_NAME = 'auth';

    protected $sessionKey;

    /**
     * @var \Igniter\Flame\Auth\Models\User The currently authenticated user.
     */
    protected $user;

    /**
     * @var string The user model to use
     */
    protected $model;

    protected $requireApproval = false;

    /**
     * @var bool Internal flag to toggle using the session for the current authentication request
     */
    protected $useSession = true;

    /**
     * @var bool Indicates if the user was authenticated via a recaller cookie.
     */
    protected $viaRemember = false;

    /**
     * Determine if the current user is authenticated.
     */
    public function check()
    {
        if (is_null($this->user)) {
            // Load the user using session identifier
            if ($sessionData = Session::get($this->sessionKey)) {
                $userData = $sessionData;
            }
            // If no user is found in session,
            // load the user using cookie token
            elseif ($cookieData = Cookie::get($this->sessionKey)) {
                $this->viaRemember = true;
                $userData = @json_decode($cookieData, true);
            }
            else {
                return false;
            }

            if (!is_array($userData) || count($userData) !== 2)
                return false;

            [$userId, $rememberToken] = $userData;

            if (!$user = $this->getById($userId))
                return false;

            if (!$user->checkRememberToken($rememberToken))
                return false;

            $this->user = $user;
        }

        if (!($user = $this->getUser()))
            return false;

        if ($this->requireApproval && $user && !$user->is_activated) {
            $this->user = null;

            return false;
        }

        return true;
    }

    /**
     * Determine if the current user is a guest.
     */
    public function guest()
    {
        return !$this->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Igniter\Flame\Auth\Models\User
     */
    public function user()
    {
        if (is_null($this->user)) {
            $this->check();
        }

        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user.
     * @return int|null
     */
    public function id()
    {
        return $this->user()->getAuthIdentifier();
    }

    /**
     * Get the currently authenticated user model.
     * @return \Igniter\Flame\Auth\Models\User
     */
    public function getUser()
    {
        return $this->user();
    }

    /**
     * Set the current user model
     *
     * @param $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Validate a user using the given credentials.
     *
     * @param array $credentials
     * @param bool $remember
     * @param bool $login
     *
     * @return \Igniter\Flame\Auth\Models\User|bool
     * @throws \Exception
     */
    public function authenticate(array $credentials = [], $remember = false, $login = true)
    {
        $user = $this->getByCredentials($credentials);

        // Validate the user against the given credentials,
        // if valid log the user into the application
        if (is_null($user) || !$this->validateCredentials($user, $credentials)) {
            return false;
        }

        $user->clearResetPasswordCode();

        if ($login) $this->login($user, $remember);

        return $this->user;
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param array $credentials
     * @return bool
     */
    public function once($credentials = [])
    {
        $this->useSession = false;

        $user = $this->authenticate($credentials);

        $this->useSession = true;

        return (bool)$user;
    }

    /**
     * Log the given user ID into the application without sessions or cookies.
     *
     * @param mixed $id
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function onceUsingId($id)
    {
        if (!is_null($user = $this->getById($id))) {
            $this->setUser($user);

            return $user;
        }

        return false;
    }

    /**
     * Log a user into the application.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @param bool $remember
     *
     * @throws \Exception
     */
    public function login(Authenticatable $user, $remember = false)
    {
        $user->beforeLogin();

        // Approval is required, user not approved
        if ($this->requireApproval && !$user->is_activated) {
            throw new Exception(sprintf(
                'Cannot login user "%s" until activated.', $user->getAuthIdentifier()
            ));
        }

        $this->setUser($user);

        if ($this->useSession) {
            $toPersist = $this->getPersistData($user);
            Session::put($this->sessionKey, $toPersist);

            // If the user should be permanently "remembered" by the application.
            if ($remember) {
                Cookie::queue(Cookie::forever($this->sessionKey, json_encode($toPersist)));
            }
        }

        $user->afterLogin();
    }

    /**
     * Log the given user ID into the application.
     *
     * @param $id
     * @param bool $remember
     *
     * @return mixed
     * @throws \Exception
     */
    public function loginUsingId($id, $remember = false)
    {
        if (!is_null($user = $this->getById($id))) {
            $this->login($user, $remember);

            return $user;
        }

        return false;
    }

    /**
     * Log the user out of the application.
     * @return void
     **/
    public function logout()
    {
        if (is_null($this->user) && !$this->check())
            return;

        if ($this->isImpersonator()) {
            $this->user = $this->getImpersonator();
            $this->stopImpersonate();

            return;
        }

        if ($this->user)
            $this->user->updateRememberToken(null);

        $this->user = null;

        Session::forget($this->sessionKey);

        // delete the remember me cookies if they exist
        Cookie::queue(Cookie::forget($this->sessionKey));
    }

    /**
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember()
    {
        return $this->viaRemember;
    }

    //
    // User
    //

    /**
     * @param $identifier
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|\Igniter\Flame\Auth\Models\User
     */
    public function getById($identifier)
    {
        $query = $this->createModelQuery();

        return $query->find($identifier);
    }

    /**
     * @param $identifier
     * @param $token
     *
     * @return mixed
     * @throws \Exception
     */
    public function getByToken($identifier, $token)
    {
        $model = $this->createModel();
        $query = $this->createModelQuery();

        return $query
            ->where($model->getAuthIdentifierName(), $identifier)
            ->where($model->getRememberTokenName(), $token)
            ->first();
    }

    /**
     * @param array $credentials
     *
     * @return null|\Igniter\Flame\Auth\Models\User
     */
    public function getByCredentials(array $credentials)
    {
        if (empty($credentials))
            return null;

        $query = $this->createModelQuery();

        foreach ($credentials as $key => $value) {
            if (!contains_substring($key, 'password')) {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    public function validateCredentials(UserModel $user, $credentials)
    {
        $plain = $credentials['password'];

        // Backward compatibility to turn SHA1 passwords to BCrypt
        if ($user->hasShaPassword($plain)) {
            $user->updateHashPassword($plain);
        }

        return Hash::check($plain, $user->getAuthPassword());
    }

    /**
     * Create a new instance of the model
     * if it does not already exist.
     * @return mixed
     * @throws \Exception
     */
    public function createModel()
    {
        if (!isset($this->model))
            throw new Exception(sprintf('Required property [model] missing in %s', get_called_class()));

        $modelClass = $this->model;
        if (!class_exists($modelClass))
            throw new Exception(sprintf('Missing model [%s] in %s', $modelClass, get_called_class()));

        return new $modelClass();
    }

    /**
     * Prepares a query derived from the user model.
     */
    protected function createModelQuery()
    {
        $model = $this->createModel();
        $query = $model->newQuery();
        $this->extendUserQuery($query);

        return $query;
    }

    /**
     * Extend the query used for finding the user.
     *
     * @param \Igniter\Flame\Database\Builder $query
     *
     * @return void
     */
    public function extendUserQuery($query)
    {
    }

    /**
     * Gets the name of the user model
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the name of the user model
     *
     * @param $model
     *
     * @return $this
     */
    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Create a new "remember me" token for the user
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable|\Igniter\Flame\Auth\Models\User $user
     * @return array
     */
    protected function getPersistData($user)
    {
        $user->updateRememberToken(Str::random(42));

        return [$user->getKey(), $user->getRememberToken()];
    }

    //
    // Impersonation
    //

    /**
     * Impersonates the given user and sets properties
     * in the session but not the cookie.
     *
     * @param \Igniter\Flame\Auth\Models\User $user
     *
     * @throws \Exception
     */
    public function impersonate($user)
    {
        $oldSession = Session::get($this->sessionKey);
        $oldUser = !empty($oldSession[0]) ? $this->getById($oldSession[0]) : false;

        $user->fireEvent('model.auth.beforeImpersonate', [$oldUser]);
        $this->login($user, false);

        if (!$this->isImpersonator()) {
            Session::put($this->sessionKey.'_impersonate', $oldSession);
        }
    }

    public function stopImpersonate()
    {
        $currentSession = Session::get($this->sessionKey);
        $currentUser = !empty($currentSession[0]) ? $this->getById($currentSession[0]) : false;

        $oldSession = Session::pull($this->sessionKey.'_impersonate');
        $oldUser = !empty($oldSession[0]) ? $this->getById($oldSession[0]) : false;

        if ($currentUser) {
            $currentUser->fireEvent('model.auth.afterImpersonate', [$oldUser]);
        }

        if ($oldSession)
            Session::put($this->sessionKey, $oldSession);
    }

    public function isImpersonator()
    {
        return Session::has($this->sessionKey.'_impersonate');
    }

    public function getImpersonator()
    {
        $impersonateArray = Session::get($this->sessionKey.'_impersonate');

        // Check supplied session/cookie is an array (user id, persist code)
        if (!is_array($impersonateArray) || count($impersonateArray) !== 2)
            return false;

        $id = $impersonateArray[0];

        return $this->createModel()->find($id);
    }
}
