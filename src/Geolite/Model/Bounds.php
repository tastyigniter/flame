<?php namespace Igniter\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Contracts;
use Igniter\Flame\Geolite\Polygon;

class Bounds implements Contracts\BoundsInterface
{
    /**
     * @var float
     */
    protected $south;

    /**
     * @var float
     */
    protected $west;

    /**
     * @var float
     */
    protected $north;

    /**
     * @var float
     */
    protected $east;

    /**
     * @var integer
     */
    protected $precision = 8;

    /**
     * @param float $south South bound, also min latitude
     * @param float $west West bound, also min longitude
     * @param float $north North bound, also max latitude
     * @param float $east East bound, also max longitude
     */
    public function __construct($south, $west, $north, $east)
    {
        $south = (float)$south;
        $north = (float)$north;
        $west = (float)$west;
        $east = (float)$east;

        $this->south = $south;
        $this->west = $west;
        $this->north = $north;
        $this->east = $east;
    }

    public static function fromPolygon(Contracts\PolygonInterface $polygon)
    {
        $bounds = new static(null, null, null, null);
        $bounds->setPolygon($polygon);

        return $bounds;
    }

    /**
     * @param  float $north
     * @return $this
     */
    public function setNorth($north)
    {
        $this->north = $north;

        return $this;
    }

    /**
     * @param  float $east
     * @return $this
     */
    public function setEast($east)
    {
        $this->east = $east;

        return $this;
    }

    /**
     * @param  float $south
     * @return $this
     */
    public function setSouth($south)
    {
        $this->south = $south;

        return $this;
    }

    /**
     * @param  float $west
     * @return $this
     */
    public function setWest($west)
    {
        $this->west = $west;

        return $this;
    }

    /**
     * Returns the south bound.
     *
     * @return float
     */
    public function getSouth(): float
    {
        return $this->south;
    }

    /**
     * Returns the west bound.
     *
     * @return float
     */
    public function getWest(): float
    {
        return $this->west;
    }

    /**
     * Returns the north bound.
     *
     * @return float
     */
    public function getNorth(): float
    {
        return $this->north;
    }

    /**
     * Returns the east bound.
     *
     * @return float
     */
    public function getEast(): float
    {
        return $this->east;
    }

    /**
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

    /**
     * @param  Contracts\CoordinatesInterface $coordinate
     * @return bool
     */
    public function pointInBounds(Contracts\CoordinatesInterface $coordinate)
    {
        return !(bccomp($coordinate->getLatitude(), $this->getSouth(), $this->getPrecision()) === -1
            OR bccomp($coordinate->getLatitude(), $this->getNorth(), $this->getPrecision()) === 1
            OR bccomp($coordinate->getLongitude(), $this->getEast(), $this->getPrecision()) === 1
            OR bccomp($coordinate->getLongitude(), $this->getWest(), $this->getPrecision()) === -1);
    }

    /**
     * @return Contracts\PolygonInterface
     */
    public function getAsPolygon()
    {
        $northWest = new Coordinates($this->north, $this->west);

        return new Polygon(
            new CoordinatesCollection([
                $northWest,
                new Coordinates($this->north, $this->east),
                new Coordinates($this->south, $this->east),
                new Coordinates($this->south, $this->west),
                $northWest
            ])
        );
    }

    public function setPolygon(Contracts\PolygonInterface $polygon)
    {
        foreach ($polygon->getCoordinates() as $coordinate) {
            $this->addCoordinate($coordinate);
        }
    }

    /**
     * @param  Contracts\BoundsInterface $bounds
     * @return Contracts\BoundsInterface
     */
    public function merge(Contracts\BoundsInterface $bounds)
    {
        $cBounds = clone $this;

        $newCoordinates = $bounds->getAsPolygon()->getCoordinates();
        foreach ($newCoordinates as $coordinate) {
            $cBounds->addCoordinate($coordinate);
        }

        return $cBounds;
    }

    /**
     * Returns an array with bounds.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'south' => $this->getSouth(),
            'west' => $this->getWest(),
            'north' => $this->getNorth(),
            'east' => $this->getEast(),
        ];
    }

    /**
     * @param Contracts\CoordinatesInterface $coordinate
     */
    protected function addCoordinate(Contracts\CoordinatesInterface $coordinate)
    {
        $latitude = $coordinate->getLatitude();
        $longitude = $coordinate->getLongitude();

        if (!$this->north AND !$this->south AND !$this->east AND !$this->west) {
            $this->setNorth($latitude);
            $this->setSouth($latitude);
            $this->setEast($longitude);
            $this->setWest($longitude);
        }
        else {
            if (bccomp($latitude, $this->getSouth(), $this->getPrecision()) === -1)
                $this->setSouth($latitude);

            if (bccomp($latitude, $this->getNorth(), $this->getPrecision()) === 1)
                $this->setNorth($latitude);

            if (bccomp($longitude, $this->getEast(), $this->getPrecision()) === 1)
                $this->setEast($longitude);

            if (bccomp($longitude, $this->getWest(), $this->getPrecision()) === -1)
                $this->setWest($longitude);
        }
    }
}