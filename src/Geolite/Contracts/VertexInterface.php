<?php namespace Igniter\Flame\Geolite\Contracts;

interface VertexInterface
{
    /**
     * Set the origin coordinate.
     *
     * @param \Igniter\Flame\Geolite\Contracts\CoordinatesInterface $from The origin coordinate.
     *
     * @return VertexInterface
     */
    public function setFrom(CoordinatesInterface $from);

    /**
     * Get the origin coordinate.
     *
     * @return \Igniter\Flame\Geolite\Contracts\CoordinatesInterface
     */
    public function getFrom();

    /**
     * Set the destination coordinate.
     *
     * @param \Igniter\Flame\Geolite\Contracts\CoordinatesInterface $to The destination coordinate.
     *
     * @return VertexInterface
     */
    public function setTo(CoordinatesInterface $to);

    /**
     * Get the destination coordinate.
     *
     * @return \Igniter\Flame\Geolite\Contracts\CoordinatesInterface
     */
    public function getTo();

    /**
     * Get the gradient (slope) of the vertex.
     *
     * @return integer
     */
    public function getGradient();

    /**
     * Get the ordinate (longitude) of the point where vertex intersects with the ordinate-axis (Prime-Meridian) of the coordinate system.
     *
     * @return integer
     */
    public function getOrdinateIntercept();
}
