<?php

namespace Igniter\Flame\Geolite\Formatter;

use Igniter\Flame\Geolite\Model\AdminLevelCollection;
use Igniter\Flame\Geolite\Model\Location;

class StringFormatter
{
    const STREET_NUMBER = '%n';
    const STREET_NAME = '%S';
    const LOCALITY = '%L';
    const POSTAL_CODE = '%z';
    const SUB_LOCALITY = '%D';
    const ADMIN_LEVEL = '%A';
    const ADMIN_LEVEL_CODE = '%a';
    const COUNTRY_NAME = '%C';
    const COUNTRY_CODE = '%c';
    const TIMEZONE = '%T';

    /**
     * Transform a `Location` instance into a string representation.
     *
     * @param Location $location
     * @param string $format
     *
     * @return string
     */
    public function format(Location $location, string $format): string
    {
        $replace = [
            self::STREET_NUMBER => $location->getStreetNumber(),
            self::STREET_NAME => $location->getStreetName(),
            self::LOCALITY => $location->getLocality(),
            self::POSTAL_CODE => $location->getPostalCode(),
            self::SUB_LOCALITY => $location->getSubLocality(),
            self::COUNTRY_NAME => $location->getCountryName(),
            self::COUNTRY_CODE => $location->getCountryCode(),
            self::TIMEZONE => $location->getTimezone(),
        ];

        for ($level = 1; $level <= AdminLevelCollection::MAX_LEVEL_DEPTH; ++$level) {
            $adminLevel = $location->getAdminLevels()[$level] ?? null;
            $replace[self::ADMIN_LEVEL.$level] = $adminLevel ? $adminLevel->getName() : null;
            $replace[self::ADMIN_LEVEL_CODE.$level] = $adminLevel ? $adminLevel->getCode() : null;
        }

        return strtr($format, $replace);
    }
}