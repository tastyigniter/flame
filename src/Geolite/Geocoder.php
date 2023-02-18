<?php

namespace Igniter\Flame\Geolite;

use GuzzleHttp\Client;
use Igniter\Flame\Geolite\Contracts\AbstractProvider;
use Igniter\Flame\Geolite\Contracts\DistanceInterface;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Illuminate\Support\Manager;
use InvalidArgumentException;

class Geocoder extends Manager implements Contracts\GeocoderInterface
{
    public function geocode($address)
    {
        return $this->geocodeQuery(GeoQuery::create($address));
    }

    public function reverse(float $latitude, float $longitude)
    {
        return $this->reverseQuery(GeoQuery::fromCoordinates($latitude, $longitude));
    }

    public function distance(DistanceInterface $distance)
    {
        return $this->driver()->distance($distance);
    }

    public function geocodeQuery(GeoQueryInterface $query)
    {
        return $this->driver()->geocodeQuery($query);
    }

    public function reverseQuery(GeoQueryInterface $query)
    {
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
     * @param string $driver
     * @return mixed
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        return $this->makeProvider($driver);
    }

    /**
     * @param $name
     * @return \Igniter\Flame\Geolite\Contracts\AbstractProvider
     */
    public function makeProvider($name): AbstractProvider
    {
        if (isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        return $this->drivers[$name] = $this->createProvider($name);
    }

    /**
     * Get the default driver name.
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->container['config']['geocoder.default'] ?? 'nominatim';
    }

    protected function createProvider($name)
    {
        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($name);
        }

        $method = 'create'.studly_case($name).'Provider';
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Provider [$name] not supported.");
    }

    protected function createChainProvider()
    {
        $providers = $this->container['config']['geocoder.providers'];

        return new Provider\ChainProvider($this, $providers);
    }

    protected function createNominatimProvider()
    {
        $config = $this->container['config']['geocoder.providers.nominatim'];

        return new Provider\NominatimProvider(new Client, $config);
    }

    protected function createGoogleProvider()
    {
        $config = $this->container['config']['geocoder.providers.google'];

        return new Provider\GoogleProvider(new Client, $config);
    }
}
