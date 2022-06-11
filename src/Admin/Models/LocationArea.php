<?php

namespace Igniter\Admin\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Database\Traits\Sortable;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Geolite\Contracts\LocationInterface;
use Igniter\Flame\Geolite\Facades\Geocoder;
use Igniter\Flame\Location\Contracts\AreaInterface;
use InvalidArgumentException;

/**
 * LocationArea Model Class
 */
class LocationArea extends Model implements AreaInterface
{
    use HasFactory;
    use Validation;
    use Sortable;

    const VERTEX = 'vertex';

    const BOUNDARY = 'boundary';

    const INSIDE = 'inside';

    const OUTSIDE = 'outside';

    const SORT_ORDER = 'priority';

    /**
     * @var string The database table name
     */
    protected $table = 'location_areas';

    protected $primaryKey = 'area_id';

    public $relation = [
        'belongsTo' => [
            'location' => [\Igniter\Admin\Models\Location::class],
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

    protected $fillable = ['area_id', 'type', 'name', 'boundaries', 'conditions', 'is_default', 'priority'];

    public $rules = [
        ['type', 'igniter::admin.locations.label_area_type', 'sometimes|required|string'],
        ['name', 'igniter::admin.locations.label_area_name', 'sometimes|required|string'],
        ['area_id', 'igniter::admin.locations.label_area_id', 'integer'],
        ['boundaries.components', 'igniter::admin.locations.label_address_component', 'sometimes|required_if:type,address'],
        ['boundaries.components.*.type', 'igniter::admin.locations.label_address_component_type', 'sometimes|required|string'],
        ['boundaries.components.*.value', 'igniter::admin.locations.label_address_component_value', 'sometimes|required|string'],
        ['boundaries.polygon', 'igniter::admin.locations.label_area_shape', 'sometimes|required_if:type,polygon'],
        ['boundaries.circle', 'igniter::admin.locations.label_area_circle', 'sometimes|required_if:type,circle|json'],
        ['boundaries.vertices', 'igniter::admin.locations.label_area_vertices', 'sometimes|required_unless:type,address|json'],
        ['boundaries.distance.*.type', 'igniter::admin.locations.label_area_distance', 'sometimes|required|string'],
        ['boundaries.distance.*.distance', 'igniter::admin.locations.label_area_distance', 'sometimes|required|numeric'],
        ['boundaries.distance.*.charge', 'igniter::admin.locations.label_area_charge', 'sometimes|required|numeric'],
        ['conditions', 'igniter::admin.locations.label_delivery_condition', 'sometimes|required'],
        ['conditions.*.amount', 'igniter::admin.locations.label_area_charge', 'sometimes|required|numeric'],
        ['conditions.*.type', 'igniter::admin.locations.label_charge_condition', 'sometimes|required|alpha_dash'],
        ['conditions.*.total', 'igniter::admin.locations.label_area_min_amount', 'sometimes|required|numeric'],
    ];

    public $boundary;

    public function getConditionsAttribute($value)
    {
        // backward compatibility v2.0
        if (!is_array($conditions = json_decode($value, true)))
            $conditions = [];

        foreach ($conditions as $key => &$item) {
            if (isset($item['condition'])) {
                $item['type'] = $item['condition'];
                unset($item['condition']);
            }
        }

        return $conditions;
    }

    protected function afterSave()
    {
        if (!$this->is_default)
            return;

        $this->newQuery()
            ->where('location_id', $this->location_id)
            ->whereKeyNot($this->getKey())
            ->update(['is_default' => 0]);
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

    public function getColorAttribute($value)
    {
        if (!strlen($value))
            $value = $this->pickColor();

        return $value;
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
