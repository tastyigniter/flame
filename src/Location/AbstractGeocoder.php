<?php

namespace Igniter\Flame\Location;

use Illuminate\Support\Fluent;

abstract class AbstractGeocoder
{
    /**
     * Handle the geocoder request.
     *
     * @param $address
     *
     * @return GeoPosition|bool
     */
    public function geocode($address)
    {
        $response = $this->process($address);

        if ($response instanceof Fluent) {
            $position = $this->hydrate(new GeoPosition(), $response);
            $position->driver = get_class($this);

            return $position;
        }

        return FALSE;
    }

    /**
     * Returns url content as string.
     *
     * @param string $url
     *
     * @return mixed
     */
    protected function getUrlContent($url)
    {
        $timeout = 5;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $urlContent = curl_exec($ch);
        curl_close($ch);

        return $urlContent;
    }

    /**
     * Returns the URL to use for querying the current driver.
     *
     * @return string
     */
    abstract protected function url();

    /**
     * Hydrates the position with the given location
     * instance using the drivers array map.
     *
     * @param GeoPosition $position
     * @param Fluent $location
     *
     * @return GeoPosition
     */
    abstract protected function hydrate(GeoPosition $position, Fluent $location);

    /**
     * Process the specified driver.
     *
     * @param $request
     *
     * @return Fluent|bool
     */
    abstract protected function process($request);
}