<?php

namespace Igniter\Flame\Location\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Location\Contracts\LocationInterface;
use Igniter\Flame\Location\OrderTypes;
use Illuminate\Support\Facades\DB;

class AbstractLocation extends Model implements LocationInterface
{
    const KM_UNIT = 111.13384;

    const M_UNIT = 69.05482;

    const OPENING = 'opening';

    const DELIVERY = 'delivery';

    const COLLECTION = 'collection';

    /**
     * @var string The database table name
     */
    protected $table = 'locations';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'location_id';

    public $relation = [
        'hasMany' => [
            'working_hours' => [\Admin\Models\Working_hours_model::class],
            'delivery_areas' => [\Admin\Models\Location_areas_model::class],
        ],
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function getName()
    {
        return $this->attributes['location_name'];
    }

    public function getEmail()
    {
        return strtolower($this->attributes['location_email']);
    }

    public function getTelephone()
    {
        return $this->attributes['location_telephone'];
    }

    public function getDescription()
    {
        return $this->attributes['description'];
    }

    public function getAddress()
    {
        return [
            'address_1' => $this->location_address_1,
            'address_2' => $this->location_address_2,
            'city' => $this->location_city,
            'state' => $this->location_state,
            'postcode' => $this->location_postcode,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'country_id' => $this->location_country_id,
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

    public function getMinimumOrderTotal($orderType)
    {
        return $this->getOption($orderType.'_min_order_amount', 0);
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

    public function minimumFutureOrderDays($orderType = null)
    {
        $orderType = $orderType ?: static::DELIVERY;

        return (int)$this->getOption("future_orders.min_{$orderType}_days", 0);
    }

    public function availableOrderTypes()
    {
        return OrderTypes::instance()->makeOrderTypes($this);
    }

    public static function getOrderTypeOptions()
    {
        return collect(OrderTypes::instance()->listOrderTypes())->pluck('name', 'code');
    }

    public function calculateDistance(CoordinatesInterface $position)
    {
        $distance = $this->makeDistance();

        $distance->setFrom($this->getCoordinates());
        $distance->setTo($position);
        $distance->in($this->getDistanceUnit());

        return app('geocoder')->distance($distance);
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

    //
    // Scopes
    //

    public function scopeSelectDistance($query, $latitude = null, $longitude = null)
    {
        if (setting('distance_unit') === 'km') {
            $sql = '( 6371 * acos( cos( radians(?) ) * cos( radians( location_lat ) ) *';
        }
        else {
            $sql = '( 3959 * acos( cos( radians(?) ) * cos( radians( location_lat ) ) *';
        }

        $sql .= ' cos( radians( location_lng ) - radians(?) ) + sin( radians(?) ) *';
        $sql .= ' sin( radians( location_lat ) ) ) ) AS distance';

        $query->selectRaw(DB::raw($sql), [$latitude, $longitude, $latitude]);

        return $query;
    }
}
