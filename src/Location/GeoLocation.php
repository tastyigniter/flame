<?php

namespace Igniter\Flame\Location;

/**
 * Geo Address definition
 *
 * @package Main
 */
class GeoLocation
{
    /**
     * @var string Geo position search query.
     */
    public $query;

    public $latitude;

    public $longitude;

    public $address;

    public $formattedAddress;

    public $status;

    public $data;

    /**
     * Constructor.
     *
     * @param string $query
     */
    public function __construct($query = null, $config = [])
    {
        $this->query = $query;
        $this->config = $this->evalConfig($config);
    }

    public function evalConfig(array $config)
    {
        if (isset($config['latitude']))
            $this->latitude = $config['latitude'];

        if (isset($config['longitude']))
            $this->longitude = $config['longitude'];

        if (isset($config['address']))
            $this->address = $config['address'];

        if (isset($config['status']))
            $this->status = $config['status'];

        return $config;
    }

    public function getStatus()
    {
        if (is_null($this->query) OR is_null($this->status)) {
            return null;
        }

        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getFormattedAddress()
    {
        return $this->address;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}