<?php

namespace Igniter\Flame\Location;

use Illuminate\Contracts\Support\Arrayable;

class GeoPosition implements Arrayable
{
    public $latitude;

    public $longitude;

    public $formattedAddress;

    public $city;

    public $state;

    public $stateCode;

    public $country;

    public $countryCode;

    public $postalCode;

    public function __construct(array $attributes = [])
    {
        $this->fillFromArray($attributes);
    }

    public function isValid()
    {
        if (!is_float($this->latitude))
            return FALSE;

        if (!is_float($this->longitude))
            return FALSE;

        return TRUE;
    }

    /**
     * Fill the position properties from an array.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function fillFromArray(array $attributes)
    {
        $this->latitude = array_get($attributes, 'latitude', $this->latitude);
        $this->longitude = array_get($attributes, 'longitude', $this->longitude);
        $this->formattedAddress = array_get($attributes, 'formattedAddress', $this->formattedAddress);
        $this->city = array_get($attributes, 'city', $this->city);
        $this->state = array_get($attributes, 'state', $this->state);
        $this->stateCode = array_get($attributes, 'stateCode', $this->stateCode);
        $this->country = array_get($attributes, 'country', $this->country);
        $this->countryCode = array_get($attributes, 'countryCode', $this->countryCode);
        $this->postalCode = array_get($attributes, 'postalCode', $this->postalCode);
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array $attributes
     *
     * @return \Igniter\Flame\Location\GeoPosition
     */
    public static function fromArray(array $attributes)
    {
        return new self($attributes);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
            'formattedAddress' => $this->formattedAddress,
            'city'             => $this->city,
            'state'            => $this->state,
            'stateCode'        => $this->stateCode,
            'country'          => $this->country,
            'countryCode'      => $this->countryCode,
            'postalCode'       => $this->postalCode,
        ];
    }
}