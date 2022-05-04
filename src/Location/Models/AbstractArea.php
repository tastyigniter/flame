<?php

namespace Igniter\Flame\Location\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Geolite\Contracts\LocationInterface;
use Igniter\Flame\Geolite\Facades\Geocoder;
use Igniter\Flame\Location\Contracts\AreaInterface;
use InvalidArgumentException;

abstract class AbstractArea extends Model implements AreaInterface
{
    const VERTEX = 'vertex';

    const BOUNDARY = 'boundary';

    const INSIDE = 'inside';

    const OUTSIDE = 'outside';

    /**
     * @var string The database table name
     */
    protected $table = 'location_areas';

    protected $primaryKey = 'area_id';

    public $relation = [
        'belongsTo' => [
            'location' => [\Admin\Models\Locations_model::class],
        ],
    ];

    protected $casts = [
        'boundaries' => 'array',
        'conditions' => 'array',
        'is_default' => 'boolean',
    ];

    protected $appends = ['vertices', 'circle'];

    public static $areaColors = [
        '#F16745', '#FFC65D', '#7BC8A4', '#4CC3D9', '#93648D', '#404040',
        '#F16745', '#FFC65D', '#7BC8A4', '#4CC3D9', '#93648D', '#404040',
        '#F16745', '#FFC65D', '#7BC8A4', '#4CC3D9', '#93648D', '#404040',
        '#F16745', '#FFC65D',
    ];

    public function getColorAttribute($value)
    {
        if (!strlen($value))
            $value = $this->pickColor();

        return $value;
    }

    protected function pickColor()
    {
        return array_random(self::$areaColors);
    }

    //
    // Accessors & Mutators
    //

    public function getVerticesAttribute()
    {
        return isset($this->boundaries['vertices']) ?
            json_decode($this->boundaries['vertices'], false) : [];
    }

    public function getCircleAttribute()
    {
        return isset($this->boundaries['circle']) ?
            json_decode($this->boundaries['circle'], false) : null;
    }

    //
    // Helpers
    //

    /**
     * @return \Igniter\Flame\Geolite\Contracts\PolygonInterface
     */
    public function getPolygon()
    {
        $geolite = app('geolite');
        $vertices = array_map(function ($coordinates) use ($geolite) {
            return $geolite->coordinates($coordinates->lat, $coordinates->lng);
        }, $this->vertices);

        return $geolite->polygon($vertices);
    }

    /**
     * @return \Igniter\Flame\Geolite\Contracts\CircleInterface
     */
    public function getCircle()
    {
        $geolite = app('geolite');
        $coordinate = $geolite->coordinates(
            $this->circle->lat,
            $this->circle->lng
        );

        return $geolite->circle($coordinate, $this->circle->radius);
    }

    public function isAddressBoundary()
    {
        return $this->type === 'address';
    }

    public function isPolygonBoundary()
    {
        return $this->type === 'polygon';
    }

    public function getLocationId()
    {
        return $this->attributes['location_id'];
    }

    public function checkBoundary($coordinate)
    {
        if (!$coordinate instanceof CoordinatesInterface) {
            throw new InvalidArgumentException(sprintf(
                'Invalid class "%s" given, expected: %s',
                get_class($coordinate), CoordinatesInterface::class
            ));
        }

        if ($this->isAddressBoundary()) {
            $position = Geocoder::reverse(
                $coordinate->getLatitude(), $coordinate->getLongitude()
            )->first();

            if ($position)
                return $this->matchAddressComponents($position);
        }

        return $this->isPolygonBoundary()
            ? $this->pointInVertices($coordinate)
            : $this->pointInCircle($coordinate);
    }

    // Check if the point is inside the polygon or on the boundary
    public function pointInVertices(CoordinatesInterface $coordinate)
    {
        if (!$this->vertices)
            return false;

        return $this->getPolygon()->pointInPolygon($coordinate);
    }

    public function pointInCircle(CoordinatesInterface $coordinate)
    {
        if (!$this->circle)
            return false;

        $circle = $this->getCircle();

        $circle->distanceUnit(setting('distance_unit'));

        return $circle->pointInRadius($coordinate);
    }

    public function matchAddressComponents(LocationInterface $position)
    {
        $components = array_get($this->boundaries, 'components');
        if (!is_array($components))
            $components = [];

        $groupedComponents = collect($components)->groupBy('type')->all();

        return app('geolite')->addressMatch($groupedComponents)->matches($position);
    }
}
