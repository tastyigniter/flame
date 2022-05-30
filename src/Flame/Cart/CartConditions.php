<?php

namespace Igniter\Flame\Cart;

use Illuminate\Support\Collection;

class CartConditions extends Collection
{
    public function apply(CartContent $content)
    {
        return $this
            ->sorted()
            ->reduce(function ($total, CartCondition $condition) use ($content) {
                return $condition->withTarget($content)->apply($total);
            }, $content->subtotal());
    }

    public function applied()
    {
        return $this->filter(function (CartCondition $condition) {
            return $condition->isValid();
        });
    }

    public function sorted()
    {
        return $this->sortBy(function ($condition) {
            return $condition->priority;
        });
    }
}
