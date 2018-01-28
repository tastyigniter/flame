<?php

namespace Igniter\Flame\Foundation\Http\Middleware;

use Closure;
use Exception;
use File;
use Igniter\Flame\Foundation\Application;
use Redirect;

class CheckForApplicationSetup
{
    public function __construct(Application $app)
    {
        $this->app = $app;
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
        // Lets redirect to setup if this is a fresh install
        if ($this->isFreshlyInstalled())
            return Redirect::to('setup.php');

        return $next($request);
    }

    protected function isFreshlyInstalled()
    {
        if ($this->app->runningInConsole())
            return false;

        $envPath = $this->app->environmentFilePath();
        $setupConfig = base_path('setup/setup_config');
        if (File::isFile($envPath) OR File::isFile($setupConfig))
            return false;

        $appKey = substr($this->app->config['app.key'], strlen('base64:'));
        if (base64_encode(base64_decode($appKey)) !== $appKey)
            return true;

        try {
            params('ti_setup');
        } catch (Exception $ex) {
            return false;
        }

        return true;
    }
}