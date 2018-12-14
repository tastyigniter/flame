<?php

namespace Igniter\Flame\Geolite\Provider;

use GuzzleHttp\Client as HttpClient;
use Igniter\Flame\Geolite\Contracts\AbstractProvider;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Igniter\Flame\Geolite\Exception\GeoliteException;
use Igniter\Flame\Geolite\Model;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;

class NominatimProvider extends AbstractProvider
{
    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param \GuzzleHttp\Client $client
     * @param array $config
     */
    public function __construct(HttpClient $client, array $config)
    {
        $this->httpClient = $client;
        $this->config = $config;
    }

    /**
     * Returns the provider name.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Open street maps';
    }

    /**
     * Handle the geocoder request.
     *
     * @param \Igniter\Flame\Geolite\Contracts\GeoQueryInterface $query
     * @return \Illuminate\Support\Collection
     */
    public function geocodeQuery(GeoQueryInterface $query): Collection
    {
        $url = sprintf(
            array_get($this->config, 'endpoints.geocode'),
            urlencode($query->getText()),
            $query->getLimit()
        );

        return $this->cacheCallback($url, function () use ($query, $url) {
            return $this->hydrateResponse(
                $this->requestUrl($url, $query)
            );
        });
    }

    /**
     * Handle the reverse geocoding request.
     *
     * @param \Igniter\Flame\Geolite\Contracts\GeoQueryInterface $query
     * @return \Illuminate\Support\Collection
     */
    public function reverseQuery(GeoQueryInterface $query): Collection
    {
        $coordinates = $query->getCoordinates();

        $url = sprintf(
            array_get($this->config, 'endpoints.reverse'),
            $coordinates->getLatitude(),
            $coordinates->getLongitude(),
            $query->getData('zoom', 18)
        );

        return $this->cacheCallback($url, function () use ($query, $url) {
            return $this->hydrateResponse(
                $this->requestUrl($url, $query)
            );
        });
    }

    protected function requestUrl($url, GeoQueryInterface $query)
    {
        if ($locale = $query->getLocale())
            $url = sprintf('%s&accept-language=%s', $url, $locale);

        $options['User-Agent'] = $query->getData('userAgent', request()->userAgent());
        $options['Referer'] = $query->getData('referer', request()->get('referer'));
        $options['timeout'] = $query->getData('timeout', 15);

        if (empty($options['User-Agent']))
            throw new GeoliteException('The User-Agent must be set to use the Nominatim provider.');

        $response = $this->getHttpClient()->get($url, $options);

        return $this->parseResponse($url, $response);
    }

    protected function hydrateResponse($response)
    {
        $result = [];
        foreach ($response as $location) {
            $address = new Model\Location($this->getName());

            $this->parseCoordinates($address, $location);

            // set official place id
            if (isset($location->place_id))
                $address->setValue('id', $location->place_id);

            $this->parseAddress($address, $location);

            if (isset($location->formatted_address))
                $address->withFormattedAddress($location->formatted_address);

            $result[] = $address;
        }

        return new Collection($result);
    }

    //
    //
    //

    protected function parseResponse($url, ResponseInterface $response)
    {
        $json = json_decode($response->getBody());

        if (empty($json)) {
            throw new GeoliteException(sprintf(
                'The geocoder server returned an invalid response for query: "%s".',
                $url
            ));
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode === 401 OR $statusCode === 403)
            throw new GeoliteException(sprintf(
                'API access denied. Request: %s - Message: %s',
                $url, $json->error_message
            ));

        if ($statusCode === 429)
            throw new GeoliteException(sprintf(
                'Daily quota exceeded %s - Message: %s',
                $url, $json->error_message
            ));

        if ($statusCode >= 300) {
            throw new GeoliteException(sprintf(
                'The geocoder server returned [%s] an invalid response for query: "%s" - Message: %s.',
                $statusCode, $url, $json->error_message
            ));
        }

        return $json;
    }

    protected function parseCoordinates(Model\Location $address, $location)
    {
        $address->setCoordinates($location->lat, $location->lon);

        if (isset($location->boundingbox)) {
            list($south, $north, $west, $east) = $location->boundingbox;
            $address->setBounds($south, $west, $north, $east);
        }
    }

    protected function parseAddress(Model\Location $address, $location)
    {
        foreach (['state', 'county'] as $level => $field) {
            if (isset($location->address->{$field})) {
                $address->addAdminLevel($level + 1, $location->address->{$field}, '');
            }
        }

        if (isset($location->address->postcode))
            $address->setPostalCode(current(explode(';', $location->address->postcode)));

        foreach (['city', 'town', 'village', 'hamlet'] as $field) {
            if (isset($location->address->{$field})) {
                $address->setLocality($location->address->{$field});
                break;
            }
        }

        $address->setStreetNumber($location->address->house_number ?? null);
        $address->setStreetName($location->address->road ?? $location->address->pedestrian ?? null);
        $address->setSubLocality($location->address->suburb ?? null);
        $address->setCountryName($location->address->country ?? null);

        $countryCode = $location->address->country_code ?? null;
        $address->setCountryCode($countryCode ? strtoupper($countryCode) : null);
    }
}