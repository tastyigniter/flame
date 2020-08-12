<?php

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Exception\GeoliteException;
use Igniter\Flame\Geolite\Model\Ellipsoid;

class Distance implements Contracts\DistanceInterface
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
     * The user unit.
     *
     * @var string
     */
    protected $unit;

    /**
     * {@inheritdoc}
     */
    public function setFrom(Contracts\CoordinatesInterface $from)
    {
        $this->from = $from;

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
    public function in($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Returns the approximate flat distance between two coordinates
     * using Pythagoras’ theorem which is not very accurate.
     * @see http://en.wikipedia.org/wiki/Pythagorean_theorem
     * @see http://en.wikipedia.org/wiki/Equirectangular_projection
     *
     * @return float The distance in meters
     */
    public function flat()
    {
        Ellipsoid::checkCoordinatesEllipsoid($this->from, $this->to);

        $latA = deg2rad($this->from->getLatitude());
        $lngA = deg2rad($this->from->getLongitude());
        $latB = deg2rad($this->to->getLatitude());
        $lngB = deg2rad($this->to->getLongitude());

        $x = ($lngB - $lngA) * cos(($latA + $latB) / 2);
        $y = $latB - $latA;

        $sqrt = sqrt(($x * $x) + ($y * $y));

        return $this->convertToUserUnit($sqrt * $this->from->getEllipsoid()->getA());
    }

    /**
     * Returns the approximate distance between two coordinates
     * using the spherical trigonometry called Great Circle Distance.
     * @see http://www.ga.gov.au/earth-monitoring/geodesy/geodetic-techniques/distance-calculation-algorithms.html#circle
     * @see http://en.wikipedia.org/wiki/Cosine_law
     *
     * @return float The distance in meters
     */
    public function greatCircle()
    {
        Ellipsoid::checkCoordinatesEllipsoid($this->from, $this->to);

        $latA = deg2rad($this->from->getLatitude());
        $lngA = deg2rad($this->from->getLongitude());
        $latB = deg2rad($this->to->getLatitude());
        $lngB = deg2rad($this->to->getLongitude());

        $degrees = acos(sin($latA)
            * sin($latB)
            + cos($latA)
            * cos($latB)
            * cos($lngB - $lngA)
        );

        return $this->convertToUserUnit($degrees * $this->from->getEllipsoid()->getA());
    }

    /**
     * Returns the approximate sea level great circle (Earth) distance between
     * two coordinates using the Haversine formula which is accurate to around 0.3%.
     * @see http://www.movable-type.co.uk/scripts/latlong.html
     *
     * @return float The distance in meters
     */
    public function haversine()
    {
        Ellipsoid::checkCoordinatesEllipsoid($this->from, $this->to);

        $latA = deg2rad($this->from->getLatitude());
        $lngA = deg2rad($this->from->getLongitude());
        $latB = deg2rad($this->to->getLatitude());
        $lngB = deg2rad($this->to->getLongitude());

        $dLat = $latB - $latA;
        $dLon = $lngB - $lngA;

        $a = sin($dLat / 2) * sin($dLat / 2) + cos($latA) * cos($latB) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $this->convertToUserUnit($this->from->getEllipsoid()->getA() * $c);
    }

    /**
     * Returns geodetic distance between between two coordinates using Vincenty inverse
     * formula for ellipsoids which is accurate to within 0.5mm.
     * @see http://www.movable-type.co.uk/scripts/latlong-vincenty.html
     *
     * @return float The distance in meters
     */
    public function vincenty()
    {
        Ellipsoid::checkCoordinatesEllipsoid($this->from, $this->to);

        $a = $this->from->getEllipsoid()->getA();
        $b = $this->from->getEllipsoid()->getB();
        $f = 1 / $this->from->getEllipsoid()->getInvF();

        $lL = deg2rad($this->to->getLongitude() - $this->from->getLongitude());
        $u1 = atan((1 - $f) * tan(deg2rad($this->from->getLatitude())));
        $u2 = atan((1 - $f) * tan(deg2rad($this->to->getLatitude())));

        $sinU1 = sin($u1);
        $cosU1 = cos($u1);
        $sinU2 = sin($u2);
        $cosU2 = cos($u2);

        $lambda = $lL;
        $iterLimit = 100;

        do {
            $sinLambda = sin($lambda);
            $cosLambda = cos($lambda);
            $sinSigma = sqrt(($cosU2 * $sinLambda) * ($cosU2 * $sinLambda) +
                ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda) * ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda));

            if (0.0 === $sinSigma) {
                return 0.0; // co-incident points
            }

            $cosSigma = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
            $sigma = atan2($sinSigma, $cosSigma);
            $sinAlpha = $cosU1 * $cosU2 * $sinLambda / $sinSigma;
            $cosSqAlpha = 1 - $sinAlpha * $sinAlpha;
            if ($cosSqAlpha != 0.0) {
                $cos2SigmaM = $cosSigma - 2 * $sinU1 * $sinU2 / $cosSqAlpha;
            }
            else {
                $cos2SigmaM = 0.0;
            }
            $cC = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
            $lambdaP = $lambda;
            $lambda = $lL + (1 - $cC) * $f * $sinAlpha * ($sigma + $cC * $sinSigma *
                    ($cos2SigmaM + $cC * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)));
        } while (abs($lambda - $lambdaP) > 1e-12 && --$iterLimit > 0);

        if (0 === $iterLimit) {
            throw new GeoliteException('Vincenty formula failed to converge !');
        }

        $uSq = $cosSqAlpha * ($a * $a - $b * $b) / ($b * $b);
        $aA = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
        $bB = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));
        $deltaSigma = $bB * $sinSigma * ($cos2SigmaM + $bB / 4 * ($cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) -
                    $bB / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)));
        $s = $b * $aA * ($sigma - $deltaSigma);

        return $this->convertToUserUnit($s);
    }

    /**
     * Converts results in meters to user's unit (if any).
     * The default returned value is in meters.
     *
     * @param float $meters
     *
     * @return float
     */
    public function convertToUserUnit($meters)
    {
        switch ($this->unit) {
            case Geolite::KILOMETER_UNIT:
                return $meters / 1000;
            case Geolite::MILE_UNIT:
                return $meters / Geolite::METERS_PER_MILE;
            case Geolite::FOOT_UNIT:
                return $meters / Geolite::FEET_PER_METER;
            default:
                return $meters;
        }
    }
}
