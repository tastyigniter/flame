<?php

namespace Igniter\Flame\Location\Traits;

use Geocoder;
use Igniter\Flame\Location\Contracts\AreaInterface;
use Model;

trait HasDeliveryAreas
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection
     */
    protected $deliveryAreas;

    public static function bootHasDeliveryAreas()
    {
        static::saving(function (Model $model) {
            $model->geocodeAddressOnSave();
        });
    }

    protected function geocodeAddressOnSave()
    {
        if (!array_get($this->options, 'auto_lat_lng', TRUE))
            return;

        if (!empty($this->location_lat) AND !empty($this->location_lng))
            return;

        $address = format_address($this->getAddress(), FALSE);

        $geoLocation = Geocoder::geocode($address)->first();
        if ($geoLocation AND $geoLocation->hasCoordinates()) {
            $this->location_lat = $geoLocation->getCoordinates()->getLatitude();
            $this->location_lng = $geoLocation->getCoordinates()->getLongitude();
        }
    }

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
    public function searchOrDefaultDeliveryArea($coordinates)
    {
        if ($area = $this->searchDeliveryArea($coordinates))
            return $area;

        return $this->delivery_areas->where('is_default', 1)->first();
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