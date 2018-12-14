<?php namespace Igniter\Flame\Location\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Location\Contracts\AreaInterface;

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
            'location' => ['Admin\Models\Locations_model'],
        ],
    ];

    public $casts = [
        'boundaries' => 'serialize',
        'conditions' => 'serialize',
    ];

    protected $appends = ['vertices', 'circle'];

    protected static $areaColors = [
        '#F16745', '#FFC65D', '#7BC8A4', '#4CC3D9', '#93648D', '#404040',
        '#F16745', '#FFC65D', '#7BC8A4', '#4CC3D9', '#93648D', '#404040',
        '#F16745', '#FFC65D', '#7BC8A4', '#4CC3D9', '#93648D', '#404040',
        '#F16745', '#FFC65D',
    ];

    public function getChargeSummaryTrans($name)
    {
        $trans = [
            'all' => '{amount} on all orders',
            'above' => '{amount} above {total}',
            'below' => '{amount} below {total}',
        ];

        if (is_null($name))
            return $trans;

        return $trans[$name] ?? null;
    }

    //
    // Accessors & Mutators
    //

    public function getVerticesAttribute()
    {
        return isset($this->boundaries['vertices']) ?
            json_decode($this->boundaries['vertices']) : [];
    }

    public function getCircleAttribute()
    {
        return isset($this->boundaries['circle']) ?
            json_decode($this->boundaries['circle']) : null;
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

    public function getLocationId()
    {
        return $this->attributes['location_id'];
    }

    public function deliveryAmount($cartTotal)
    {
        return $this->getConditionValue('amount', $cartTotal);
    }

    public function minimumOrderTotal($cartTotal)
    {
        return $this->getConditionValue('total', $cartTotal);
    }

    public function listConditions()
    {
        $conditions = [];
        if (!$this->conditions)
            return $conditions;

        foreach ($this->conditions as $condition) {
            $condition['label'] = $this->getChargeSummaryTrans($condition['type']);

            $conditions[] = $condition;
        }

        return $conditions;
    }

    public function checkBoundary(CoordinatesInterface $coordinate)
    {
        return ($this->type == 'polygon')
            ? $this->pointInVertices($coordinate)
            : $this->pointInCircle($coordinate);
    }

    // Check if the point is inside the polygon or on the boundary
    public function pointInVertices(CoordinatesInterface $coordinate)
    {
        if (!$this->vertices)
            return FALSE;

        return $this->getPolygon()->pointInPolygon($coordinate);
    }

    public function pointInCircle(CoordinatesInterface $coordinate)
    {
        if (!$this->circle)
            return FALSE;

        $circle = $this->getCircle();

        $circle->distanceUnit(setting('distance_unit'));

        return $circle->pointInRadius($coordinate);
    }

    protected function getConditionValue($type, $cartTotal)
    {
        if (!$condition = $this->filterConditionRules($type, $cartTotal))
            return null;

        $condition = (object)$condition;

        // Delivery is unavailable when delivery charge from the matched rule is -1
        if ($condition->amount < 0)
            return $type == 'total' ? $condition->total : null;

        // At this stage, minimum total is 0 when the matched condition is a below rule
        if ($type == 'total' AND $condition->type == 'below')
            return 0;

        return $condition->{$type};
    }

    protected function filterConditionRules($value = 'total', $cartTotal)
    {
        $collection = collect($this->conditions);

        if ($value == 'total')
            return $collection->sortBy($value)->first();

        return $collection->first(function ($condition) use ($cartTotal) {
            switch ($condition['type']) {
                case 'all':
                    return TRUE;
                case 'below':
                    return $cartTotal < $condition['total'];
                case 'above':
                    return $cartTotal > $condition['total'];
            }
        });
    }
}