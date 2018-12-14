<?php namespace Igniter\Flame\Geolite\Contracts;

interface DistanceInterface
{
    /**
     * Set the origin coordinate
     *
     * @param CoordinatesInterface $from The origin coordinate
     *
     * @return DistanceInterface
     */
    public function setFrom(CoordinatesInterface $from);

    /**
     * Get the origin coordinate
     *
     * @return CoordinatesInterface
     */
    public function getFrom();

    /**
     * Set the destination coordinate
     *
     * @param CoordinatesInterface $to The destination coordinate
     *
     * @return DistanceInterface
     */
    public function setTo(CoordinatesInterface $to);

    /**
     * Get the destination coordinate
     *
     * @return CoordinatesInterface
     */
    public function getTo();

    /**
     * Set the user unit
     *
     * @param string $unit Set the unit
     *
     * @return DistanceInterface
     */
    public function in($unit);

    public function haversine();
}
