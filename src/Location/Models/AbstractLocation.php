<?php

namespace Igniter\Flame\Location\Models;

use DB;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Location\Contracts\LocationInterface;

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
            'working_hours' => ['Admin\Models\Working_hours_model'],
            'delivery_areas' => ['Admin\Models\Location_areas_model'],
        ],
    ];

    public $casts = [
        'options' => 'serialize',
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
        $row = $this;

        $address_data = [
            'address_1' => $row['location_address_1'],
            'address_2' => $row['location_address_2'],
            'city' => $row['location_city'],
            'state' => $row['location_state'],
            'postcode' => $row['location_postcode'],
            'location_lat' => $row['location_lat'],
            'location_lng' => $row['location_lng'],
            'country_id' => $row['location_country_id'],
            'country' => isset($row['country_name']) ? $row['country_name'] : null,
            'iso_code_2' => isset($row['iso_code_2']) ? $row['iso_code_2'] : null,
            'iso_code_3' => isset($row['iso_code_3']) ? $row['iso_code_3'] : null,
            'format' => isset($row['format']) ? $row['format'] : null,
        ];

        return $address_data;
    }

    public function setOption($key, $value)
    {
        $options = $this->options;
        array_set($options, $key, $value);
        $this->options = $options;
    }

    public function getOption($key = null, $default = null)
    {
        return array_get($this->options, $key, $default);
    }

    public function getReservationInterval()
    {
        return (int)$this->getOption('reservation_time_interval', 0);
    }

    public function getReservationLeadTime()
    {
        return (int)$this->getOption('reservation_lead_time', 0);
    }

    public function getOrderTimeInterval($orderType)
    {
        return (int)$this->getOption($orderType.'_time_interval', 15);
    }

    public function getOrderLeadTime($orderType)
    {
        return (int)$this->getOption($orderType.'_lead_time', 15);
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
        return $this->getOption('offer_delivery') == 1;
    }

    public function hasCollection()
    {
        return $this->getOption('offer_collection') == 1;
    }

    public function hasFutureOrder($orderType = null)
    {
        $orderType = $orderType ?: static::DELIVERY;

        return (bool)$this->getOption("future_orders.{$orderType}_days", 0);
    }

    public function futureOrderDays($orderType = null)
    {
        $orderType = $orderType ?: static::DELIVERY;

        return (int)$this->getOption("future_orders.{$orderType}_days", 0);
    }

    public function availableOrderTypes()
    {
        $orderTypes = [];
        if ($this->hasDelivery())
            $orderTypes[1] = static::DELIVERY;

        if ($this->hasCollection())
            $orderTypes[2] = static::COLLECTION;

        return $orderTypes;
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
