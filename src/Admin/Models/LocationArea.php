<?php

namespace Igniter\Admin\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Traits\Sortable;
use Igniter\Flame\Database\Traits\Validation;
use Igniter\Flame\Location\Models\AbstractArea;

/**
 * LocationArea Model Class
 */
class LocationArea extends AbstractArea
{
    use HasFactory;
    use Validation;
    use Sortable;

    const SORT_ORDER = 'priority';

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
}
