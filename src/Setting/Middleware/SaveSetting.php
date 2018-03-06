<?php namespace Igniter\Flame\Setting\Middleware;

use Closure;
use Igniter\Flame\Setting\SettingManager;

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
     * @param \Igniter\Flame\Setting\SettingManager $manager
     */
    public function __construct(SettingManager $manager)
    {
        $this->manager = $manager;
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