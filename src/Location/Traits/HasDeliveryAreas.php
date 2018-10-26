<?php

namespace Igniter\Flame\Location\Traits;

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
        return $this->delivery_areas->keyBy('area_id');
    }

    /**
     * @param $areaId
     *
     * @return \Igniter\Flame\Location\Models\Area|null
     */
    public function findDeliveryArea($areaId)
    {
        return $this->listDeliveryAreas()->get($areaId);
    }

    /**
     * @param \Igniter\Flame\Location\GeoPosition $position
     *
     * @return \Igniter\Flame\Location\Models\Area|null
     * @throws \Exception
     */
    public function searchOrFirstDeliveryArea(GeoPosition $position)
    {
        if (!$area = $this->searchDeliveryArea($position))
            $area = $this->delivery_areas->first();

        return $area;
    }

    /**
     * @param \Igniter\Flame\Location\GeoPosition $position
     *
     * @return \Igniter\Flame\Location\Models\Area|null
     * @throws \Exception
     */
    public function searchDeliveryArea(GeoPosition $position)
    {
        return $this->delivery_areas->first(function (Area $model) use ($position) {
            return $model->checkBoundary($position) != Area::OUTSIDE;
        });
    }

    public function getDistanceUnit()
    {
        return strtolower($this->distanceUnit ?? setting('distance_unit'));
    }
}