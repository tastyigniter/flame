<?php

namespace Igniter\Admin\Traits;

use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Location\OrderTypes;

trait LocationHelpers
{
    public function getName()
    {
        return $this->location_name;
    }

    public function getEmail()
    {
        return strtolower($this->location_email);
    }

    public function getTelephone()
    {
        return $this->location_telephone;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getAddress()
    {
        $country = optional($this->country);

        return [
            'address_1' => $this->location_address_1,
            'address_2' => $this->location_address_2,
            'city' => $this->location_city,
            'state' => $this->location_state,
            'postcode' => $this->location_postcode,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'country_id' => $this->location_country_id,
            'country' => $country->country_name,
            'iso_code_2' => $country->iso_code_2,
            'iso_code_3' => $country->iso_code_3,
            'format' => $country->format,
        ];
    }

    public function getReservationInterval()
    {
        return (int)$this->getOption('reservation_time_interval', 0);
    }

    public function getReservationLeadTime()
    {
        return (int)$this->getOption('reservation_stay_time', 0);
    }

    public function getReservationStayTime()
    {
        return (int)$this->getOption('reservation_stay_time', 0);
    }

    public function getMinReservationAdvanceTime()
    {
        return (int)$this->getOption('min_reservation_advance_time', 2);
    }

    public function getMaxReservationAdvanceTime()
    {
        return (int)$this->getOption('max_reservation_advance_time', 30);
    }

    public function getReservationCancellationTimeout()
    {
        return (int)$this->getOption('reservation_cancellation_timeout', 0);
    }

    public function getOrderTimeInterval($orderType)
    {
        return (int)$this->getOption($orderType.'_time_interval', 15);
    }

    public function getOrderLeadTime($orderType)
    {
        return (int)$this->getOption($orderType.'_lead_time', 15);
    }

    public function getOrderTimeRestriction($orderType)
    {
        return (int)$this->getOption($orderType.'_time_restriction', 0);
    }

    public function getOrderCancellationTimeout($orderType)
    {
        return (int)$this->getOption($orderType.'_cancellation_timeout', 0);
    }

    public function deliveryMinutes()
    {
        return (int)$this->getOption('delivery_lead_time', 15);
    }

    public function collectionMinutes()
    {
        return (int)$this->getOption('collection_lead_time', 15);
    }

    public function hasDelivery()
    {
        return $this->getOption('offer_delivery', 1) == 1;
    }

    public function hasCollection()
    {
        return $this->getOption('offer_collection', 1) == 1;
    }

    public function hasFutureOrder($orderType = null)
    {
        $orderType = $orderType ?: static::DELIVERY;

        return (bool)$this->getOption("future_orders.enable_{$orderType}", 0);
    }

    public function futureOrderDays($orderType = null)
    {
        $orderType = $orderType ?: static::DELIVERY;

        return (int)$this->getOption("future_orders.{$orderType}_days", 0);
    }

    public function availableOrderTypes()
    {
        return resolve(OrderTypes::class)->makeOrderTypes($this);
    }

    public static function getOrderTypeOptions()
    {
        return collect(resolve(OrderTypes::class)->listOrderTypes())->pluck('name', 'code');
    }

    public function calculateDistance(CoordinatesInterface $position)
    {
        $distance = $this->makeDistance();

        $distance->setFrom($position);
        $distance->setTo($this->getCoordinates());
        $distance->in($this->getDistanceUnit());

        return $distance->haversine();
    }

    /**
     * @return \Igniter\Flame\Geolite\Model\Coordinates
     */
    public function getCoordinates()
    {
        return app('geolite')->coordinates($this->location_lat, $this->location_lng);
    }

    /**
     * @return \Igniter\Flame\Geolite\Contracts\DistanceInterface
     */
    public function makeDistance()
    {
        return app('geolite')->distance();
    }
}
