<?php namespace Igniter\Flame\Geolite\Contracts;

interface BoundsInterface
{
    /**
     * @return float
     */
    public function getNorth();

    /**
     * @return float
     */
    public function getEast();

    /**
     * @return float
     */
    public function getSouth();

    /**
     * @return float
     */
    public function getWest();

    /**
     * @return \Igniter\Flame\Geolite\Contracts\PolygonInterface
     */
    public function getAsPolygon();

    /**
     * @param  \Igniter\Flame\Geolite\Contracts\BoundsInterface $bounds
     * @return BoundsInterface
     */
    public function merge(BoundsInterface $bounds);
}
