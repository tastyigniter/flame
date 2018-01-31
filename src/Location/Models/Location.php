<?php

namespace Igniter\Flame\Location\Models;

use Admin\Models\Image_tool_model;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Location\GeoLocation;
use Igniter\Flame\Location\WorkingSchedule;

class Location extends Model
{
    const CLOSED = 'closed';

    const OPEN = 'open';

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

    protected $deliveryAreas;

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
        return isset($this->options['gallery']) ? $this->options['gallery'] : [];
    }

    public function getReservationInterval()
    {
        return $this->reservation_time_interval;
    }

    public function deliveryTime()
    {
        return $this->delivery_time;
    }

    public function collectionTime()
    {
        return $this->collection_time;
    }

    public function lastOrderTime()
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
        return $this->future_orders ? TRUE : FALSE;
    }

    public function futureOrderDays($orderType = null)
    {
        $orderType = $orderType ?: static::DELIVERY;

        return isset($this->future_order_days[$orderType])
            ? $this->future_order_days[$orderType] : 5;
    }

    public function getDeliveryChargeSummary()
    {
        return ''; // $this->area()->getNearestAreaChargeSummary();
    }

//    public function workingSchedule()
//    {
//        if (!$this->workingSchedule)
//            $this->workingSchedule = $this->createWorkingSchedule();
//
//        return $this->workingSchedule;
//    }

    public function deliveryAreas()
    {
        if (!$this->deliveryAreas)
            $this->deliveryAreas = $this->delivery_areas()->get();

        return $this->deliveryAreas;
    }

    /**
     * @return mixed
     */
    protected function createWorkingSchedule()
    {
        $workingHours = $this->working_hours()->get();

        return new WorkingSchedule($this, $workingHours);
    }

    /**
     * @return mixed
     */
    protected function createDeliveryAreas()
    {
        $deliveryAreas = $this->delivery_areas()->get();

        return new WorkingSchedule($this, $deliveryAreas);
    }

    public function getWorkingHourByDay($day)
    {

    }

    public function isOpened()
    {
        return $this->workingSchedule()->isOpened();
    }

    public function isClosed()
    {
        return $this->workingSchedule()->isClosed();
    }

    public function checkWorkingStatus()
    {
        return false;
    }

    public function checkDistance()
    {
        return false;
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

    //
    // Helpers
    //

    /**
     * Find a location working hour by day of the week
     *
     * @param int $location_id
     * @param string $day
     *
     * @return array
     */
    public function getOpeningHourByDay($location_id = null, $day = null)
    {
        $weekdays = ['Monday' => 0, 'Tuesday' => 1, 'Wednesday' => 2, 'Thursday' => 3, 'Friday' => 4, 'Saturday' => 5, 'Sunday' => 6];
        $day = (!isset($weekdays[$day])) ? date('l', strtotime($day)) : $day;
        $hour = ['open' => '00:00:00', 'close' => '00:00:00', 'status' => '0'];

        $working_hours = Working_hours_model::where('location_id', $location_id)
                                            ->where('weekday', $weekdays[$day])->first();

        if ($working_hours) {
            $hour['open'] = $row['opening_time'];
            $hour['close'] = $row['closing_time'];
            $hour['status'] = $row['status'];
        }

        return $hour;
    }

    /**
     * Find the nearest location to latitude and longitude
     *
     * @param  float $latitude
     * @param  float $longitude
     *
     * @return array|bool an array of the nearest location, or null on failure
     */
    public function findNearestByCoordinates($latitude, $longitude)
    {
        if (is_null($latitude) OR is_null($longitude))
            return null;

        $query = $this->newQuery()
//                       ->with('delivery_areas')
                      ->select(['location_id', 'location_radius'])
                      ->selectDistance($lat, $lng)
                      ->isEnabled()
//                           ->having('distance', '>', 100)
                      ->orderBy('distance', 'asc');
//        if ($result) {
//            $searchRadius = ($result->location_radius > 0)
//                ? $result->location_radius : (int)setting('search_radius');
//
//            if ($result->distance > $searchRadius) {
//                dd($result->distance, $result->location_radius, $result->getKey(), $searchRadius);
//
//                return null;
//            }
//        }

        return $query->first();
    }

    public function checkDeliveryCoverage(GeoLocation $geoLocation)
    {
        return true;
//        $userPosition = $this->area()->geocode()->geocodePosition($geoLocation);
//
//        return $this->area()->setUserPosition($userPosition)->checkCoverage();
    }
}