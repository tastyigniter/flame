<?php

namespace Igniter\Flame\Translation;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Session;

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
            $this->setLocale($locale);
        }
    }

    public function loadLocaleFromBrowser()
    {
        if (!$this->detectBrowserLocale())
            return false;

        $locale = $this->getBrowserLocale();
        if (!$locale || !$this->isValid($locale))
            return false;

        $remember = $this->getLocale() != $locale;

        $this->setLocale($locale, $remember);

        return true;
    }

    public function loadLocaleFromRequest()
    {
        $locale = $this->getRequestLocale();
        if (!$locale || !$this->isValid($locale))
            return false;

        $remember = $this->getLocale() != $locale;

        $this->setLocale($locale, $remember);

        return true;
    }

    public function loadLocaleFromSession()
    {
        $locale = $this->getSessionLocale();
        if (!$locale || !$this->isValid($locale))
            return false;

        $remember = $this->getLocale() != $locale;

        $this->setLocale($locale, $remember);

        return true;
    }

    public function setLocale($locale, $remember = true)
    {
        if (!$this->isValid($locale)) {
            return false;
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);

        if ($remember) {
            $this->setSessionLocale($locale);
        }
    }

    public function getLocale()
    {
        $sessionLocale = $this->getSessionLocale();
        if ($sessionLocale && $this->isValid($sessionLocale)) {
            return $sessionLocale;
        }

        return $this->getConfig('locale');
    }

    public function getDefaultLocale()
    {
        return $this->getConfig('locale');
    }

    public function supportedLocales()
    {
        return $this->getConfig('supportedLocales', []);
    }

    public function detectBrowserLocale()
    {
        return (bool)$this->getConfig('detectBrowserLocale', false);
    }

    public function isValid($locale)
    {
        return in_array($locale, $this->supportedLocales());
    }

    public function setSessionLocale($locale)
    {
        return Session::put($this->sessionKey, $locale);
    }

    public function getSessionLocale()
    {
        return Session::get($this->sessionKey);
    }

    public function getRequestLocale()
    {
        return RequestFacade::segment(1);
    }

    public function getBrowserLocale()
    {
        return substr($this->request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
    }

    protected function getConfig(string $string)
    {
        return $this->config['localization.'.$string];
    }
}
