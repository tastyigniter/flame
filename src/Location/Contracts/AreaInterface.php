<?php namespace Igniter\Flame\Location\Contracts;

use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Geolite\Contracts\LocationInterface;

interface AreaInterface
{
    public function getLocationId();

    public function checkBoundary($position);

    public function pointInVertices(CoordinatesInterface $coordinate);

    public function pointInCircle(CoordinatesInterface $coordinate);

    public function matchAddressComponents(LocationInterface $position);
}