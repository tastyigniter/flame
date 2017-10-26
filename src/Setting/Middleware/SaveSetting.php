<?php namespace Igniter\Flame\Setting\Middleware;

use Closure;
use Igniter\Flame\Foundation\Application;
use Igniter\Flame\Setting\SettingManager;
use Igniter\Flame\Setting\SettingStore;

class SaveSetting
{
    /**
     * The setting stores.
     *
     * @var \Igniter\Flame\Setting\SettingStore
     */
    protected $stores;

    /**
     * Indicates if the setting was handled for the current request.
     *
     * @var bool
     */
    protected $settingHandled = FALSE;

    /**
     * Create a new setting middleware.
     *
     * @param  \Igniter\Flame\Setting\SettingStore $stores
     */
    public function __construct(Application $app)
    {
        $this->manager = $app->make(SettingManager::class);
    }

    public function handle($request, Closure $next)
    {
        $this->settingHandled = TRUE;

        return $next($request);
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Symfony\Component\HttpFoundation\Response $response
     *
     * @return void
     */
    public function terminate($request, $response)
    {
        if ($this->settingHandled) {
            $this->manager->driver('config')->save();
            $this->manager->driver('prefs')->save();
        }
    }
}