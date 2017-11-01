<?php

namespace Igniter\Flame\Foundation\Http\Middleware;

use Admin\Models\Locations_model;
use Admin\Models\Staff_groups_model;
use Admin\Models\Staffs_model;
use Admin\Models\Users_model;
use Carbon\Carbon;
use Closure;
use File;
use Igniter\Flame\Foundation\Application;
use Illuminate\Encryption\Encrypter;
use System\Models\Languages_model;

class CompleteApplicationSetup
{
    /**
     * @var \SetupRepository
     */
    protected $repository;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->envPath = $app->environmentPath();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param  string|null $guard
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $this->makeRepository();

        // We will only run this if a value setup_config file exists
        if ($this->verifyRepository()) {

            // Replace and write example.env contents to .env
            $this->writeEnvWith();

            // Install the database tables
            \Artisan::call('igniter:up');

            // Create the admin user if no admin exists.
            $this->createSuperUser();

            // Create the default location if not already created
            $this->createDefaultLocation();

            // Save the site configuration to the settings table
            $this->addSystemSettings();

            // Delete the setup repository file since its no longer needed
            $this->repository->destroy();
        }

        return $next($request);
    }

    protected function makeRepository()
    {
        if (!File::exists(base_path('setup/setup_config')))
            return FALSE;

        $repositoryPath = base_path('setup/classes/SetupRepository.php');
        if (!class_exists('SetupRepository') AND File::exists($repositoryPath))
            File::requireOnce($repositoryPath);

        $this->repository = new \SetupRepository(base_path('setup/setup_config'));
    }

    protected function verifyRepository()
    {
        if (!$this->repository OR !$this->repository->exists())
            return FALSE;

        // Make sure all system requirements were met
        if ($this->repository->get('requirement') != 'success')
            return FALSE;

        // Make sure core library was installed
        if ($this->repository->get('install') != 'complete')
            return FALSE;

        // Make sure database configuration values was provided
        if (!is_array($this->repository->get('database')))
            return FALSE;

        // Make sure application settings exists
        if (!is_array($this->repository->get('settings')))
            return FALSE;

        return TRUE;
    }

    protected function writeEnvWith()
    {
        if ($this->app['config']['app.key'])
            return false;

        $contents = File::get($this->envPath.'/.env');

        $search = $this->envReplacementPatterns();
        foreach ($search as $pattern => $replace) {
            $contents = preg_replace($pattern, $replace, $contents);
            putenv($replace);
        }

        File::put($this->envPath.'/.env', $contents);
    }

    protected function envReplacementPatterns()
    {
        $config = $this->repository->get('settings');

        return [
            '/^APP_NAME=TastyIgniter/m' => 'APP_NAME='.$config['site_name'],
            // Create the encryption key used for authentication and encryption
            '/^APP_KEY=/m'              => 'APP_KEY='.$this->generateRandomKey(),
        ];
    }

    /**
     * Generate a random key for the application.
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:'.base64_encode(
                Encrypter::generateKey($this->app['config']['app.cipher'])
            );
    }

    protected function createSuperUser()
    {
        // Abort: a super admin user already exists
        if (Users_model::where('super_user', 1)->count())
            return TRUE;

        $config = $this->repository->get('settings');

        $staffEmail = strtolower($config['site_email']);
        $staff = Staffs_model::firstOrNew([
            'staff_email' => $staffEmail,
        ]);

        $staff->staff_name = $config['staff_name'];
        $staff->staff_group_id = Staff_groups_model::first()->staff_group_id;
        $staff->staff_location_id = 0; //Locations_model::first()->location_id;
        $staff->language_id = Languages_model::first()->language_id;
        $staff->timezone = FALSE;
        $staff->staff_status = TRUE;
        $staff->save();

        $user = Users_model::firstOrNew([
            'username' => $config['username'],
        ]);

        $user->staff_id = $staff->staff_id;
        $user->password = $config['password'];
        $user->super_user = TRUE;
        $user->is_activated = TRUE;
        $user->date_activated = Carbon::now();

        return $user->save();
    }

    protected function addSystemSettings()
    {
        if (in_array(setting('ti_setup'), ['installed', 'updated']))
            return TRUE;

        $config = $this->repository->get('settings');

        $version = app()->version();
        $settings = [
            'site_url'            => root_url(),
            'site_name'           => $config['site_name'],
            'site_email'          => $config['site_email'],
            'site_key'            => isset($config['site_key']) ? $config['site_key'] : null,
            'site_location_mode'  => $config['use_multi'] ? 'multiple' : 'single',
            'default_location_id' => Locations_model::first()->location_id,
            'ti_setup'            => 'installed',
            'ti_version'          => $version,
            'sys_hash'            => md5("TastyIgniter!core!".$version),
        ];

        foreach ($settings as $key => $value) {
            $item = $key;
            $value = is_array($value) ? serialize($value) : $value;

            if (in_array($key, ['ti_setup', 'ti_version', 'site_key', 'default_location_id'])) {
                params()->set($item, $value);
            }
            else {
                setting()->set($item, $value);
            }
        }
    }

    protected function createDefaultLocation()
    {
        // Abort: a location already exists
        if (Locations_model::count())
            return TRUE;

        $config = $this->repository->get('settings');

        Locations_model::insert([
            'location_name'  => $config['site_name'],
            'location_email' => $config['site_email'],
        ]);
    }
}