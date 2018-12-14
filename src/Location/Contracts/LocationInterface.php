<?php namespace Igniter\Flame\Location\Contracts;

use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;

interface LocationInterface
{
    public function getName();

    public function getEmail();

    public function getTelephone();

    public function getDescription();

    public function getAddress();

    public function hasDelivery();

    public function hasCollection();

    public function calculateDistance(CoordinatesInterface $position);
}