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

        if (!$this->bounds->pointInBounds($coordinate))
            return false;

        if ($this->pointOnVertex($coordinate))
            return true;

        if ($this->pointOnBoundary($coordinate))
            return true;

        return $this->pointIsInside($coordinate);
    }

    /**
     * @param Contracts\CoordinatesInterface $coordinate
     * @return bool
     */
    protected function pointOnBoundary(Contracts\CoordinatesInterface $coordinate)
    {
        $precision = $coordinate->getPrecision();
        $total = $this->count();
        for ($i = 1; $i <= $total; $i++) {
            $previousVertex = $this->get($i - 1);
            $currentVertex = $this->get($i) ?? $this->get(0);

            // Check if coordinate is on a horizontal boundary
            if (bccomp(
                    $previousVertex->formatLatitude(),
                    $currentVertex->formatLatitude(),
                    $precision
                ) === 0
                && bccomp(
                    $previousVertex->formatLatitude(),
                    $coordinate->formatLatitude(),
                    $precision
                ) === 0
                && bccomp(
                    $coordinate->formatLongitude(),
                    min($previousVertex->formatLongitude(), $currentVertex->formatLongitude()),
                    $precision
                ) === 1
                && bccomp(
                    $coordinate->formatLongitude(),
                    max($previousVertex->formatLongitude(), $currentVertex->formatLongitude()),
                    $precision
                ) === -1
            ) {
                return true;
            }

            // Check if coordinate is on a vertical boundary
            if (bccomp(
                    $previousVertex->formatLongitude(),
                    $currentVertex->formatLongitude(),
                    $precision
                ) === 0
                && bccomp(
                    $previousVertex->formatLongitude(),
                    $coordinate->formatLongitude(),
                    $precision
                ) === 0
                && bccomp(
                    $coordinate->formatLatitude(),
                    min($previousVertex->formatLatitude(), $currentVertex->formatLatitude()),
                    $precision
                ) >= 0
                && bccomp(
                    $coordinate->formatLatitude(),
                    max($previousVertex->formatLatitude(), $currentVertex->formatLatitude()),
                    $precision
                ) <= 0
            ) {
                return true;
            }

            if (
                bccomp(
                    number_format(($coordinate->formatLatitude() - $previousVertex->formatLatitude())
                        * ($currentVertex->formatLongitude() - $previousVertex->formatLongitude()), $precision),
                    number_format(($coordinate->formatLongitude() - $previousVertex->formatLongitude())
                        * ($currentVertex->formatLatitude() - $previousVertex->formatLatitude()), $precision),
                    $precision
                ) === 0
                && bccomp(
                    $coordinate->formatLongitude(),
                    min($previousVertex->formatLongitude(), $currentVertex->formatLongitude()),
                    $precision
                ) >= 0
                && bccomp(
                    $coordinate->formatLongitude(),
                    max($previousVertex->formatLongitude(), $currentVertex->formatLongitude()),
                    $precision
                ) <= 0
                && bccomp(
                    $coordinate->formatLatitude(),
                    min($previousVertex->formatLatitude(), $currentVertex->formatLatitude()),
                    $precision
                ) >= 0
                && bccomp(
                    $coordinate->formatLatitude(),
                    max($previousVertex->formatLatitude(), $currentVertex->formatLatitude()),
                    $precision
                ) <= 0
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
    protected function pointOnVertex(Contracts\CoordinatesInterface $coordinate)
    {
        $precision = $coordinate->getPrecision();
        foreach ($this->coordinates as $vertexCoordinate) {
            if (bccomp(
                    $vertexCoordinate->formatLatitude(),
                    $coordinate->formatLatitude(),
                    $precision
                ) === 0 &&
                bccomp(
                    $vertexCoordinate->formatLongitude(),
                    $coordinate->formatLongitude(),
                    $precision
                ) === 0
            ) {
                return true;
            }
        }

        return false;
    }

    protected function pointIsInside(Contracts\CoordinatesInterface $coordinate): bool
    {
        $precision = $coordinate->getPrecision();
        $total = $this->count();
        $intersections = 0;
        for ($i = 1; $i < $total; $i++) {
            $previousVertex = $this->get($i - 1);
            $nextVertex = $this->get($i);

            if (
                bccomp(
                    $previousVertex->getLatitude(), $coordinate->getLatitude(), $precision
                ) != bccomp(
                    $nextVertex->getLatitude(), $coordinate->getLatitude(), $precision
                ) && bccomp(
                    $coordinate->getLongitude(),
                    number_format(($nextVertex->getLongitude() - $previousVertex->getLongitude())
                        * ($coordinate->getLatitude() - $previousVertex->getLatitude())
                        / ($nextVertex->getLatitude() - $previousVertex->getLatitude())
                        + $previousVertex->getLongitude(), $precision
                    ),
                    $precision
                ) > 0
            ) {
                $intersections++;
            }
        }

        return ($intersections % 2) != 0;
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
