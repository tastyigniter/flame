<?php namespace Igniter\Flame\Currency\Converters;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

abstract class AbstractConverter
{
    /**
     * Returns information about the converter
     * Must return array:
     *
     * [
     *      'name'        => 'Open Exchange Rates',
     *      'description' => 'Conversion services provided by Open Exchange Rates.'
     * ]
     *
     * @return array
     */
    abstract public function converterDetails();

    /**
     * Returns list of exchange rates for currencies specified.
     *
     * @param $base
     * @param array $currencies
     * @return array
     */
    abstract public function getExchangeRates($base, array $currencies);

    //
    //
    //

    public function getName()
    {
        return array_get($this->converterDetails(), 'name', 'Undefined name');
    }

    public function getDescription()
    {
        return array_get($this->converterDetails(), 'description', 'Undefined description');
    }

    /**
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        return new Client();
    }

    //
    //
    //

    /**
     * Forget the repository cache.
     *
     * @return $this
     */
    public function forgetCache()
    {
        if ($this->getCacheLifetime()) {
            // Flush cache keys, then forget actual cache
            $this->getCacheDriver()->forget($this->getCacheKey());
        }

        return $this;
    }

    public function getCacheKey()
    {
        return sprintf('igniter.currency.rates.%s', str_slug($this->getName()));
    }

    /**
     * Get the cache lifetime.
     *
     * @return float|int
     */
    public function getCacheLifetime()
    {
        return config('currency.ratesCacheDuration', 0);
    }

    protected function cacheCallback($cacheKey, \Closure $closure)
    {
        if (!$lifetime = $this->getCacheLifetime())
            return $closure();

        $lifetime = $this->getCacheLifetime();
        $cacheKey = $this->getCacheKey().'@'.md5($cacheKey);

        return $this->getCacheDriver()->remember($cacheKey, $lifetime, $closure);
    }

    protected function getCacheDriver(): \Illuminate\Contracts\Cache\Repository
    {
        return Cache::driver(config('currency.cache_driver'));
    }
}