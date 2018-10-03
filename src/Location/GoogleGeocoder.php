<?php

namespace Igniter\Flame\Location;

use Illuminate\Support\Fluent;

class GoogleGeocoder extends AbstractGeocoder
{
    protected $status;

    protected $errorMessage;

    /**
     * Returns the URL to use for querying the current driver.
     *
     * @return string
     */
    protected function url()
    {
        return 'https://maps.googleapis.com/maps/api/geocode/json';
    }

    protected function apiKey()
    {
        return setting('maps_api_key');
    }

    /**
     * Hydrates the position with the given location
     * instance using the drivers array map.
     *
     * @param \Igniter\Flame\Location\GeoPosition $position
     * @param Fluent $address
     *
     * @return GeoPosition
     */
    protected function hydrate(GeoPosition $position, Fluent $address)
    {
        $position->status = $this->status;
        $position->error = $this->errorMessage;

        foreach ($address->get('address_components', []) as $component) {
            if (in_array('country', $component->types)) {
                $position->country = $component->long_name;
                $position->countryCode = $component->short_name;
            }

            if (in_array('administrative_area_level_1', $component->types)) {
                $position->state = $component->short_name;
                $position->stateCode = $component->short_name;
            }

            if (in_array('administrative_area_level_2', $component->types))
                $position->city = $component->long_name;

            if (in_array('postal_code', $component->types))
                $position->postalCode = $component->long_name;
        }

        $position->formattedAddress = $address->get('formatted_address');
        if ($geometry = $address->get('geometry')) {
            $position->latitude = $geometry->location->lat;
            $position->longitude = $geometry->location->lng;
        }

        return $position;
    }

    /**
     * Process the specified driver.
     *
     * @param array $request
     *
     * @return Fluent|bool
     */
    protected function process($request)
    {
        $this->status = null;
        $this->errorMessage = null;

        try {
            $url = $this->url().'?'.$this->buildUrlParams($request);

            $response = json_decode($this->getUrlContent($url));

            if (!$response OR !isset($response->status))
                return FALSE;

            $this->status = $response->status;

            if (isset($response->error_message))
                $this->errorMessage = $response->error_message;

//            if ($response->status == 'OK' AND isset($response->results[0]))
            return new Fluent($response->results[0] ?? []);
        }
        catch (\Exception $ex) {
            return FALSE;
        }
    }

    protected function buildUrlParams($params = [])
    {
        if (!isset($params['key']))
            $params['key'] = $this->apiKey();

        return http_build_query($params);
    }
}