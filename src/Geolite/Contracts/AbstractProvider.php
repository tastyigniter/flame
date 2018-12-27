<?php namespace Igniter\Flame\Geolite\Contracts;

use Cache;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;

abstract class AbstractProvider
{
    /**
     * The cache lifetime.
     *
     * @var \GuzzleHttp\Client
     */
    protected $httpClient;

    /**
     * The cache lifetime.
     *
     * @var float|int
     */
    protected $cacheLifetime;

    protected $logs = [];

    /**
     * Returns the provider name.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Handle the geocoder request.
     *
     * @param \Igniter\Flame\Geolite\Contracts\GeoQueryInterface $query
     * @return \Illuminate\Support\Collection
     */
    abstract public function geocodeQuery(GeoQueryInterface $query): Collection;

    /**
     * Handle the reverse geocoding request.
     *
     * @param \Igniter\Flame\Geolite\Contracts\GeoQueryInterface $query
     * @return \Illuminate\Support\Collection
     */
    abstract public function reverseQuery(GeoQueryInterface $query): Collection;

    /**
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        return $this->httpClient ?? new Client();
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
        return sprintf('geocode.%s', str_slug($this->getName()));
    }

    /**
     * Set the repository cache lifetime.
     *
     * @param float|int $cacheLifetime
     *
     * @return $this
     */
    public function setCacheLifetime($cacheLifetime)
    {
        $this->cacheLifetime = $cacheLifetime;

        return $this;
    }

    /**
     * Get the repository cache lifetime.
     *
     * @return float|int
     */
    public function getCacheLifetime()
    {
        $lifetime = config('geocoder.cache.duration');

        return !is_null($this->cacheLifetime) ? $this->cacheLifetime : $lifetime;
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
        return Cache::store(config('geocoder.cache.store'));
    }

    //
    //
    //

    public function log($message)
    {
        $this->logs[] = $message;

        return $this;
    }

    /**
     * @return \Igniter\Flame\Geolite\Contracts\AbstractProvider $this
     */
    public function resetLogs()
    {
        $this->logs = [];

        return $this;
    }

    public function getLogs()
    {
        return $this->logs;
    }
}