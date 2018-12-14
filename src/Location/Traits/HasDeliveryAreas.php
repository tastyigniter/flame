<?php

namespace Igniter\Flame\Location\Traits;

use Igniter\Flame\Location\Contracts\AreaInterface;

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
     * @return \Igniter\Flame\Location\Contracts\AreaInterface|null
     */
    public function findDeliveryArea($areaId)
    {
        return $this->listDeliveryAreas()->get($areaId);
    }

    /**
     * @param \Igniter\Flame\Geolite\Contracts\CoordinatesInterface $coordinates
     * @return \Igniter\Flame\Location\Contracts\AreaInterface|null
     */
    public function searchOrFirstDeliveryArea($coordinates)
    {
        if (!$area = $this->searchDeliveryArea($coordinates))
            $area = $this->delivery_areas->first();

        return $area;
    }

    /**
     * @param \Igniter\Flame\Geolite\Contracts\CoordinatesInterface $coordinates
     * @return \Igniter\Flame\Location\Contracts\AreaInterface|null
     */
    public function searchDeliveryArea($coordinates)
    {
        if (!$coordinates)
            return null;

        return $this->delivery_areas->first(function (AreaInterface $model) use ($coordinates) {
            return $model->checkBoundary($coordinates);
        });
    }

    public function getDistanceUnit()
    {
        return strtolower($this->distanceUnit ?? setting('distance_unit'));
    }
}