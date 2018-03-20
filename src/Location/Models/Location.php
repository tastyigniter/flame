<?php

namespace Igniter\Flame\Location\Models;

use Main\Models\Image_tool_model;
use DB;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Location\GeoPosition;
use Igniter\Flame\Location\Traits\HasDeliveryAreas;
use Igniter\Flame\Location\Traits\HasWorkingHours;

class Location extends Model
{
    use HasWorkingHours;
    use HasDeliveryAreas;

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
            'working_hours'  => ['Admin\Models\Working_hours_model'],
            'delivery_areas' => ['Admin\Models\Location_areas_model'],
        ],
    ];

    public $casts = [
        'options' => 'serialize',
    ];

    /**
     * @var array The location working hours.
     */
    protected $workingSchedule;

    protected $coveredArea;

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
            'address_1'    => $row['location_address_1'],
            'address_2'    => $row['location_address_2'],
            'city'         => $row['location_city'],
            'state'        => $row['location_state'],
            'postcode'     => $row['location_postcode'],
            'location_lat' => $row['location_lat'],
            'location_lng' => $row['location_lng'],
            'country_id'   => $row['location_country_id'],
            'country'      => isset($row['country_name']) ? $row['country_name'] : null,
            'iso_code_2'   => isset($row['iso_code_2']) ? $row['iso_code_2'] : null,
            'iso_code_3'   => isset($row['iso_code_3']) ? $row['iso_code_3'] : null,
            'format'       => isset($row['format']) ? $row['format'] : null,
        ];

        return $address_data;
    }

    public function getThumb($options = [])
    {
        return Image_tool_model::resize($this->location_image, $options);
    }

    public function getGallery()
    {
        return array_get($this->options, 'gallery', []);
    }

    public function getReservationInterval()
    {
        return $this->reservation_time_interval;
    }

    public function getReservationStayTime()
    {
        return $this->reservation_stay_time;
    }

    public function getOrderTimeInterval($orderType)
    {
        return $orderType == 'delivery' ? $this->deliveryMinutes() : $this->collectionMinutes();
    }

    public function deliveryMinutes()
    {
        return $this->delivery_time;
    }

    public function collectionMinutes()
    {
        return $this->collection_time;
    }

    public function lastOrderMinutes()
    {
        return $this->last_order_time;
    }

    public function hasGallery()
    {
        $gallery = $this->getGallery();

        return (isset($gallery['images']) AND count(array_filter($gallery['images'])));
    }

    public function hasDelivery()
    {
        return $this->offer_delivery == 1;
    }

    public function hasCollection()
    {
        return $this->offer_collection == 1;
    }

    public function hasFutureOrder()
    {
        return (bool)array_get($this->options, 'future_orders', FALSE);
    }

    public function futureOrderDays($orderType = null)
    {
        $orderType = $orderType ?: static::DELIVERY;

        return array_get($this->options, "future_order_days.{$orderType}", 0);
    }

    public function availableOrderTypes()
    {
        return [1 => static::DELIVERY, 2 => static::COLLECTION];
    }

    public function calculateDistance(GeoPosition $position)
    {
        if (!is_float($position->latitude) OR !is_float($position->longitude)
            OR !is_float($this->location_lat) OR !is_float($this->location_lng))
            return null;

        $degrees = sin(deg2rad($position->latitude)) * sin(deg2rad($this->location_lat)) +
            cos(deg2rad($position->latitude)) * cos(deg2rad($this->location_lat)) *
            cos(deg2rad($position->longitude - $this->location_lng));

        $distance = rad2deg(acos($degrees));

        return $this->getDistanceUnit() == 'km' ? ($distance * static::KM_UNIT) : ($distance * static::M_UNIT);
    }

    //
    // Scopes
    //

    public function scopeSelectDistance($query, $latitude = null, $longitude = null)
    {
        if (setting('distance_unit') === 'km') {
            $sql = "( 6371 * acos( cos( radians(?) ) * cos( radians( location_lat ) ) *";
        }
        else {
            $sql = "( 3959 * acos( cos( radians(?) ) * cos( radians( location_lat ) ) *";
        }

        $sql .= " cos( radians( location_lng ) - radians(?) ) + sin( radians(?) ) *";
        $sql .= " sin( radians( location_lat ) ) ) ) AS distance";

        $query->selectRaw(DB::raw($sql), [$latitude, $longitude, $latitude])
            // ->having('distance', '>', 100)
              ->orderBy('distance', 'asc');

        return $query;
    }
}