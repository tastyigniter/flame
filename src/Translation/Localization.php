<?php

namespace Igniter\Flame\Translation;

class Localization
{
    protected $locale;

    protected $supportedLocales;

    public function __construct($locale, array $supportedLocales = [])
    {
        $this->locale = $locale;
        $this->supportedLocales = $supportedLocales;
    }

    public function supportedLocales()
    {
        return $this->supportedLocales;
    }

    public function isValid($locale)
    {
        return in_array($locale, $this->supportedLocales());
    }
}