<?php

namespace Igniter\Flame\Currency;

use Carbon\Carbon;
use Igniter\Flame\Currency\Contracts\CurrencyInterface;
use Illuminate\Contracts\Cache\Factory as FactoryContract;
use Illuminate\Support\Arr;

class Currency
{
    /**
     * Currency configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Application cache
     *
     * @var \Illuminate\Contracts\Cache\Factory
     */
    protected $cache;

    /**
     * Currency model instance.
     *
     * @var Contracts\CurrencyInterface
     */
    protected $model;

    /**
     * Formatter instance.
     *
     * @var Contracts\FormatterInterface
     */
    protected $formatter;

    /**
     * User's currency
     *
     * @var string
     */
    protected $userCurrency;

    /**
     * Cached currencies
     *
     * @var \Illuminate\Support\Collection
     */
    protected $currenciesCache;

    /**
     * Loaded currencies
     *
     * @var \Illuminate\Support\Collection
     */
    protected $loadedCurrencies;

    /**
     * Create a new instance.
     *
     * @param array $config
     * @param FactoryContract $cache
     */
    public function __construct(array $config, FactoryContract $cache)
    {
        $this->config = $config;
        $this->cache = $cache->store($this->config('cache_driver'));
    }

    /**
     * Format given number.
     *
     * @param float $amount
     * @param string $from
     * @param string $to
     * @param bool $format
     *
     * @return string
     */
    public function convert($amount, $from = null, $to = null, $format = TRUE)
    {
        // Get currencies involved
        $from = $from ?: $this->config('default');
        $to = $to ?: $this->getUserCurrency();

        // Ensure exchange rates is fresh
        $this->updateRates();

        // Get exchange rates
        $fromRate = optional($this->getCurrency($from))->getRate();
        $toRate = optional($this->getCurrency($to))->getRate();

        // Skip invalid to currency rates
        if ($toRate === null) {
            return null;
        }

        // Convert amount
        $value = $amount * $toRate * (1 / $fromRate);

        // Should the result be formatted?
        if ($format === TRUE) {
            return $this->format($value, $to);
        }

        // Return value
        return $value;
    }

    /**
     * Format the value into the desired currency.
     *
     * @param float $value
     * @param string $code
     * @param bool $includeSymbol
     *
     * @return string
     */
    public function format($value, $code = null, $includeSymbol = TRUE)
    {
        // Get default currency if one is not set
        $code = $code ?: $this->config('default');

        // Remove unnecessary characters
        $value = preg_replace('/[\s\',!]/', '', $value);

        // Check for a custom formatter
        if ($formatter = $this->getFormatter()) {
            return $formatter->format($value, $code);
        }

        // Get the measurement format
        $format = optional($this->getCurrency($code))->getFormat();

        // Value Regex
        $valRegex = '/([0-9].*|)[0-9]/';

        // Match decimal and thousand separators
        preg_match_all('/[\s\',.!]/', $format, $separators);

        if (($thousand = array_get($separators, '0.0', null)) AND $thousand == '!') {
            $thousand = '';
        }

        $decimal = array_get($separators, '0.1', null);

        // Match format for decimals count
        preg_match($valRegex, $format, $valFormat);

        $valFormat = array_get($valFormat, 0, 0);

        // Count decimals length
        $decimals = $decimal ? strlen(substr(strrchr($valFormat, $decimal), 1)) : 0;

        // Do we have a negative value?
        if ($negative = $value < 0 ? '-' : '') {
            $value *= -1;
        }

        // Format the value
        $value = number_format($value, $decimals, $decimal, $thousand);

        // Apply the formatted measurement
        if ($includeSymbol) {
            $value = preg_replace($valRegex, $value, $format);
        }

        // Return value
        return $negative.$value;
    }

    /**
     * Set user's currency.
     *
     * @param string $code
     */
    public function setUserCurrency($code)
    {
        $this->userCurrency = strtoupper($code);
    }

    /**
     * Return the user's currency code.
     *
     * @return string
     */
    public function getUserCurrency()
    {
        $code = $this->userCurrency ?: $this->config('default');

        return optional($this->getCurrency($code))->currency_code;
    }

    /**
     * Determine if the provided currency is valid.
     *
     * @param string $code
     *
     * @return bool
     */
    public function hasCurrency($code)
    {
        return (bool)$this->getCurrency(strtoupper($code));
    }

    /**
     * Determine if the provided currency is active.
     *
     * @param string $code
     *
     * @return bool
     */
    public function isActive($code)
    {
        return $code AND (bool)optional($this->getCurrency($code))->isEnabled();
    }

    /**
     * Return the current currency if the
     * one supplied is not valid.
     *
     * @param string $code
     *
     * @return \Igniter\Flame\Currency\Contracts\CurrencyInterface
     */
    public function getCurrency($code = null)
    {
        if (isset($this->currenciesCache[$code])) {
            return $this->currenciesCache[$code];
        }

        $code = $code ?: $this->getUserCurrency();

        $currency = $this->getCurrencies()->first(function (CurrencyInterface $currency) use ($code) {
            return $currency->isEnabled() AND ((int)$code === $currency->getId()) OR ($code === $currency->getCode());
        });

        return $this->currenciesCache[$code] = $currency;
    }

    /**
     * Return all currencies.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCurrencies()
    {
        if ($this->loadedCurrencies === null) {
            $this->loadCurrencies();
        }

        return $this->loadedCurrencies;
    }

    /**
     * Get currency model.
     *
     * @return \Igniter\Flame\Currency\Contracts\CurrencyInterface|\Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        if ($this->model === null) {
            // Get model class
            $model = $this->config('model', Models\Currency::class);

            // Create model instance
            $this->model = new $model();
        }

        return $this->model;
    }

    /**
     * Get formatter driver.
     *
     * @return \Igniter\Flame\Currency\Contracts\FormatterInterface
     */
    public function getFormatter()
    {
        if ($this->formatter === null && $this->config('formatter') !== null) {
            // Get formatter configuration
            $config = $this->config('formatters.'.$this->config('formatter'), []);

            // Get formatter class
            $class = Arr::pull($config, 'class');

            // Create formatter instance
            $this->formatter = new $class(array_filter($config));
        }

        return $this->formatter;
    }

    /**
     * Clear cached currencies.
     */
    public function clearCache()
    {
        $this->cache->forget('igniter.currency');
    }

    /**
     * Get configuration value.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function config($key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return Arr::get($this->config, $key, $default);
    }

    protected function loadCurrencies()
    {
        $currencies = $this->cache->rememberForever('igniter.currency', function () {
            return $this->getModel()->get();
        });

        $this->loadedCurrencies = $currencies;
    }

    //
    //
    //

    public function updateRates($skipCache = FALSE)
    {
        $base = $this->config('default');

        $rates = $this->getRates($base, $skipCache);

        $this->getCurrencies()->each(function (CurrencyInterface $currency) use ($rates) {
            if ($rate = array_get($rates, $currency->getCode()))
                $currency->updateRate($rate);
        });
    }

    protected function getRates($base, $skipCache = FALSE)
    {
        $duration = Carbon::now()->addHours($this->config('ratesCacheDuration', 0));

        $currencies = $this->getCurrencies();

        if ($skipCache)
            return app('currency.converter')->getExchangeRates($base, $currencies);

        return $this->cache->remember('igniter.currency.rates', $duration, function () use ($base, $currencies) {
            return app('currency.converter')->getExchangeRates($base, $currencies);
        });
    }

    /**
     * Get a given value from the current currency.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getCurrency()->$$key;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->getModel(), $method], $parameters);
    }
}
