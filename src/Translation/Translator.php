<?php namespace Igniter\Flame\Translation;

use Illuminate\Support\Str;
use \Illuminate\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    public function get($key, array $replace = [], $locale = null, $fallback = TRUE)
    {
        if (Str::startsWith($key, 'lang:'))
            $key = substr($key, 5);

        return parent::get($key, $replace, $locale, $fallback);
    }
}