<?php

namespace Igniter\Flame\Auth;

use App;
use Carbon\Carbon;
use Cookie;
use Exception;
use Igniter\Flame\Auth\Models\User as UserModel;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Traits\Singleton;
use Illuminate\Support\Str;
use System\Traits\SessionMaker;

/**
 * Auth Manager Class
 * Adapted from Ion Auth.
 * @link https://github.com/benedmunds/CodeIgniter-Ion-Auth
 * @package        Igniter\Flame\Auth\Manager.php
 */
class Manager
{
    use Singleton;
    use SessionMaker;

    protected $hasher;

    /**
     * @var Model The currently authenticated user.
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
     * Gets the hasher.
     * @return \BcryptHash
     */
    public function getHasher()
    {
        return $this->hasher ?: App::make('hash');
    }

    /**
     * Sets the hasher.
     *
     * @param  string $hasher
     *
     * @return $this
     */
    public function setHasher($hasher)
    {
        $this->hasher = $hasher;

        return $this;
    }

    /**
     * Registers a user by giving the required credentials
     * and an optional flag for whether to activate the user.
     *
     * @param array $credentials
     * @param bool $activate
     *
     * @return Models\User
     */
    public function register(array $credentials, $activate = FALSE)
    {
        $model = $this->createModel();
        $model->fill($credentials);
        $model->save();

        if ($activate) {
            $model->attemptActivation($model->getActivationCode());
        }

        // Prevents revalidation of the password field
        // on subsequent saves to this model object
        $model->password = null;

        return $this->user = $model;
    }

    /**
     * Determine if the current user is authenticated.
     */
    public function check()
    {
        $user = $this->user;
        if (!is_null($user) AND ($this->requireApproval AND !$user->is_activated))
            return $user;

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
     * @return Model
     */
    public function getUser()
    {
        return $this->user;
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
     * @param $userModel
     * @param bool $remember
     */
    public function login($userModel, $remember = FALSE)
    {
        // Approval is required, user not approved
        if ($this->requireApproval AND !$userModel->is_activated) {
            throw new ApplicationException(sprintf(
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
    }

    /**
     * Log the given user ID into the application.
     *
     * @param $id
     * @param bool $remember
     *
     * @return mixed
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

        $this->clearUserDataFromStorage();

        $this->loggedOut = true;
    }

    /**
     * @todo: Check whether authenticated user belongs to a group
     *
     * @param mixed group(s) to check
     * @param bool user id
     * @param bool check if all groups is present, or any of the groups
     *
     * @return bool
     **/
    public function inGroup($check_group, $id = FALSE, $check_all = FALSE)
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

        $hasher = $this->getHasher();

        // Backward compatibility to turn SHA1 passwords to BCrypt
        if ($userModel->hasShaPassword($plain)) {
            $userModel->updateHashPassword($hasher->make($plain));
        }

        return $hasher->check($plain, $userModel->getAuthPassword());
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
            throw new Exception('Required property [model] missing in %s', get_called_class());

        $modelClass = $this->model;
        if (!class_exists($modelClass))
            return null;

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

    //
    // Session & Cookie
    //

    /**
     * @return mixed
     */
    public function getSessionUserId()
    {
        $sessionArray = $this->getSessionArray();

        return isset($sessionArray['id']) ? $sessionArray['id'] : null;
    }

    public function getSessionArray()
    {
        $sessionData = $this->getSession($this->getSessionName());

        if (is_null($sessionData))
            return [];

        return unserialize(base64_decode($sessionData));
    }

    public function getSessionName()
    {
        return 'auth';
    }

    protected function makeSessionKey()
    {
        return strtolower(basename(str_replace('\\', '/', get_class($this)))).'_info';
    }

    protected function updateSession(UserModel $userModel)
    {
        $id = $userModel->getAuthIdentifier();
        $identityName = $userModel->getAuthIdentifierName();

        $sessionData = base64_encode(serialize([
            'id'              => $id,
            $identityName     => $id,
            $this->identifier => $userModel->{$this->identifier},
            'last_check'      => Carbon::now(),
        ]));

        $this->putSession($this->getSessionName(), $sessionData);
    }

    /**
     * @param $userModel
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
        return 'remember_'.$this->makeSessionKey();
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
    protected function clearUserDataFromStorage()
    {
        $this->resetSession();

        if (!is_null($this->getRememberCookie())) {
            $rememberCookie = $this->getRememberCookieName();
            Cookie::forget($rememberCookie);
        }
    }

    //
    // Reset Password
    //

    public function createResetCode()
    {
        $model = $this->createModel();
        $email = $model->getReminderEmail();
        $value = sha1($email.spl_object_hash($this).microtime(TRUE));

        return hash_hmac('sha1', $value, $this->getHasher()->getHashKey());
    }

    /**
     * Reset password feature
     *
     * @param string $identity The user email
     *
     * @return bool|array
     */
    public function resetPassword($identity)
    {
        $model = $this->createModel();

        // Reset the user password and send email link
        if ($model->resetPassword($identity)) {

            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    /**
     * Validate a password reset for the given credentials.
     *
     * @param $credentials
     *
     * @return bool|UserModel
     */
    public function validateResetPassword($credentials)
    {
        $userModel = $this->getByCredentials($credentials);

        if (is_null($userModel))
            return FALSE;

        $token = $credentials['reset_code'];

        $expiration = $this->resetExpiration;
        if ($expiration > 0) {
            if ((time() - strtotime($userModel->reset_time)) > $expiration) {
                // Reset password request has expired, so clear code.
                $this->createModel()->clearResetPasswordCode($token);

                return FALSE;
            }
        }

        return $userModel;
    }

    /**
     * Complete a password reset request
     *
     * @param $credentials
     *
     * @return bool
     */
    public function completeResetPassword($credentials)
    {
        $userModel = $this->validateResetPassword($credentials);

        if (!$userModel)
            return FALSE;

        if ($this->createModel()->completeResetPassword($userModel->getAuthIdentifier(), $credentials))
            return TRUE;

        return FALSE;
    }

    //
    // Impersonation
    //

    /**
     * Impersonates the given user and sets properties
     * in the session but not the cookie.
     */
    public function impersonate($userModel)
    {
        $sessionName = $this->getSessionName();
        $oldSession = $this->getSession($sessionName);

        $this->login($userModel, FALSE);

        $this->putSession($sessionName.'_impersonate', $oldSession);
    }

    public function stopImpersonate()
    {
        $sessionName = $this->getSessionName();
        $oldSession = $this->getSession($sessionName.'_impersonate');

        $this->putSession($sessionName, $oldSession);
    }

    public function isImpersonator()
    {
        return $this->hasSession($this->getSessionName().'_impersonate');
    }

    public function getImpersonator()
    {
        $sessionName = $this->getSessionName();
        $impersonateArray = $this->getSession($sessionName.'_impersonate');

        // Check supplied session/cookie is an array (user id, persist code)
        if (!is_array($impersonateArray) OR count($impersonateArray) !== 2)
            return FALSE;

        $id = reset($impersonateArray);

        return $this->createModel()->find($id);
    }
}