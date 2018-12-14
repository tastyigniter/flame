<?php

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts;
use Igniter\Flame\Geolite\Model;
use InvalidArgumentException;

class Circle implements Contracts\CircleInterface
{
    const TYPE = 'CIRCLE';

    /**
     * @var Contracts\CoordinatesInterface
     */
    protected $coordinate;

    /**
     * @var int
     */
    protected $radius;

    /**
     * The user unit.
     *
     * @var string
     */
    protected $unit;

    /**
     * @var integer
     */
    protected $precision = 8;

    /**
     * @param null|array|Model\Coordinates $coordinate
     * @param int $radius
     */
    public function __construct($coordinate, int $radius)
    {
        if ($coordinate instanceof Contracts\CoordinatesInterface) {
            $this->coordinate = $coordinate;
        }
        else if (is_array($coordinate)) {
            list($latitude, $longitude) = $coordinate;
            $this->coordinate = new Model\Coordinates($latitude, $longitude);
        }
        else {
            throw new InvalidArgumentException();
        }

        $this->radius = $radius;
    }

    public function getRadius()
    {
        return $this->radius;
    }

    /**
     * Returns the geometry type.
     *
     * @return string
     */
    public function getGeometryType()
    {
        return static::TYPE;
    }

    /**
     * Returns the precision of the geometry.
     *
     * @return integer
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param  integer $precision
     * @return $this
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;

        return $this;
    }

    public function getCoordinate()
    {
        return $this->coordinate;
    }

    /**
     * {@inheritDoc}
     */
    public function getCoordinates()
    {
        return $this->getCoordinate();
    }

    /**
     * {@inheritDoc}
     */
    public function setCoordinates(Model\CoordinatesCollection $coordinates)
    {
        $this->coordinates = $coordinates;
        $this->bounds->setPolygon($this);

        return $this;
    }

    //
    //
    //

    /**
     * Returns true if the geometry is empty.
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return !$this->getCoordinate()->getLatitude()
            OR !$this->getCoordinate()->getLongitude()
            OR !$this->getRadius();
    }

    public function distanceUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * @param  Contracts\CoordinatesInterface $coordinate
     * @return boolean
     */
    public function pointInRadius(Contracts\CoordinatesInterface $coordinate)
    {
        $distance = new Distance();
        $distance->in($this->unit)
                 ->setFrom($coordinate)
                 ->setTo($this->getCoordinate());

        $radius = $distance->convertToUserUnit($this->getRadius());

        return ($distance->haversine() <= $radius);
    }

    /**
     * Returns the bounding box of the Geometry
     *
     * @return \Igniter\Flame\Geolite\Model\Bounds
     */
    public function getBounds()
    {
        return null;
    }
}