<?php namespace Igniter\Flame\Foundation\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class RegisterTastyIgniter
{
    /**
     * Register specifics for TastyIgniter.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        // Workaround for CLI and URL based in subdirectory
        if ($app->runningInConsole()) {
            $app['url']->forceRootUrl($app['config']->get('app.url'));
        }

        // Register singletons
        $app->singleton('string', function () {
            return new \Igniter\Flame\Support\Str;
        });

        // Change extensions and themes paths based on config
        if ($extensionsPath = $app['config']->get('system.extensionsPath'))
            $app->useExtensionsPath($extensionsPath);

        if ($themesPath = $app['config']->get('system.themesPath'))
            $app->useThemesPath($themesPath);

        if ($assetsPath = $app['config']->get('system.assetsPath'))
            $app->useAssetsPath($assetsPath);

        // Set execution context
        $requestPath = $this->normalizeUrl($app['request']->path());
        $adminUri = $this->normalizeUrl($app['config']->get('system.adminUri', 'admin'));
        $app->setAppContext(starts_with($requestPath, $adminUri) ? 'admin' : 'main');
    }

    /**
     * Adds leading slash from a URL.
     *
     * @param string $url URL to normalize.
     *
     * @return string Returns normalized URL.
     */
    protected function normalizeUrl($url)
    {
        if (substr($url, 0, 1) != '/')
            $url = '/'.$url;

        if (!strlen($url))
            $url = '/';

        return $url;
    }
}