<?php

namespace Igniter\Flame\Geolite\Contracts;

interface CircleInterface
{
    /**
     * Returns the geometry type.
     *
     * @return string
     */
    public function getGeometryType();

    /**
     * Returns the precision of the geometry.
     *
     * @return integer
     */
    public function getPrecision();

    /**
     *  Returns a vertex of this <code>Geometry</code> (usually, but not necessarily, the first one).
     *  The returned coordinate should not be assumed to be an actual Coordinate object used in
     *  the internal representation.
     *
     * @return \Igniter\Flame\Geolite\Contracts\CoordinatesInterface if there's a coordinate in the collection
     * @return null if this Geometry is empty
     */
    public function getCoordinate();

    /**
     *  Returns a collection containing the values of all the vertices for this geometry.
     *  If the geometry is a composite, the array will contain all the vertices
     *  for the components, in the order in which the components occur in the geometry.
     *
     * @return \Igniter\Flame\Geolite\Model\CoordinatesCollection the vertices of this <code>Geometry</code>
     */
    public function getCoordinates();

    public function getRadius();

    public function distanceUnit($unit);

    public function pointInRadius(CoordinatesInterface $coordinate);

    /**
     * Returns true if the geometry is empty.
     *
     * @return boolean
     */
    public function isEmpty();
}