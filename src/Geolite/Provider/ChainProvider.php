<?php

namespace Igniter\Flame\Geolite\Provider;

use Igniter\Flame\Geolite\Contracts;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Illuminate\Support\Collection;
use Throwable;

class ChainProvider extends Contracts\AbstractProvider
{
    /**
     * @var \Igniter\Flame\Geolite\Contracts\GeocoderInterface
     */
    protected $geocoder;

    /**
     * @var array
     */
    protected $providers = [];

    /**
     * @param $geocoder
     * @param array $config
     */
    public function __construct($geocoder, array $config)
    {
        $this->geocoder = $geocoder;
        $this->providers = $config;
    }

    public function getName(): string
    {
        return 'Chain';
    }

    public function geocodeQuery(GeoQueryInterface $query): Collection
    {
        foreach ($this->providers as $name => $config) {
            $provider = $this->geocoder->makeProvider($name, $config);

            try {
                $result = $provider->geocodeQuery($query);
                if ($result->isNotEmpty())
                    return $result;
            }
            catch (Throwable $ex) {
                $this->log(sprintf(
                    'Provider "%s" could not geocode address, "%s".',
                    $provider->getName(), $ex->getMessage()
                ));
            }
        }

        return new Collection;
    }

    public function reverseQuery(GeoQueryInterface $query): Collection
    {
        foreach ($this->providers as $name => $config) {
            $provider = $this->geocoder->makeProvider($name, $config);

            try {
                $result = $provider->reverseQuery($query);
                if ($result->isNotEmpty())
                    return $result;
            }
            catch (Throwable $e) {
                $coordinates = $query->getCoordinates();
                $this->log(sprintf(
                    'Provider "%s" could not reverse coordinates: "%f %f".',
                    $provider->getName(),
                    $coordinates->getLatitude(),
                    $coordinates->getLongitude()
                ));
            }
        }

        return new Collection;
    }

    public function addProvider($name, array $config = [])
    {
        $this->providers[$name] = $config;

        return $this;
    }
}