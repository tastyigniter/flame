<?php

namespace Igniter\Flame\Geolite;

class Vertex implements Contracts\VertexInterface
{
    /**
     * The origin coordinate.
     *
     * @var Contracts\CoordinatesInterface
     */
    protected $from;

    /**
     * The destination coordinate.
     *
     * @var Contracts\CoordinatesInterface
     */
    protected $to;

    /**
     * @var float
     */
    protected $gradient;

    /**
     * @var float
     */
    protected $ordinateIntercept;

    /**
     * @var int
     */
    protected $precision = 8;

    /**
     * The cardinal points / directions (the four cardinal directions,
     * the four ordinal directions, plus eight further divisions).
     *
     * @var array
     */
    public static $cardinalPoints = [
        'N', 'NNE', 'NE', 'ENE',
        'E', 'ESE', 'SE', 'SSE',
        'S', 'SSW', 'SW', 'WSW',
        'W', 'WNW', 'NW', 'NNW',
        'N',
    ];

    /**
     * {@inheritdoc}
     */
    public function setFrom(Contracts\CoordinatesInterface $from)
    {
        $this->from = $from;

        if (empty($this->to) || ($this->to->getLatitude() - $this->from->getLatitude() === 0)) {
            return $this;
        }

        if ($this->to->getLatitude() !== $this->from->getLatitude()) {
            $this->gradient = ($this->to->getLongitude() - $this->from->getLongitude()) / ($this->to->getLatitude() - $this->from->getLatitude());
            $this->ordinateIntercept = $this->from->getLongitude() - $this->from->getLatitude() * $this->gradient;
        }
        else {
            $this->gradient = null;
            $this->ordinateIntercept = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * {@inheritdoc}
     */
    public function setTo(Contracts\CoordinatesInterface $to)
    {
        $this->to = $to;

        if (empty($this->from) || ($this->to->getLatitude() - $this->from->getLatitude() === 0)) {
            return $this;
        }

        if ($this->to->getLatitude() !== $this->from->getLatitude()) {
            $this->gradient = ($this->to->getLongitude() - $this->from->getLongitude()) / ($this->to->getLatitude() - $this->from->getLatitude());
            $this->ordinateIntercept = $this->from->getLongitude() - $this->from->getLatitude() * $this->gradient;
        }
        else {
            $this->gradient = null;
            $this->ordinateIntercept = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * {@inheritdoc}
     */
    public function getGradient()
    {
        return $this->gradient;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrdinateIntercept()
    {
        return $this->ordinateIntercept;
    }

    /**
     * @return int
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @param  int $precision
     * @return $this
     */
    public function setPrecision($precision)
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Returns the initial bearing from the origin coordinate
     * to the destination coordinate in degrees.
     *
     * @return float The initial bearing in degrees
     */
    public function initialBearing()
    {
        $latA = deg2rad($this->from->getLatitude());
        $latB = deg2rad($this->to->getLatitude());
        $dLng = deg2rad($this->to->getLongitude() - $this->from->getLongitude());

        $y = sin($dLng) * cos($latB);
        $x = cos($latA) * sin($latB) - sin($latA) * cos($latB) * cos($dLng);

        return (float)(rad2deg(atan2($y, $x)) + 360) % 360;
    }

    /**
     * Returns the final bearing from the origin coordinate
     * to the destination coordinate in degrees.
     *
     * @return float The final bearing in degrees
     */
    public function finalBearing()
    {
        $latA = deg2rad($this->to->getLatitude());
        $latB = deg2rad($this->from->getLatitude());
        $dLng = deg2rad($this->from->getLongitude() - $this->to->getLongitude());

        $y = sin($dLng) * cos($latB);
        $x = cos($latA) * sin($latB) - sin($latA) * cos($latB) * cos($dLng);

        return (float)((rad2deg(atan2($y, $x)) + 360) % 360 + 180) % 360;
    }

    /**
     * Returns the initial cardinal point / direction from the origin coordinate to
     * the destination coordinate.
     * @see http://en.wikipedia.org/wiki/Cardinal_direction
     *
     * @return string The initial cardinal point / direction
     */
    public function initialCardinal()
    {
        return static::$cardinalPoints[(int)round($this->initialBearing() / 22.5)];
    }

    /**
     * Returns the final cardinal point / direction from the origin coordinate to
     * the destination coordinate.
     * @see http://en.wikipedia.org/wiki/Cardinal_direction
     *
     * @return string The final cardinal point / direction
     */
    public function finalCardinal()
    {
        return static::$cardinalPoints[(int)round($this->finalBearing() / 22.5)];
    }

    /**
     * Returns the half-way point / coordinate along a great circle
     * path between the origin and the destination coordinates.
     *
     * @return \Igniter\Flame\Geolite\Model\Coordinates
     */
    public function middle()
    {
        $latA = deg2rad($this->from->getLatitude());
        $lngA = deg2rad($this->from->getLongitude());
        $latB = deg2rad($this->to->getLatitude());
        $lngB = deg2rad($this->to->getLongitude());

        $bx = cos($latB) * cos($lngB - $lngA);
        $by = cos($latB) * sin($lngB - $lngA);

        $lat3 = rad2deg(atan2(sin($latA) + sin($latB), sqrt((cos($latA) + $bx) * (cos($latA) + $bx) + $by * $by)));
        $lng3 = rad2deg($lngA + atan2($by, cos($latA) + $bx));

        return new Model\Coordinates($lat3, $lng3);
    }

    /**
     * Returns the destination point with a given bearing in degrees travelling along a
     * (shortest distance) great circle arc and a distance in meters.
     *
     * @param int $bearing The bearing of the origin in degrees.
     * @param int $distance The distance from the origin in meters.
     *
     * @return \Igniter\Flame\Geolite\Model\Coordinates
     */
    public function destination($bearing, $distance)
    {
        $lat = deg2rad($this->from->getLatitude());
        $lng = deg2rad($this->from->getLongitude());

        $bearing = deg2rad($bearing);

        $endLat = asin(sin($lat) * cos($distance / $this->from->getEllipsoid()->getA()) + cos($lat) *
            sin($distance / $this->from->getEllipsoid()->getA()) * cos($bearing));
        $endLon = $lng + atan2(sin($bearing) * sin($distance / $this->from->getEllipsoid()->getA()) * cos($lat),
                cos($distance / $this->from->getEllipsoid()->getA()) - sin($lat) * sin($endLat));

        return new Model\Coordinates(rad2deg($endLat), rad2deg($endLon));
    }

    /**
     * Returns true if the vertex passed on argument is on the same line as this object
     *
     * @param  Vertex $vertex The vertex to compare
     * @return bool
     */
    public function isOnSameLine(Vertex $vertex)
    {
        if (is_null($this->getGradient()) AND is_null($vertex->getGradient()) AND $this->from->getLongitude() == $vertex->getFrom()->getLongitude()) {
            return TRUE;
        }

        if (!is_null($this->getGradient()) AND !is_null($vertex->getGradient())) {
            return
                bccomp($this->getGradient(), $vertex->getGradient(), $this->getPrecision()) === 0
                AND
                bccomp($this->getOrdinateIntercept(), $vertex->getOrdinateIntercept(), $this->getPrecision()) === 0;
        }

        return FALSE;
    }

    /**
     * Returns the other coordinate who is not the coordinate passed on argument
     * @param  Contracts\CoordinatesInterface $coordinate
     * @return null|Contracts\CoordinatesInterface
     */
    public function getOtherCoordinate(Contracts\CoordinatesInterface $coordinate)
    {
        if ($coordinate->isEqual($this->from))
            return $this->to;

        if ($coordinate->isEqual($this->to))
            return $this->from;

        return null;
    }

    /**
     * Returns the determinant value between $this (vertex) and another vertex.
     *
     * @param  Vertex $vertex
     * @return string
     */
    public function getDeterminant(Vertex $vertex)
    {
        $abscissaVertexOne = $this->to->getLatitude() - $this->from->getLatitude();
        $ordinateVertexOne = $this->to->getLongitude() - $this->from->getLongitude();
        $abscissaVertexSecond = $vertex->getTo()->getLatitude() - $vertex->getFrom()->getLatitude();
        $ordinateVertexSecond = $vertex->getTo()->getLongitude() - $vertex->getFrom()->getLongitude();

        return bcsub(
            bcmul($abscissaVertexOne, $ordinateVertexSecond, $this->precision),
            bcmul($abscissaVertexSecond, $ordinateVertexOne, $this->precision),
            $this->precision
        );
    }
}
