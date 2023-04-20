<?php

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\PolygonInterface;

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
     * @var bool
     */
    protected $hasCoordinate = false;

    /**
     * @var int
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
        elseif (is_array($coordinates) || is_null($coordinates)) {
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
     * {@inheritdoc}
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * {@inheritdoc}
     */
    public function setCoordinates(Model\CoordinatesCollection $coordinates)
    {
        $this->coordinates = $coordinates;
        $this->bounds->setPolygon($this);

        return $this;
    }

    /**
     * @return int
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param int $precision
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
     * @param Model\Bounds $bounds
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
                [$latitude, $longitude] = $value;
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
     * @param Contracts\CoordinatesInterface $coordinate
     * @return bool
     */
    public function pointInPolygon(Contracts\CoordinatesInterface $coordinate)
    {
        if ($this->isEmpty())
            return false;

        if ($this->pointOnVertex($coordinate))
            return true;

        if ($this->pointIsInside($coordinate))
            return true;

        return $this->pointOnBoundary($coordinate);
    }

    /**
     * @param Contracts\CoordinatesInterface $coordinate
     * @return bool
     */
    public function pointOnBoundary(Contracts\CoordinatesInterface $coordinate)
    {
        $total = $this->count();
        for ($i = 0; $i < $total; $i++) {
            $j = ($i + 1) % $total;
            $currentVertex = $this->get($i);
            $nextVertex = $this->get($j);

            if (
                $currentVertex->getLatitude() == $nextVertex->getLatitude()
                && $currentVertex->getLatitude() == $coordinate->getLatitude()
                && ($coordinate->getLongitude() >= min($currentVertex->getLongitude(), $nextVertex->getLongitude()))
                && ($coordinate->getLongitude() <= max($currentVertex->getLongitude(), $nextVertex->getLongitude()))
            ) {
                return true;
            }

            if (
                ($currentVertex->getLongitude() > $coordinate->getLongitude()) != ($nextVertex->getLongitude() > $coordinate->getLongitude())
                && $coordinate->getLatitude() < ($nextVertex->getLatitude() - $currentVertex->getLatitude())
                * ($coordinate->getLongitude() - $currentVertex->getLongitude())
                / ($nextVertex->getLongitude() - $currentVertex->getLongitude())
                + $currentVertex->getLatitude()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Contracts\CoordinatesInterface $coordinate
     * @return bool
     */
    public function pointOnVertex(Contracts\CoordinatesInterface $coordinate)
    {
        foreach ($this->coordinates as $vertexCoordinate) {
            if (bccomp(
                    $vertexCoordinate->getLatitude(),
                    $coordinate->getLatitude(),
                    $this->getPrecision()
                ) === 0 &&
                bccomp(
                    $vertexCoordinate->getLongitude(),
                    $coordinate->getLongitude(),
                    $this->getPrecision()
                ) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    protected function pointIsInside(Contracts\CoordinatesInterface $coordinate): bool
    {
        $inside = false;
        $total = $this->count();
        for ($i = 0; $i < $total; $i++) {
            $j = ($i + 1) % $total;
            $currentVertex = $this->get($i);
            $nextVertex = $this->get($j);

            if ((
                    $currentVertex->getLongitude() < $coordinate->getLongitude()
                    && $nextVertex->getLongitude() >= $coordinate->getLongitude()
                    || $nextVertex->getLongitude() < $coordinate->getLongitude()
                    && $currentVertex->getLongitude() >= $coordinate->getLongitude()
                ) && (
                    $currentVertex->getLatitude()
                    + ($coordinate->getLongitude() - $currentVertex->getLongitude())
                    / ($nextVertex->getLongitude() - $currentVertex->getLongitude())
                    * ($nextVertex->getLatitude() - $currentVertex->getLatitude())
                    < $coordinate->getLatitude()
                )
            ) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    //
    //
    //

    /**
     * @return bool
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
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->coordinates->jsonSerialize();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->coordinates->offsetExists($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->coordinates->offsetGet($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->coordinates->offsetSet($offset, $value);
        $this->bounds->setPolygon($this);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $coordinates = $this->coordinates->offsetUnset($offset);
        $this->bounds->setPolygon($this);

        return $coordinates;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return $this->coordinates->count();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return $this->coordinates->getIterator();
    }
}
