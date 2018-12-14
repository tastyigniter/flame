<?php namespace Igniter\Flame\Location\Contracts;

use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;

interface AreaInterface
{
    public function getLocationId();

    public function deliveryAmount($cartTotal);

    public function minimumOrderTotal($cartTotal);

    public function listConditions();

    public function checkBoundary(CoordinatesInterface $coordinate);

    public function pointInVertices(CoordinatesInterface $coordinate);

    public function pointInCircle(CoordinatesInterface $coordinate);
}