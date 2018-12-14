<?php namespace Igniter\Flame\Geolite\Contracts;

use Igniter\Flame\Geolite\Model\AdminLevelCollection;

interface LocationInterface
{
    /**
     * The name of the provider that created this Location.
     *
     * @return string
     */
    public function getProvidedBy();

    /**
     * Will always return the coordinates value object.
     *
     * @return \Igniter\Flame\Geolite\Model\Coordinates|null
     */
    public function getCoordinates();

    /**
     * Returns the bounds value object.
     *
     * @return \Igniter\Flame\Geolite\Model\Bounds|null
     */
    public function getBounds();

    /**
     * Returns the street number value.
     *
     * @return string|int|null
     */
    public function getStreetNumber();

    /**
     * Returns the street name value.
     *
     * @return string|null
     */
    public function getStreetName();

    /**
     * Returns the city or locality value.
     *
     * @return string|null
     */
    public function getLocality();

    /**
     * Returns the postal code or zipcode value.
     *
     * @return string|null
     */
    public function getPostalCode();

    /**
     * Returns the locality district, or
     * sublocality, or neighborhood.
     *
     * @return string|null
     */
    public function getSubLocality();

    /**
     * Returns the administrative levels.
     *
     * This method MUST NOT return null.
     *
     * @return \Igniter\Flame\Geolite\Model\AdminLevelCollection|null
     */
    public function getAdminLevels(): AdminLevelCollection;

    /**
     * Returns the country name.
     *
     * @return string|null
     */
    public function getCountryName();

    /**
     * Returns the country code.
     *
     * @return string|null
     */
    public function getCountryCode();

    /**
     * Returns the timezone for the Location. The timezone MUST be in the list of supported timezones.
     *
     * {@link http://php.net/manual/en/timezones.php}
     *
     * @return string|null
     */
    public function getTimezone();

    /**
     * Returns an array with data indexed by name.
     *
     * @return array
     */
    public function toArray(): array;
}