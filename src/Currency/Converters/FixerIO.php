<?php

namespace Igniter\Flame\Currency\Converters;

use Exception;
use Illuminate\Support\Facades\Log;

class FixerIO extends AbstractConverter
{
    const API_URL = 'http://data.fixer.io/api/latest?access_key=%s&base=%s&symbols=%s';

    protected $accessKey;

    public function __construct(array $config = [])
    {
        $this->accessKey = $config['apiKey'];
    }

    /**
     * @inheritDoc
     */
    public function converterDetails()
    {
        return [
            'name' => 'Fixer.io',
            'description' => 'Conversion services by Fixer.io',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getExchangeRates($base, array $currencies)
    {
        if (!strlen($this->accessKey))
            return [];

        try {
            $response = $this->getHttpClient()->get(
                sprintf(self::API_URL, $this->accessKey, $base, implode(',', $currencies))
            );

            $result = json_decode($response->getBody(), TRUE);

            if (isset($result['success']) AND !$result['success'])
                throw new Exception('An error occurred when requesting currency exchange rates from fixer.io, check your api key.');

            return $result['rates'] ?? [];
        }
        catch (Exception $ex) {
            Log::info($ex->getMessage());
        }
    }
}