<?php namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts;
use Igniter\Flame\Geolite\Contracts\PolygonInterface;
use Igniter\Flame\Geolite\Model;

class Polygon implements PolygonInterface, \Countable, \IteratorAggregate, \ArrayAccess, \JsonSerializable
{
    const TYPE = 'POLYGON';

    /**
     * @var Model\CoordinatesCollection
     */
    protected $coordinates;

    /**
     * @var Model\Bounds
     */
    protected $bounds;

    /**
     * @var boolean
     */
    protected $hasCoordinate = FALSE;

    /**
     * @var integer
     */
    protected $precision = 8;

    /**
     * @param null|array|Model\CoordinatesCollection $coordinates
     */
    public function __construct($coordinates = null)
    {
        if ($coordinates instanceof Model\CoordinatesCollection) {
            $this->coordinates = $coordinates;
        }
        else if (is_array($coordinates) OR is_null($coordinates)) {
            $this->coordinates = new Model\CoordinatesCollection([]);
        }
        else {
            throw new \InvalidArgumentException;
        }

        $this->bounds = Model\Bounds::fromPolygon($this);

        if (is_array($coordinates))
            $this->put($coordinates);
    }

    /**
     * @return string
     */
    public function getGeometryType()
    {
        return self::TYPE;
    }

    /**
     * @return Contracts\CoordinatesInterface
     */
    public function getCoordinate()
    {
        return $this->coordinates->offsetGet(0);
    }

    /**
     * {@inheritDoc}
     */
    public function getCoordinates()
    {
        return $this->coordinates;
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
        $this->bounds->setPrecision($precision);
        $this->precision = $precision;

        return $this;
    }

    /**
     * @return Model\Bounds
     */
    public function getBounds()
    {
        return $this->bounds;
    }

    /**
     * @param  Model\Bounds $bounds
     * @return $this
     */
    public function setBounds(Model\Bounds $bounds)
    {
        $this->bounds = $bounds;

        return $this;
    }

    //
    //
    //

    public function get($key)
    {
        return $this->coordinates->get($key);
    }

    public function put($key, Contracts\CoordinatesInterface $coordinate = null)
    {
        if (is_array($key)) {
            $values = $key;
        }
        elseif ($coordinate !== null) {
            $values = [$key => $coordinate];
        }
        else {
            throw new \InvalidArgumentException;
        }

        foreach ($values as $index => $value) {
            if (!$value instanceof Contracts\CoordinatesInterface) {
                list($latitude, $longitude) = $value;
                $value = new Model\Coordinates($latitude, $longitude);
            }

            $this->coordinates->put($index, $value);
        }

        $this->bounds->setPolygon($this);
    }

    public function push(Contracts\CoordinatesInterface $coordinate)
    {
        $coordinates = $this->coordinates->push($coordinate);

        $this->bounds->setPolygon($this);

        return $coordinates;
    }

    public function forget($key)
    {
        $coordinates = $this->coordinates->forget($key);

        $this->bounds->setPolygon($this);

        return $coordinates;
    }

    /**
     * @param  Contracts\CoordinatesInterface $coordinate
     * @return boolean
     */
    public function pointInPolygon(Contracts\CoordinatesInterface $coordinate)
    {
        if ($this->isEmpty())
            return FALSE;

        if (!$this->bounds->pointInBounds($coordinate))
            return FALSE;

        if ($this->pointOnVertex($coordinate))
            return TRUE;

        if ($this->pointOnBoundary($coordinate))
            return TRUE;

        return $this->pointOnIntersections($coordinate);
    }

    /**
     * @param  Contracts\CoordinatesInterface $coordinate
     * @return boolean
     */
    public function pointOnBoundary(Contracts\CoordinatesInterface $coordinate)
    {
        $total = $this->count();
        for ($i = 1; $i <= $total; $i++) {
            $currentVertex = $this->get($i - 1);
            $nextVertex = $this->get($i);

            if (is_null($nextVertex))
                $nextVertex = $this->get(0);

            // Check if coordinate is on a horizontal boundary
            if (bccomp(
                    $currentVertex->getLatitude(),
                    $nextVertex->getLatitude(),
                    $this->getPrecision()
                ) === 0
                AND bccomp(
                    $currentVertex->getLatitude(),
                    $coordinate->getLatitude(),
                    $this->getPrecision()
                ) === 0
                AND bccomp(
                    $coordinate->getLongitude(),
                    min($currentVertex->getLongitude(), $nextVertex->getLongitude()),
                    $this->getPrecision()
                ) === 1
                AND bccomp(
                    $coordinate->getLongitude(),
                    max($currentVertex->getLongitude(), $nextVertex->getLongitude()),
                    $this->getPrecision()
                ) === -1
            ) {
                return TRUE;
            }

            // Check if coordinate is on a boundary
            if (bccomp(
                    $coordinate->getLatitude(),
                    min($currentVertex->getLatitude(), $nextVertex->getLatitude()),
                    $this->getPrecision()
                ) === 1
                AND bccomp(
                    $coordinate->getLatitude(),
                    max($currentVertex->getLatitude(), $nextVertex->getLatitude()),
                    $this->getPrecision()
                ) <= 0
                AND bccomp(
                    $coordinate->getLongitude(),
                    max($currentVertex->getLongitude(), $nextVertex->getLongitude()),
                    $this->getPrecision()
                ) <= 0
                AND bccomp(
                    $currentVertex->getLatitude(),
                    $nextVertex->getLatitude(),
                    $this->getPrecision()
                ) !== 0
            ) {
                $xinters = ($coordinate->getLatitude() - $currentVertex->getLatitude())
                    * ($nextVertex->getLongitude() - $currentVertex->getLongitude())
                    / ($nextVertex->getLatitude() - $currentVertex->getLatitude())
                    + $currentVertex->getLongitude();

                if (bccomp(
                        $xinters,
                        $coordinate->getLongitude(),
                        $this->getPrecision()
                    ) === 0
                ) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    /**
     * @param  Contracts\CoordinatesInterface $coordinate
     * @return boolean
     */
    public function pointOnVertex(Contracts\CoordinatesInterface $coordinate)
    {
        foreach ($this->coordinates as $vertexCoordinate) {
            if (bccomp(
                    $vertexCoordinate->getLatitude(),
                    $coordinate->getLatitude(),
                    $this->getPrecision()
                ) === 0 AND
                bccomp(
                    $vertexCoordinate->getLongitude(),
                    $coordinate->getLongitude(),
                    $this->getPrecision()
                ) === 0
            ) {
                return TRUE;
            }
        }

        return FALSE;
    }

    protected function pointOnIntersections(Contracts\CoordinatesInterface $coordinate): bool
    {
        $total = $this->count();
        $intersections = 0;
        for ($i = 1; $i < $total; $i++) {
            $currentVertex = $this->get($i - 1);
            $nextVertex = $this->get($i);

            if (bccomp(
                    $coordinate->getLatitude(),
                    min($currentVertex->getLatitude(), $nextVertex->getLatitude()),
                    $this->getPrecision()
                ) === 1
                AND bccomp(
                    $coordinate->getLatitude(),
                    max($currentVertex->getLatitude(), $nextVertex->getLatitude()),
                    $this->getPrecision()
                ) <= 0
                AND bccomp(
                    $coordinate->getLongitude(),
                    max($currentVertex->getLongitude(), $nextVertex->getLongitude()),
                    $this->getPrecision()
                ) <= 0
                AND bccomp(
                    $currentVertex->getLatitude(),
                    $nextVertex->getLatitude(),
                    $this->getPrecision()
                ) !== 0
            ) {
                $xinters = ($coordinate->getLatitude() - $currentVertex->getLatitude())
                    * ($nextVertex->getLongitude() - $currentVertex->getLongitude())
                    / ($nextVertex->getLatitude() - $currentVertex->getLatitude())
                    + $currentVertex->getLongitude();

                if (bccomp(
                        $coordinate->getLongitude(),
                        $xinters,
                        $this->getPrecision()
                    ) <= 0
                    OR bccomp(
                        $currentVertex->getLongitude(),
                        $nextVertex->getLongitude(),
                        $this->getPrecision()
                    ) === 0
                ) {
                    $intersections++;
                }
            }
        }

        return ($intersections % 2) != 0;
    }

    //
    //
    //

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return $this->count() < 1;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->coordinates->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->coordinates->jsonSerialize();
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return $this->coordinates->offsetExists($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->coordinates->offsetGet($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->coordinates->offsetSet($offset, $value);
        $this->bounds->setPolygon($this);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        $coordinates = $this->coordinates->offsetUnset($offset);
        $this->bounds->setPolygon($this);

        return $coordinates;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return $this->coordinates->count();
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return $this->coordinates->getIterator();
    }
}
