<?php

namespace Igniter\Flame\Currency\Converters;

use Exception;
use Illuminate\Support\Facades\Log;

class OpenExchangeRates extends AbstractConverter
{
    const API_URL = 'https://openexchangerates.org/api/latest.json?app_id=%s&base=%s&symbols=%s';

    protected $appId;

    public function __construct(array $config = [])
    {
        $this->appId = $config['apiKey'];
    }

    /**
     * @inheritDoc
     */
    public function converterDetails()
    {
        return [
            'name' => 'Open Exchange Rates',
            'description' => 'Conversion services provided by Open Exchange Rates.',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getExchangeRates($base, array $currencies)
    {
        try {
            $response = $this->getHttpClient()->get(
                sprintf(self::API_URL, $this->appId, $base, implode(',', $currencies))
            );

            $result = json_decode($response->getBody(), TRUE);

            if (isset($result['error']) AND $result['error'])
                throw new Exception($result['description']);

            return $result['rates'] ?? [];
        }
        catch (Exception $ex) {
            Log::info($ex->getMessage());
        }
    }
}