<?php

namespace Igniter\Flame\Geolite;

use Igniter\Flame\Geolite\Contracts\LocationInterface;

class AddressMatch
{
    public function __construct(array $components)
    {
        $this->components = $components;
    }

    public function matches(LocationInterface $position)
    {
        $matched = collect($this->components)->filter(function ($component) use ($position) {
            foreach ($component as $item) {
                $type = array_get($item, 'type');
                $value = array_get($item, 'value');

                if ($this->matchComponentValue($position, $type, $value))
                    return TRUE;
            }

            return FALSE;
        });

        return $matched->isNotEmpty();
    }

    protected function matchComponentValue(LocationInterface $position, $type, $value)
    {
        if (!is_string($value) AND !is_numeric($value))
            return FALSE;

        switch ($type) {
            case 'street':
                return $this->evalComponentValue(
                    $value, $position->getStreetName()
                );
            case 'locality':
                return $this->evalComponentValue(
                    $value, $position->getLocality()
                );
            case 'admin_level_2':
            case 'admin_level_1':
                $adminLevel = $position->getAdminLevels()->get((int)substr($type, -1));

                return $this->evalComponentValue(
                    $value, $adminLevel ? $adminLevel->getName() : null
                );
            case 'postal_code':
                return $this->evalComponentValue(
                    $value, $position->getPostalCode()
                );
        }
    }

    protected function evalComponentValue($left, $right)
    {
        if (empty($right))
            return $right;

        if (@preg_match($left, '') !== FALSE)
            return preg_match($left, $right) > 0;

        return strtolower($left) === strtolower($right);
    }
}