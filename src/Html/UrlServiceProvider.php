<?php

namespace Igniter\Flame\Html;

use Igniter\Flame\Support\Str;
use Illuminate\Support\ServiceProvider;

class UrlServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        $this->forceUrlGeneratorPolicy();
    }

    /**
     * Controls how URL links are generated throughout the application.
     *
     * detect   - detect hostname and use the current schema
     * secure   - detect hostname and force HTTPS schema
     * insecure - detect hostname and force HTTP schema
     * force    - force hostname and schema using app.url config value
     */
    public function forceUrlGeneratorPolicy()
    {
        $policy = $this->app['config']->get('system.urlPolicy', 'detect');

        switch (strtolower($policy)) {
            case 'force':
                $appUrl = $this->app['config']->get('app.url');
                $schema = Str::startsWith($appUrl, 'http://') ? 'http' : 'https';
                $this->app['url']->forceRootUrl($appUrl);
                $this->app['url']->forceScheme($schema);
                break;

            case 'insecure':
                $this->app['url']->forceScheme('http');
                break;

            case 'secure':
                $this->app['url']->forceScheme('https');
                break;
        }
    }
}
