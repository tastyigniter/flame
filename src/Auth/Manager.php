<?php

namespace Igniter\Flame\Auth;

use Carbon\Carbon;
use Cookie;
use Exception;
use Hash;
use Igniter\Flame\Auth\Models\User as UserModel;
use Illuminate\Support\Str;
use Session;

/**
 * Auth Manager Class
 * Adapted from Ion Auth.
 * @link https://github.com/benedmunds/CodeIgniter-Ion-Auth
 * @package        Igniter\Flame\Auth\Manager.php
 */
class Manager
{
    const AUTH_KEY_NAME = 'auth';

    protected $sessionKey;

    protected $hasher;

    /**
     * @var \Igniter\Flame\Auth\Models\User The currently authenticated user.
     */
    protected $user;

    /**
     * @var string The user model to use
     */
    protected $model;

    /**
     * @var string The user group model to use
     */
    protected $groupModel;

    /**
     * @var string The model identifier column (username or email)
     */
    protected $identifier;

    /**
     * @var bool Indicates if the logout method has been called.
     */
    protected $loggedOut;

    /**
     * Indicates if a token user retrieval has been attempted.
     * @var bool
     */
    protected $tokenRetrievalAttempted = FALSE;

    /**
     * @var string Number of seconds the reset password request expires,
     * Set to 0 to next expire
     **/
    protected $resetExpiration;

    protected $requireApproval = FALSE;

    /**
     * Determine if the current user is authenticated.
     */
    public function check()
    {
        if (is_null($this->user)) {
            $user = null;
            $sessionUserId = $this->getSessionUserId();
            $rememberCookie = $this->getRememberCookie();

            // Load the user using session identifier
            if ($sessionUserId) {
                $user = $this->getById($sessionUserId);
            }
            // If no user is found in session,
            // load the user using cookie token
            else if ($rememberCookie AND $user = $this->getUserByRememberCookie($rememberCookie)) {
                $this->updateSession($user);
            }

            if (is_null($user))
                return FALSE;

            $this->user = $user;
        }

        if (!($user = $this->getUser()) OR ($this->requireApproval AND !$user->is_activated))
            return FALSE;

        return TRUE;
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
        $id = $this->getSessionUserId();
        if (is_null($id) AND $this->user()) {
            $id = $this->user()->getAuthIdentifier();
        }

        return $id;
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
     * @param $userModel
     */
    public function setUser($userModel)
    {
        $this->user = $userModel;
    }

    /**
     * Validate a user using the given credentials.
     *
     * @param array $credentials
     * @param bool $remember
     * @param bool $login
     *
     * @return bool
     * @throws \Exception
     */
    public function authenticate(array $credentials = [], $remember = FALSE, $login = TRUE)
    {
        $userModel = $this->getByCredentials($credentials);

        // Validate the user against the given credentials,
        // if valid log the user into the application
        if (!is_null($userModel) AND $this->validateCredentials($userModel, $credentials)) {
            if ($login)
                $this->login($userModel, $remember);

            return TRUE;
        }

        return FALSE;
    }

    /**
     * Log a user into the application without sessions or cookies.
     *
     * @param array $credentials
     */
    public function loginOnce($credentials = [])
    {
        // @todo: implement
    }

    /**
     * Log a user into the application.
     *
     * @param \Igniter\Flame\Auth\Models\User $userModel
     * @param bool $remember
     *
     * @throws \Exception
     */
    public function login($userModel, $remember = FALSE)
    {
        $userModel->beforeLogin();

        // Approval is required, user not approved
        if ($this->requireApproval AND !$userModel->is_activated) {
            throw new Exception(sprintf(
                'Cannot login user "%s" until activated.', $userModel->getAuthIdentifier()
            ));
        }

        $this->setUser($userModel);

        // If the user should be permanently "remembered" by the application.
        if ($remember) {
            $this->createRememberToken($userModel);
            $this->rememberUser($userModel);
        }

        $this->updateSession($userModel);

        $userModel->afterLogin();
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
    public function loginUsingId($id, $remember = FALSE)
    {
        $userModel = $this->getById($id);
        $this->login($userModel, $remember);

        return $userModel;
    }

    /**
     * Log the user out of the application.
     * @return void
     **/
    public function logout()
    {
        $user = $this->user();

        // delete the remember me cookies if they exist
        if (!is_null($this->user))
            $this->refreshRememberToken($user, null);

        $this->user = null;

        $this->clearUserDataFromStore();

        $this->loggedOut = TRUE;
    }

    /**
     * @todo: Check whether authenticated user belongs to a group
     *
     * @param mixed $groupToCheck group(s) to check
     * @param bool $id user id
     * @param bool $checkAll if all groups is present, or any of the groups
     *
     * @return void
     */
    public function inGroup($groupToCheck, $id = FALSE, $checkAll = FALSE)
    {
        // @todo: implement
    }

    //
    // Providers
    //

    /**
     * @param $identifier
     *
     * @return mixed
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

    public function validateCredentials(UserModel $userModel, $credentials)
    {
        $plain = $credentials['password'];

        // Backward compatibility to turn SHA1 passwords to BCrypt
        if ($userModel->hasShaPassword($plain)) {
            $userModel->updateHashPassword($plain);
        }

        return Hash::check($plain, $userModel->getAuthPassword());
    }

    //
    // Model
    //

    /**
     * Create a new instance of the model
     * if it does not already exist.
     * @return mixed
     * @throws \Exception
     */
    protected function createModel()
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
     * Create a new instance of the group model
     * if it does not already exist.
     * @return mixed
     * @throws \Exception
     */
    protected function createGroupModel()
    {
        if (!isset($this->groupModel))
            throw new Exception(sprintf('Required property [groupModel] missing in %s', get_called_class()));

        $modelClass = $this->groupModel;
        if (!class_exists($modelClass))
            throw new Exception(sprintf('Missing model [%s] in %s', $modelClass, get_called_class()));

        return new $modelClass();
    }

    /**
     * Prepares a query derived from the user group model.
     */
    protected function createGroupModelQuery()
    {
        $model = $this->createGroupModel();
        $query = $model->newQuery();
        $this->extendUserGroupQuery($query);

        return $query;
    }

    /**
     * Extend the query used for finding the user group.
     *
     * @param \Igniter\Flame\Database\Builder $query
     *
     * @return void
     */
    public function extendUserGroupQuery($query)
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

    //
    // Session & Cookie
    //

    /**
     * @return mixed
     */
    public function getSessionUserId()
    {
        $sessionData = Session::get($this->sessionKey);

        return array_get($sessionData, static::AUTH_KEY_NAME.'.id', null);
    }

    protected function updateSession(UserModel $userModel)
    {
        $id = $userModel->getAuthIdentifier();
        $identityName = $userModel->getAuthIdentifierName();

        $sessionData = Session::get($this->sessionKey);
        $sessionData[static::AUTH_KEY_NAME] = [
            'id' => $id,
            $identityName => $id,
            $this->identifier => $userModel->{$this->identifier},
            'last_check' => Carbon::now(),
        ];

        Session::put($this->sessionKey, $sessionData);
    }

    /**
     * @param \Igniter\Flame\Auth\Models\User $userModel
     * @param null $token
     */
    protected function refreshRememberToken(UserModel $userModel, $token = null)
    {
        $userModel->updateRememberToken($token);
    }

    /**
     * Create a new "remember me" token for the user
     * if one doesn't already exist.
     *
     * @param $userModel
     */
    protected function createRememberToken(UserModel $userModel)
    {
        if (empty($userModel->getRememberToken())) {
            $this->refreshRememberToken($userModel, Str::random(60));
        }
    }

    /**
     * Get the decrypted remember cookie for the request.
     * @return string|null
     */
    protected function getRememberCookie()
    {
        return Cookie::get($this->getRememberCookieName());
    }

    /**
     * Get the user ID from the remember cookie.
     * @return string|null
     */
    protected function getRememberCookieId()
    {
        if ($this->validateRememberCookie($rememberCookie = $this->getRememberCookie())) {
            return reset(explode('|', $rememberCookie));
        }
    }

    /**
     * Determine if the remember cookie is in a valid format.
     *
     * @param  string $cookie
     *
     * @return bool
     */
    protected function validateRememberCookie($cookie)
    {
        if (!is_string($cookie) OR strpos($cookie, '|') === FALSE)
            return FALSE;

        $segments = explode('|', $cookie);

        return count($segments) == 2 AND !empty(trim($segments[0])) AND !empty(trim($segments[1]));
    }

    /**
     * Pull a user from the repository by its recaller ID.
     *
     * @param  string $rememberCookie
     *
     * @return mixed
     * @throws \Exception
     */
    protected function getUserByRememberCookie($rememberCookie)
    {
        if ($this->validateRememberCookie($rememberCookie) AND !$this->tokenRetrievalAttempted) {
            $this->tokenRetrievalAttempted = TRUE;
            list($id, $token) = explode('|', $rememberCookie, 2);
            $user = $this->getByToken($id, $token);

            return $user;
        }
    }

    protected function getRememberCookieName()
    {
        return 'remember_'.$this->sessionKey;
    }

    /**
     * Create a "remember me" cookie for a given ID.
     *
     * @param  object $userModel
     */
    protected function rememberUser($userModel)
    {
        $value = $userModel->getAuthIdentifier().'|'.$userModel->getRememberToken();
        Cookie::queue(Cookie::forever($this->getRememberCookieName(), $value));
    }

    /**
     * Remove the user data from the session and cookies.
     * @return void
     */
    protected function clearUserDataFromStore()
    {
        Session::forget($this->sessionKey);

        if (!is_null($this->getRememberCookie())) {
            $rememberCookie = $this->getRememberCookieName();
            Cookie::forget($rememberCookie);
        }
    }
}