<?php

namespace Igniter\Flame\Translation;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;

class Localization
{
    protected $request;

    protected $config;

    protected $sessionKey = 'igniter.translation.locale';

    public function __construct(Request $request, Repository $config)
    {
        $this->request = $request;
        $this->config = $config;
    }

    public function loadLocale()
    {
        $locale = $this->getLocale();

        if ($this->config['app.locale'] != $locale) {
            app()->setLocale($locale);
            Carbon::setLocale($locale);
        }
    }

    public function getLocale()
    {
        // Check request for locale
        $routeLocale = $this->getRouteLocale();
        if ($routeLocale AND $this->isValid($routeLocale)) {
            return $routeLocale;
        }

        // Get locale from session
        $sessionLocale = $this->getSessionLocale();
        if ($sessionLocale AND $this->isValid($sessionLocale)) {
            return $sessionLocale;
        }

        // Get locale from user browser
        if ($this->detectBrowserLocale()) {
            $browserLocale = $this->getBrowserLocale();
            if ($browserLocale AND $this->isValid($browserLocale)) {
                return $browserLocale;
            }
        }

        return $this->getConfig('locale');
    }

    public function supportedLocales()
    {
        return $this->getConfig('supportedLocales', []);
    }

    public function detectBrowserLocale()
    {
        return (bool)$this->getConfig('detectBrowserLocale', FALSE);
    }

    public function isValid($locale)
    {
        return in_array($locale, $this->supportedLocales());
    }

    public function setSessionLocale($locale)
    {
        return $this->request->getSession()->put([$this->sessionKey => $locale]);
    }

    public function getSessionLocale()
    {
        return $this->request->getSession()->get($this->sessionKey);
    }

    protected function getRouteLocale()
    {
        $paths = explode('/', $this->request->path());

        return $paths[0] ?? null;
    }

    protected function getBrowserLocale()
    {
        return substr($this->request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
    }

    protected function getConfig(string $string)
    {
        return $this->config['localization.'.$string];
    }
}