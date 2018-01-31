<?php

namespace Igniter\Flame\Location;

use Cookie;
use Session;

/**
 * Location Manager Class
 * @package        Igniter\Flame\Location\Manager.php
 */
class Manager
{
    protected $sessionStore;

    protected $location;

    protected $defaultLocation;

    protected $singleMode;

    protected $locationModel = 'Igniter\Flame\Location\Models\Location';

    protected $sessionKey = 'local_info';

//    public function __construct($sessionStore)
//    {
//        $this->sessionStore = $sessionStore;
//        $this->defaultLocation = params('default_location_id');
//        $this->singleMode = setting('site_location_mode') != 'multi';
//        self::$orderTypes = [1 => self::DELIVERY, 2 => self::COLLECTION];
//    }

    /**
     * Gets the geocoder.
     * @return \Igniter\Flame\Location\Geocoder
     */
//    public function getGeocoder()
//    {
//        return $this->geocoder ?: App::make('geocoder');
//    }

    /**
     * Sets the geocoder.
     *
     * @param  string $geocoder
     *
     * @return $this
     */
//    public function setGeocoder($geocoder)
//    {
//        $this->geocoder = $geocoder;
//
//        return $this;
//    }

    //
    // Location
    //

    /**
     * @return mixed
     */
//    public function getDefaultLocation()
//    {
//        return $this->defaultLocation;
//    }

    /**
     * @param mixed $defaultLocation
     */
//    public function setDefaultLocation($defaultLocation)
//    {
//        $this->defaultLocation = $defaultLocation;
//    }

    /**
     * @return mixed
     */
//    public function isSingleMode()
//    {
//        return $this->singleMode;
//    }

    /**
     * Creates a new instance of the location model
     * @return \Igniter\Flame\Location\Models\Location
     */
    public function createLocationModel()
    {
        $class = '\\'.ltrim($this->locationModel, '\\');
        $model = new $class();

        return $model;
    }

    /**
     * Prepares a query derived from the location model.
     */
    protected function createLocationModelQuery()
    {
        $model = $this->createLocationModel();
        $query = $model->newQuery();
        $this->extendLocationQuery($query);

        return $query;
    }

    /**
     * Extend the query used for finding the location.
     *
     * @param \Igniter\Flame\Database\Builder $query
     *
     * @return void
     */
    public function extendLocationQuery($query)
    {
    }

    /**
     * Retrieve a location by their unique identifier.
     *
     * @param  mixed $identifier
     *
     * @return \Igniter\Flame\Location\LocationInterface|null
     */
    public function getById($identifier)
    {
        $query = $this->createLocationModelQuery();
        $location = $query->find($identifier);

        return $location ?: null;
    }

    /**
     * Retrieve a location by their unique slug.
     *
     * @param  string $slug
     *
     * @return \Igniter\Flame\Location\LocationInterface|null
     */
    public function getBySlug($slug)
    {
        $model = $this->createLocationModel();
        $query = $this->createLocationModelQuery();
        $location = $query->where($model->getSlugKeyName(), $slug);

        return $location ?: null;
    }

    /**
     * Update the user location in storage.
     *
     * @param  \Igniter\Flame\Location\LocationInterface $location
     * @param \Igniter\Flame\Location\GeoPosition $userPosition
     *
     * @return void
     */
    public function updateLocationLocation(LocationInterface $location, GeoPosition $userPosition)
    {

    }

    /**
     * Validate a location against the given coordinates.
     *
     * @param  \Igniter\Flame\Location\LocationInterface $location
     * @param  array $credentials
     *
     * @return bool
     */
    public function validateCoordinates(LocationInterface $location, array $credentials)
    {
    }

//    public function check()
//    {
//        return (!is_null($this->location()));
//    }

//    public function location()
//    {
//        if (!is_null($this->location))
//            return $this->location;
//
//        $id = $this->getSession('id', $this->defaultLocation);
//
//        $location = null;
//        if (!is_null($id)) {
//            $model = $this->createLocationModel();
//            $location = $this->createLocationModelQuery()
//                             ->where($model->getKeyName(), $id)->first();
//        }
//
//        return $this->location = $location;
//    }

    /**
     * Sets the location
     *
     * @param $location
     */
//    public function setLocation($location)
//    {
//        $this->location = $location;
//    }
//
//    public function getLocation()
//    {
//        return $this->location;
//    }

    //
    // Session
    //

    /**
     * Retrieves key/value pair from session data.
     *
     * @param string $key Unique key for the data store.
     * @param string $default A default value to use when value is not found.
     *
     * @return mixed
     */
    public function getSession($key = null, $default = null)
    {
        $sessionData = Session::get($this->sessionKey);

        return is_null($key) ? $sessionData : array_get($sessionData, $key, $default);
    }

    public function putSession($key, $value)
    {
        $sessionData = $this->getSession();
        $sessionData[$key] = $value;

        Session::put($this->sessionKey, $sessionData);
    }

    public function hasSession($key)
    {
        $sessionData = $this->getSession();

        return array_key_exists($key, $sessionData);
    }

    public function forgetSession($key)
    {
        $sessionData = $this->getSession();
        unset($sessionData[$key]);

        Session::put($this->sessionKey, $sessionData);
    }

    public function resetSession()
    {
        Session::forget($this->sessionKey);
    }
}