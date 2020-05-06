<?php

namespace Igniter\Flame\Geolite\Provider;

use GuzzleHttp\Client as HttpClient;
use Igniter\Flame\Geolite\Contracts\AbstractProvider;
use Igniter\Flame\Geolite\Contracts\GeoQueryInterface;
use Igniter\Flame\Geolite\Exception\GeoliteException;
use Igniter\Flame\Geolite\Model;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class GoogleProvider extends AbstractProvider
{
    /**
     * @var array
     */
    protected $config = [];

    public function __construct(HttpClient $client, array $config)
    {
        $this->httpClient = $client;
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'Google Maps';
    }

    public function geocodeQuery(GeoQueryInterface $query): Collection
    {
        $endpoint = array_get($this->config, 'endpoints.geocode');
        $url = $this->prependGeocodeQuery($query, sprintf($endpoint,
            rawurlencode($query->getText())
        ));

        $result = [];
        try {
            $result = $this->cacheCallback($url, function () use ($query, $url) {
                return $this->hydrateResponse(
                    $this->requestUrl($url, $query),
                    $query->getLimit()
                );
            });
        }
        catch (Throwable $ex) {
            $this->log(sprintf(
                'Provider "%s" could not geocode address, "%s".',
                $this->getName(), $ex->getMessage()
            ));
        }

        return new Collection($result);
    }

    public function reverseQuery(GeoQueryInterface $query): Collection
    {
        $coordinates = $query->getCoordinates();

        $endpoint = array_get($this->config, 'endpoints.reverse');
        $url = $this->prependReverseQuery($query, sprintf($endpoint,
            $coordinates->getLatitude(),
            $coordinates->getLongitude()
        ));

        $result = [];
        try {
            $result = $this->cacheCallback($url, function () use ($query, $url) {
                return $this->hydrateResponse(
                    $this->requestUrl($url, $query),
                    $query->getLimit()
                );
            });
        }
        catch (Throwable $e) {
            $coordinates = $query->getCoordinates();
            $this->log(sprintf(
                'Provider "%s" could not reverse coordinates: "%f %f".',
                $this->getName(), $coordinates->getLatitude(), $coordinates->getLongitude()
            ));
        }

        return new Collection($result);
    }

    protected function requestUrl($url, GeoQueryInterface $query)
    {
        if ($locale = $query->getLocale())
            $url = sprintf('%s&language=%s', $url, $locale);

        if ($region = $query->getData('region', array_get($this->config, 'region')))
            $url = sprintf('%s&region=%s', $url, $region);

        if ($apiKey = array_get($this->config, 'apiKey'))
            $url = sprintf('%s&key=%s', $url, $apiKey);

        $response = $this->getHttpClient()->get($url, [
            'timeout' => $query->getData('timeout', 15),
        ]);

        return $this->parseResponse($response);
    }

    protected function hydrateResponse($response, int $limit)
    {
        $result = [];
        foreach ($response->results as $place) {
            $address = new Model\Location($this->getName());

            // set official Google place id
            if (isset($place->place_id))
                $address->setValue('id', $place->place_id);

            if (isset($place->geometry))
                $this->parseCoordinates($address, $place->geometry);

            if (isset($place->address_components))
                $this->parseAddressComponents($address, $place->address_components);

            if (isset($place->formatted_address))
                $address->withFormattedAddress($place->formatted_address);

            $result[] = $address;
            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    //
    //
    //

    /**
     * Decode the response content and validate it to make sure it does not have any errors.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return mixed result from json_decode()
     *
     * @throws \Igniter\Flame\Geolite\Exception\GeoliteException
     */
    protected function parseResponse(ResponseInterface $response)
    {
        $json = json_decode($response->getBody()->getContents(), FALSE);

        // API error
        if (!$json) {
            throw new GeoliteException(
                'The geocoder server returned an empty or invalid response.'
            );
        }

        if ($json->status === 'REQUEST_DENIED') {
            throw new GeoliteException(sprintf(
                'API access denied. Message: %s', $json->error_message ?? 'empty error message'
            ));
        }

        // you are over your quota
        if ($json->status === 'OVER_QUERY_LIMIT') {
            throw new GeoliteException(sprintf(
                'Daily quota exceeded. Message: %s', $json->error_message ?? 'empty error message'
            ));
        }

        if (!isset($json->results)
            OR !count($json->results)
            OR $json->status !== 'OK'
        ) throw new GeoliteException($json->error_message ?? 'empty error message');

        return $json;
    }

    protected function prependGeocodeQuery(GeoQueryInterface $query, $url): string
    {
        if ($bounds = $query->getBounds()) {
            $url .= sprintf('&bounds=%s,%s|%s,%s',
                $bounds->getSouth(), $bounds->getWest(),
                $bounds->getNorth(), $bounds->getEast()
            );
        }

        if ($components = $query->getData('components')) {
            $url .= sprintf('&components=%s',
                urlencode($this->serializeComponents($components))
            );
        }

        return $url;
    }

    protected function prependReverseQuery(GeoQueryInterface $query, $url): string
    {
        if ($locationType = $query->getData('location_type'))
            $url .= '&location_type='.urlencode($locationType);

        if ($resultType = $query->getData('result_type'))
            $url .= '&result_type='.urlencode($resultType);

        return $url;
    }

    protected function parseCoordinates(Model\Location $address, $geometry)
    {
        $coordinates = $geometry->location;
        $address->setCoordinates($coordinates->lat, $coordinates->lng);

        if (isset($geometry->bounds)) {
            $address->setBounds(
                $geometry->bounds->southwest->lat,
                $geometry->bounds->southwest->lng,
                $geometry->bounds->northeast->lat,
                $geometry->bounds->northeast->lng
            );
        }
        elseif (isset($geometry->viewport)) {
            $address->setBounds(
                $geometry->viewport->southwest->lat,
                $geometry->viewport->southwest->lng,
                $geometry->viewport->northeast->lat,
                $geometry->viewport->northeast->lng
            );
        }
        elseif ('ROOFTOP' === $geometry->location_type) {
            // Fake bounds
            $address->setBounds(
                $coordinates->lat,
                $coordinates->lng,
                $coordinates->lat,
                $coordinates->lng
            );
        }
    }

    protected function parseAddressComponents(Model\Location $address, $components)
    {
        foreach ($components as $component) {
            foreach ($component->types as $type) {
                $this->parseAddressComponent($address, $type, $component);
            }
        }
    }

    protected function parseAddressComponent(Model\Location $address, $type, $component)
    {
        switch ($type) {
            case 'postal_code':
                return $address->setPostalCode($component->long_name);
            case 'locality':
            case 'postal_town':
                return $address->setLocality($component->long_name);
            case 'administrative_area_level_1':
            case 'administrative_area_level_2':
            case 'administrative_area_level_3':
            case 'administrative_area_level_4':
            case 'administrative_area_level_5':
                return $address->addAdminLevel(
                    (int)substr($type, -1),
                    $component->long_name,
                    $component->short_name
                );
            case 'sublocality_level_1':
            case 'sublocality_level_2':
            case 'sublocality_level_3':
            case 'sublocality_level_4':
            case 'sublocality_level_5':
                $subLocalityLevel = $address->getValue('subLocalityLevel', []);
                $subLocalityLevel[] = [
                    'level' => (int)substr($type, -1),
                    'name' => $component->long_name,
                    'code' => $component->short_name,
                ];

                return $address->setValue('subLocalityLevel', $subLocalityLevel);
            case 'country':
                $address->setCountryName($component->long_name);

                return $address->setCountryCode($component->short_name);
            case 'street_number':
                return $address->setStreetNumber($component->long_name);
            case 'route':
                return $address->setStreetName($component->long_name);
            case 'sublocality':
                return $address->setSubLocality($component->long_name);
            case 'street_address':
            case 'intersection':
            case 'political':
            case 'colloquial_area':
            case 'ward':
            case 'neighborhood':
            case 'premise':
            case 'subpremise':
            case 'natural_feature':
            case 'airport':
            case 'park':
            case 'point_of_interest':
            case 'establishment':
                return $address->setValue($type, $component->long_name);
            default:
        }
    }

    protected function serializeComponents($components)
    {
        if (is_string($components))
            return $components;

        return implode('|', array_map(function ($name, $value) {
            return sprintf('%s:%s', $name, $value);
        }, array_keys($components), $components));
    }
}