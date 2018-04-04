<?php

namespace Igniter\Flame\Location\Traits;

use Exception;
use Igniter\Flame\Location\GeoPosition;
use Igniter\Flame\Location\Models\Area;

trait HasDeliveryAreas
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $deliveryAreas;

    public function listDeliveryAreas()
    {
        if (!$this->deliveryAreas)
            $this->loadDeliveryAreas();

        return $this->deliveryAreas;
    }

    public function findOrNewDeliveryArea($areaId)
    {
        if ($area = $this->findDeliveryArea($areaId))
            return $area;

        $area = Area::make();
        $area->location_id = $this->getKey();
        return $area;
    }

    /**
     * @param $areaId
     *
     * @return \Igniter\Flame\Location\Models\Area|null
     */
    public function findDeliveryArea($areaId)
    {
        if (!is_numeric($areaId))
            return null;

        $areas = $this->listDeliveryAreas();
        if (!$areas OR !count($areas))
            return null;

        return $areas->get($areaId);
    }

    public function findAllDeliveryAreas()
    {
        if (!$this->hasRelation('delivery_areas'))
            throw new Exception(sprintf("Model '%s' does not contain a definition for 'delivery_areas'.",
                get_class($this)));

        return $this->delivery_areas()->get();
    }

    /**
     * @param \Igniter\Flame\Location\GeoPosition $position
     *
     * @return \Igniter\Flame\Location\Models\Area|null
     * @throws \Exception
     */
    public function filterDeliveryArea(GeoPosition $position)
    {
        $areas = $this->findAllDeliveryAreas();

        $area = $areas->filter(function (Area $model) use ($position) {
            return $model->checkBoundary($position) != 'outside';
        })->first();

        return $area;
    }

    /**
     * @param \Igniter\Flame\Location\GeoPosition $position
     *
     * @return \Igniter\Flame\Location\Models\Area|null
     * @throws \Exception
     */
    public function findOrFirstDeliveryArea(GeoPosition $position)
    {
        if (!$area = $this->findDeliveryArea($position))
            $area = $this->findAllDeliveryAreas()->first();

        return $area;
    }

    public function getDistanceUnit()
    {
        return strtolower(isset($this->distanceUnit) ? $this->distanceUnit : setting('distance_unit'));
    }

    protected function loadDeliveryAreas()
    {
        $deliveryAreas = $this->findAllDeliveryAreas();

        $this->deliveryAreas = $deliveryAreas->keyBy('area_id');
    }
}