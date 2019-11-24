<?php namespace Igniter\Flame\Geolite\Model;

use Igniter\Flame\Geolite\Contracts;
use Igniter\Flame\Geolite\Formatter\StringFormatter;
use InvalidArgumentException;

class Location implements Contracts\LocationInterface
{
    /**
     * @var Coordinates|null
     */
    protected $coordinates;

    /**
     * @var Bounds|null
     */
    protected $bounds;

    /**
     * @var string|int|null
     */
    protected $streetNumber;

    /**
     * @var string|null
     */
    protected $streetName;

    /**
     * @var string|null
     */
    protected $subLocality;

    /**
     * @var string|null
     */
    protected $locality;

    /**
     * @var string|null
     */
    protected $postalCode;

    /**
     * @var AdminLevelCollection
     */
    protected $adminLevels;

    /**
     * @var string|null
     */
    protected $countryName;

    /**
     * @var string|null
     */
    protected $countryCode;

    /**
     * @var string|null
     */
    protected $formattedAddress;

    /**
     * @var string|null
     */
    protected $timezone;

    /**
     * @var string
     */
    protected $providedBy;

    /**
     * @param string $providedBy
     * @param array $data
     */
    public function __construct(string $providedBy, array $data = [])
    {
        $this->providedBy = $providedBy;
        $this->fillFromData($data);
    }

    /**
     * Create an Address with an array.
     *
     * @param array $data
     *
     * @return static
     */
    public static function createFromArray(array $data)
    {
        return new static(array_get($data, 'providedBy', 'n/a'), $data);
    }

    public function isValid()
    {
        return $this->hasCoordinates();
    }

    public function format(string $mapping = '%n %S %L %z')
    {
        return (new StringFormatter)->format($this, $mapping);
    }

    /**
     * @return null|string
     */
    public function getFormattedAddress()
    {
        return $this->formattedAddress;
    }

    /**
     * @param string|null $formattedAddress
     *
     * @return self
     */
    public function withFormattedAddress(string $formattedAddress = null)
    {
        $new = clone $this;
        $new->formattedAddress = $formattedAddress;

        return $new;
    }

    /**
     * @param float $south
     * @param float $west
     * @param float $north
     * @param float $east
     *
     * @return self
     */
    public function setBounds($south, $west, $north, $east)
    {
        try {
            $this->bounds = new Bounds($south, $west, $north, $east);
        }
        catch (InvalidArgumentException $e) {
            $this->bounds = null;
        }

        return $this;
    }

    /**
     * @param float $latitude
     * @param float $longitude
     *
     * @return self
     */
    public function setCoordinates($latitude, $longitude)
    {
        try {
            $this->coordinates = new Coordinates($latitude, $longitude);
        }
        catch (InvalidArgumentException $e) {
            $this->coordinates = null;
        }

        return $this;
    }

    /**
     * @param int $level
     * @param string $name
     * @param string|null $code
     *
     * @return self
     */
    public function addAdminLevel(int $level, string $name, string $code = null)
    {
        $this->adminLevels->put($level, new AdminLevel($level, $name, $code));

        return $this;
    }

    /**
     * @param null|string $streetNumber
     *
     * @return self
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;

        return $this;
    }

    /**
     * @param null|string $streetName
     *
     * @return self
     */
    public function setStreetName($streetName)
    {
        $this->streetName = $streetName;

        return $this;
    }

    /**
     * @param null|string $locality
     *
     * @return self
     */
    public function setLocality($locality)
    {
        $this->locality = $locality;

        return $this;
    }

    /**
     * @param null|string $postalCode
     *
     * @return self
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * @param null|string $subLocality
     *
     * @return self
     */
    public function setSubLocality($subLocality)
    {
        $this->subLocality = $subLocality;

        return $this;
    }

    /**
     * @param array $adminLevels
     *
     * @return self
     */
    public function setAdminLevels($adminLevels)
    {
        $this->adminLevels = $adminLevels;

        return $this;
    }

    /**
     * @param null|string $countryName
     *
     * @return self
     */
    public function setCountryName($countryName)
    {
        $this->countryName = $countryName;

        return $this;
    }

    /**
     * @param null|string $countryCode
     *
     * @return self
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * @param null|string $timezone
     *
     * @return self
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function setValue(string $name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getValue(string $name, $default = null)
    {
        if ($this->hasValue($name)) {
            return $this->data[$name];
        }

        return $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasValue(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * @return string
     */
    public function getProvidedBy(): string
    {
        return $this->providedBy;
    }

    /**
     * {@inheritdoc}
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * {@inheritdoc}
     */
    public function getBounds()
    {
        return $this->bounds;
    }

    /**
     * {@inheritdoc}
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function getStreetName()
    {
        return $this->streetName;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocality()
    {
        return $this->locality;
    }

    /**
     * {@inheritdoc}
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubLocality()
    {
        return $this->subLocality;
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminLevels(): AdminLevelCollection
    {
        return $this->adminLevels;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountryName()
    {
        return $this->countryName;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    public function hasCoordinates()
    {
        if (!$coordinates = $this->getCoordinates())
            return FALSE;

        list($latitude, $longitude) = $coordinates->toArray();

        return !empty($latitude) AND !empty($longitude);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        $adminLevels = [];
        foreach ($this->adminLevels as $adminLevel) {
            $level = $adminLevel->getLevel();
            $adminLevels[$level] = [
                'name' => $adminLevel->getName(),
                'code' => $adminLevel->getCode(),
                'level' => $level,
            ];
        }

        $coordinates = $this->getCoordinates();

        $noBounds = [
            'south' => null, 'west' => null,
            'north' => null, 'east' => null,
        ];

        return [
            'providedBy' => $this->providedBy,
            'latitude' => $coordinates ? $coordinates->getLatitude() : null,
            'longitude' => $coordinates ? $coordinates->getLongitude() : null,
            'bounds' => $this->bounds ? $this->bounds->toArray() : $noBounds,
            'streetNumber' => $this->streetNumber,
            'streetName' => $this->streetName,
            'postalCode' => $this->postalCode,
            'locality' => $this->locality,
            'subLocality' => $this->subLocality,
            'adminLevels' => $adminLevels,
            'countryName' => $this->getCountryName(),
            'countryCode' => $this->getCountryCode(),
            'timezone' => $this->timezone,
        ];
    }

    protected function fillFromData(array $data)
    {
        $data = $this->mergeWithDefaults($data);

        $this->adminLevels = $this->makeAdminLevels($data);
        $this->coordinates = $this->createCoordinates($data);
        $this->bounds = $this->createBounds($data);
        $this->streetNumber = $data['streetNumber'];
        $this->streetName = $data['streetName'];
        $this->postalCode = $data['postalCode'];
        $this->locality = $data['locality'];
        $this->subLocality = $data['subLocality'];
        $this->countryName = $data['countryName'];
        $this->countryCode = $data['countryCode'];
        $this->timezone = $data['timezone'];
    }

    /**
     * @param $data
     * @return Coordinates|null
     */
    protected function createCoordinates($data)
    {
        if (
            !$latitude = array_get($data, 'latitude')
            OR !$longitude = array_get($data, 'longitude')
        ) {
            return null;
        }

        return new Coordinates($latitude, $longitude);
    }

    /**
     * @param $data
     * @return Bounds|null
     */
    protected function createBounds($data)
    {
        if (!$south = array_get($data, 'bounds.south')
            OR !$west = array_get($data, 'bounds.west')
            OR !$north = array_get($data, 'bounds.north')
            OR !$east = array_get($data, 'bounds.east')
        ) return null;

        return new Bounds($south, $west, $north, $east);
    }

    protected function mergeWithDefaults(array $data): array
    {
        $defaults = [
            'latitude' => null,
            'longitude' => null,
            'bounds' => [
                'south' => null,
                'west' => null,
                'north' => null,
                'east' => null,
            ],
            'streetNumber' => null,
            'streetName' => null,
            'locality' => null,
            'postalCode' => null,
            'subLocality' => null,
            'adminLevels' => [],
            'countryName' => null,
            'countryCode' => null,
            'timezone' => null,
        ];

        return array_merge($defaults, $data);
    }

    protected function makeAdminLevels(array $data)
    {
        $adminLevels = [];
        foreach ($data['adminLevels'] as $adminLevel) {
            if (empty($adminLevel['level']))
                continue;

            $name = $adminLevel['name'] ?? $adminLevel['code'] ?? null;
            if (empty($name))
                continue;

            $adminLevels[] = new AdminLevel($adminLevel['level'], $name, $adminLevel['code'] ?? null);
        }

        return new AdminLevelCollection($adminLevels);
    }
}