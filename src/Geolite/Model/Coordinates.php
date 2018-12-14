<?php namespace Igniter\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;

class Coordinates implements CoordinatesInterface
{
    /**
     * @var float
     */
    private $latitude;

    /**
     * @var float
     */
    private $longitude;

    /**
     * The selected ellipsoid.
     *
     * @var Ellipsoid
     */
    protected $ellipsoid;

    /**
     * @param float $latitude
     * @param float $longitude
     * @param \Igniter\Flame\Geolite\Model\Ellipsoid|null $ellipsoid
     */
    public function __construct($latitude, $longitude, Ellipsoid $ellipsoid = null)
    {
        $this->latitude = $this->normalizeLatitude($latitude);
        $this->longitude = $this->normalizeLongitude($longitude);
        $this->ellipsoid = $ellipsoid ?: Ellipsoid::createFromName(Ellipsoid::WGS84);
    }

    /**
     * Set the latitude.
     *
     * @param double $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * Set the longitude.
     *
     * @param double $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
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
     * Returns the latitude.
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Returns the longitude.
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * {@inheritDoc}
     */
    public function getEllipsoid()
    {
        return $this->ellipsoid;
    }

    /**
     * @return integer
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * Returns a boolean determining coordinates equality
     * @param  CoordinatesInterface $coordinate
     * @return boolean
     */
    public function isEqual(CoordinatesInterface $coordinate)
    {
        return bccomp($this->latitude, $coordinate->getLatitude(), $this->getPrecision()) === 0
            AND bccomp($this->longitude, $coordinate->getLongitude(), $this->getPrecision()) === 0;
    }

    /**
     * Normalizes a latitude to the (-90, 90) range.
     * Latitudes below -90.0 or above 90.0 degrees are capped, not wrapped.
     *
     * @param double $latitude The latitude to normalize
     *
     * @return float
     */
    public function normalizeLatitude($latitude)
    {
        return (float)max(-90, min(90, $latitude));
    }

    /**
     * Normalizes a longitude to the (-180, 180) range.
     * Longitudes below -180.0 or abode 180.0 degrees are wrapped.
     *
     * @param double $longitude The longitude to normalize
     *
     * @return float
     */
    public function normalizeLongitude($longitude)
    {
        if (180 === $longitude % 360)
            return 180.0;

        $mod = fmod($longitude, 360);
        $fallback = $mod > 180 ? $mod - 360 : $mod;
        $longitude = $mod < -180 ? $mod + 360 : $fallback;

        return (float)$longitude;
    }

    /**
     * Returns the coordinates as a tuple
     *
     * @return array
     */
    public function toArray()
    {
        return [$this->getLongitude(), $this->getLatitude()];
    }

    protected function typeToString($value): string
    {
        return is_object($value) ? get_class($value) : gettype($value);
    }
}