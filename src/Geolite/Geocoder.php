<?php

namespace Igniter\Flame\Geolite;

use GuzzleHttp\Client;
use Igniter\Flame\Geolite\Contracts\AbstractProvider;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class Geocoder extends Manager implements Contracts\GeocoderInterface
{
    /**
     * @var int
     */
    protected $limit;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @param mixed $limit
     * @return Geocoder
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * @param string $locale
     * @return Geocoder
     */
    public function locale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    public function geocode($address)
    {
        $query = GeoQuery::create($address);

        if ($this->limit)
            $query = $query->withLimit($this->limit);

        if ($this->locale)
            $query = $query->withLocale($this->locale);

        return $this->geocodeQuery($query);
    }

    public function reverse(float $latitude, float $longitude)
    {
        $query = GeoQuery::fromCoordinates($latitude, $longitude);

        if ($this->limit)
            $query = $query->withLimit($this->limit);

        if ($this->locale)
            $query = $query->withLocale($this->locale);

        return $this->reverseQuery($query);
    }

    public function geocodeQuery(GeoQueryInterface $query)
    {
        $limit = $query->getLimit();
        if (!$limit AND $this->limit)
            $query = $query->withLimit($this->limit);

        $locale = $query->getLocale();
        if (!$locale AND $this->locale)
            $query = $query->withLocale($this->locale);

        return $this->driver()->geocodeQuery($query);
    }

    public function reverseQuery(GeoQueryInterface $query)
    {
        $limit = $query->getLimit();
        if (!$limit AND $this->limit)
            $query = $query->withLimit($this->limit);

        $locale = $query->getLocale();
        if (!$locale AND $this->locale)
            $query = $query->withLocale($this->locale);

        return $this->driver()->reverseQuery($query);
    }

    /**
     * @param $name
     * @return \Igniter\Flame\Geolite\Contracts\AbstractProvider
     */
    public function using($name)
    {
        return $this->driver($name);
    }

    /**
     * Get a driver instance.
     *
     * @param  string $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        $config = $this->app['config']['geocoder.providers.'.$driver];

        return $this->makeProvider($driver, $config);
    }

    /**
     * @param $name
     * @param array $config
     * @return \Igniter\Flame\Geolite\Contracts\AbstractProvider
     */
    public function makeProvider($name, array $config = []): AbstractProvider
    {
        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        return $this->drivers[$name] = $this->createProvider($name, $config);
    }

    /**
     * Get the default driver name.
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['geocoder.default'] ?? 'nominatim';
    }

    protected function createProvider($name, array $config = [])
    {
        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($name);
        }

        $method = 'create'.studly_case($name).'Provider';
        if (method_exists($this, $method)) {
            return $this->$method($config);
        }

        throw new InvalidArgumentException("Provider [$name] not supported.");
    }

    protected function createChainProvider($config)
    {
        return new Provider\ChainProvider($this, $config);
    }

    protected function createNominatimProvider($config)
    {
        return new Provider\NominatimProvider(new Client, $config);
    }

    protected function createGoogleProvider($config)
    {
        return new Provider\GoogleProvider(new Client, $config);
    }
}