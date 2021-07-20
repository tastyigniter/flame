<?php

namespace Igniter\Flame\Currency;

use Illuminate\Support\Collection;
use Illuminate\Support\Manager;

class Converter extends Manager
{
    public function getExchangeRates($base, Collection $currencies)
    {
        $currencies = ($currencies->map->getCode())->all();

        return $this->driver()->getExchangeRates($base, $currencies);
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

        return $this->createDriver($driver);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDriver()
    {
        return $this->container['config']['currency.converter'] ?? 'openexchangerates';
    }

    public function createOpenExchangeRatesDriver()
    {
        $config = $this->container['config']['currency.converters.openexchangerates'];

        return new $config['class']($config);
    }

    public function createFixerIODriver()
    {
        $config = $this->container['config']['currency.converters.fixerio'];

        return new $config['class']($config);
    }
}
