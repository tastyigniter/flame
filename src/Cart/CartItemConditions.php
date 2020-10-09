<?php

namespace Igniter\Flame\Cart;

use Illuminate\Support\Collection;

class CartItemConditions extends Collection
{
    public function apply($subtotal)
    {
        return $this
            ->reduce(function ($total, CartCondition $condition) {
                $condition->processValue($total);

                return $condition->processTotal($total);
            }, $subtotal);
    }
}
