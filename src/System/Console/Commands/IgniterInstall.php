<?php

namespace Igniter\System\Console\Commands;

use Igniter\Admin\Facades\AdminAuth;
use Igniter\Admin\Models\Location;
use Igniter\Admin\Models\User;
use Igniter\Admin\Models\UserGroup;
use Igniter\Admin\Models\UserRole;
use Igniter\Flame\Igniter;
use Igniter\Flame\Support\ConfigRewrite;
use Igniter\Main\Models\CustomerGroup;
use Igniter\System\Classes\UpdateManager;
use Igniter\System\Database\Seeds\DatabaseSeeder;
use Igniter\System\Helpers\SystemHelper;
use Igniter\System\Models\Language;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;

/**
 * Console command to install TastyIgniter.
 * This sets up TastyIgniter for the first time. It will prompt the user for several
 * configuration items, including application URL and database config, and then
 * perform a database migration.
 */
class IgniterInstall extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'igniter:install';

    /**
     * The console command description.
     */
    protected $description = 'Set up TastyIgniter for the first time.';

    /**
     * @var \Igniter\Flame\Support\ConfigRewrite
     */
    protected $configRewrite;

    protected $dbConfig = [];

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->configRewrite = new ConfigRewrite;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->alert('INSTALLATION');

        $this->callSilent('igniter:package-discover');
        $this->callSilent('vendor:publish', ['--tag' => 'igniter-assets', '--force' => true]);

        if (
            Igniter::hasDatabase() &&
            !$this->confirm('Application appears to be installed already. Continue anyway?', false)
        ) {
            return;
        }

        $this->line('Enter a new value, or press ENTER for the default');

        $this->setSeederProperties();

        $this->rewriteEnvFile();

        $this->migrateDatabase();

        $this->createSuperUser();

        $this->addSystemValues();

        $this->alert('INSTALLATION COMPLETE');
    }

    /**
     * Get the console command options.
     */
    protected function getOptions()
    {
        return [
            ['composer', null, InputOption::VALUE_REQUIRED, 'Absolute path to the Composer binary which should be used to install packages.', 'global'],
        ];
    }

    protected function rewriteEnvFile()
    {
        if (!file_exists(base_path().'/.env')) {
            $this->moveExampleFile('env', null, 'backup');
            $this->copyExampleFile('env', 'example', null);
        }

        if (strlen(!$this->laravel['config']['app.key']))
            SystemHelper::replaceInEnv('APP_KEY=', 'APP_KEY='.$this->generateEncryptionKey());

        SystemHelper::replaceInEnv('APP_NAME=', 'APP_NAME="'.DatabaseSeeder::$siteName.'"');
        SystemHelper::replaceInEnv('APP_URL=', 'APP_URL='.DatabaseSeeder::$siteUrl);

        $name = Config::get('database.default');
        foreach ($this->dbConfig as $key => $value) {
            Config::set("database.connections.$name.".strtolower($key), $value);

            if ($key === 'password') $value = '"'.$value.'"';
            SystemHelper::replaceInEnv('DB_'.strtoupper($key).'=', 'DB_'.strtoupper($key).'='.$value);
        }

        if (!file_exists(base_path().'/.htaccess')) {
            $this->moveExampleFile('htaccess', null, 'backup');
            $this->moveExampleFile('htaccess', 'example', null);
        }
    }

    protected function migrateDatabase()
    {
        $this->line('Migrating application and extensions...');

        DB::purge();

        $manager = resolve(UpdateManager::class)->setLogsOutput($this->output);

        $manager->update();

        $this->line('Done. Migrating application and extensions...');
    }

    protected function setSeederProperties()
    {
        $name = Config::get('database.default');
        $this->dbConfig['host'] = $this->ask('MySQL Host', Config::get("database.connections.$name.host"));
        $this->dbConfig['port'] = $this->ask('MySQL Port', Config::get("database.connections.$name.port") ?: false) ?: '';
        $this->dbConfig['database'] = $this->ask('MySQL Database', Config::get("database.connections.$name.database"));
        $this->dbConfig['username'] = $this->ask('MySQL Username', Config::get("database.connections.$name.username"));
        $this->dbConfig['password'] = $this->ask('MySQL Password', Config::get("database.connections.$name.password") ?: false) ?: '';
        $this->dbConfig['prefix'] = $this->ask('MySQL Table Prefix', Config::get("database.connections.$name.prefix") ?: false) ?: '';

        DatabaseSeeder::$siteName = $this->ask('Site Name', DatabaseSeeder::$siteName);
        DatabaseSeeder::$siteUrl = $this->ask('Site URL', Config::get('app.url'));

        DatabaseSeeder::$seedDemo = $this->confirm('Install demo data?', DatabaseSeeder::$seedDemo);
    }

    protected function createSuperUser()
    {
        DatabaseSeeder::$staffName = $this->ask('Admin Name', DatabaseSeeder::$staffName);
        DatabaseSeeder::$siteEmail = $this->output->ask('Admin Email', DatabaseSeeder::$siteEmail, function ($answer) {
            if (User::whereEmail($answer)->first()) {
                throw new \RuntimeException('An administrator with that email already exists, please choose a different email.');
            }

            return $answer;
        });
        DatabaseSeeder::$username = $this->output->ask('Admin Username', 'admin', function ($answer) {
            if (User::whereUsername($answer)->first()) {
                throw new \RuntimeException('An administrator with that username already exists, please choose a different username.');
            }

            return $answer;
        });
        DatabaseSeeder::$password = $this->output->ask('Admin Password', '123456', function ($answer) {
            if (!is_string($answer) || strlen($answer) < 6) {
                throw new \RuntimeException('Please specify the administrator password, at least 6 characters');
            }

            return $answer;
        });

        $user = AdminAuth::register([
            'email' => DatabaseSeeder::$siteEmail,
            'name' => DatabaseSeeder::$staffName,
            'language_id' => Language::first()->language_id,
            'user_role_id' => UserRole::first()->user_role_id,
            'status' => true,
            'username' => DatabaseSeeder::$username,
            'password' => DatabaseSeeder::$password,
            'super_user' => true,
            'groups' => [UserGroup::first()->user_group_id],
            'locations' => [Location::first()->location_id],
        ], true);

        $this->line('Admin user '.$user->username.' created!');
    }

    protected function addSystemValues()
    {
        params()->flushCache();

        params()->set([
            'ti_setup' => 'installed',
            'default_location_id' => Location::first()->location_id,
        ]);

        params()->save();

        setting()->flushCache();
        setting()->set('site_name', DatabaseSeeder::$siteName);
        setting()->set('site_email', DatabaseSeeder::$siteEmail);
        setting()->set('sender_name', DatabaseSeeder::$siteName);
        setting()->set('sender_email', DatabaseSeeder::$siteEmail);
        setting()->set('customer_group_id', CustomerGroup::first()->customer_group_id);
        setting()->save();

        // These parameters are no longer in use
        params()->forget('main_address');

        resolve(UpdateManager::class)->setCoreVersion();
    }

    protected function generateEncryptionKey()
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    protected function moveExampleFile($name, $old, $new)
    {
        // /$old.$name => /$new.$name
        if (file_exists(base_path().'/'.$old.'.'.$name)) {
            rename(base_path().'/'.$old.'.'.$name, base_path().'/'.$new.'.'.$name);
        }
    }

    protected function copyExampleFile($name, $old, $new)
    {
        // /$old.$name => /$new.$name
        if (file_exists(base_path().'/'.$old.'.'.$name)) {
            if (file_exists(base_path().'/'.$new.'.'.$name))
                unlink(base_path().'/'.$new.'.'.$name);

            copy(base_path().'/'.$old.'.'.$name, base_path().'/'.$new.'.'.$name);
        }
    }
}
