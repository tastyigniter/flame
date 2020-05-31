<?php

namespace Igniter\Flame\Cart;

use Exception;
use Illuminate\Support\Collection;

class CartConditions extends Collection
{
    public function applied($subtotal)
    {
        $items = $this->sortBy(function ($condition) {
            return $condition->priority;
        });

        $items->reduce(function ($total, CartCondition $condition) {
            return $condition->apply($total)->calculateTotal($total);
        }, $subtotal);

        return $items->filter(function (CartCondition $condition) {
            return $condition->isValid();
        });
    }

    public function total(float $subtotal)
    {
        return $this->sortBy(function ($condition) {
            return $condition->priority;
        })->reduce(function ($total, CartCondition $condition) {
            return $condition->calculateTotal($total);
        }, $subtotal);
    }

    public function getFrom($config)
    {
        $name = array_get($config, 'name');
        if ($condition = $this->get($name)) {
            $condition->fillFromConfig($config);
        }
        else {
            $condition = $this->makeCondition($config);
        }

        return $condition;
    }

    protected function makeCondition($config)
    {
        $className = array_get($config, 'className');
        if (!class_exists($className))
            throw new Exception(sprintf("The Cart Condition class name '%s' has not been registered", $className));

        return new $className($config);
    }
}