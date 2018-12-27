<?php

namespace Igniter\Flame\Geolite\Contracts;

interface GeocoderInterface
{
    /**
     * The default result limit.
     */
    const DEFAULT_RESULT_LIMIT = 5;

    public function geocode($address);

    public function reverse(float $latitude, float $longitude);

    public function makeProvider($name): AbstractProvider;
}