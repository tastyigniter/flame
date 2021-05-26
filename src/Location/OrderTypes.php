<?php

namespace Igniter\Flame\Location;

use Igniter\Flame\Traits\Singleton;

class OrderTypes
{
    use Singleton;

    /**
     * @var array An array of registered order types.
     */
    protected $registeredOrderTypes = [];

    /**
     * @var array Cache of order types registration callbacks.
     */
    protected static $registeredCallbacks = [];

    protected function initialize()
    {
        $this->loadOrderTypes();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function makeOrderTypes($location)
    {
        return collect($this->registeredOrderTypes)
            ->map(function ($orderType) use ($location) {
                return new $orderType['className']($location, $orderType);
            });
    }

    /**
     * @param $code
     * @return \Igniter\Flame\Location\AbstractOrderType
     */
    public function findOrderType($code)
    {
        return array_get($this->registeredOrderTypes, $code);
    }

    public function listOrderTypes()
    {
        return $this->registeredOrderTypes;
    }

    public function loadOrderTypes()
    {
        foreach (self::$registeredCallbacks as $callback) {
            $callback($this);
        }
    }

    public function registerOrderTypes($orderTypes)
    {
        foreach ($orderTypes as $className => $definition) {
            $this->registerOrderType($className, $definition);
        }
    }

    public function registerOrderType($className, $definition)
    {
        $code = $definition['code'] ?? strtolower(basename($className));

        if (!array_key_exists('name', $definition))
            $definition['name'] = $code;

        $this->registeredOrderTypes[$code] = array_merge($definition, [
            'className' => $className,
        ]);
    }

    public static function registerCallback(callable $definitions)
    {
        self::$registeredCallbacks[] = $definitions;
    }
}
