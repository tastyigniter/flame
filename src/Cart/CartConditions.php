<?php

namespace Igniter\Flame\Cart;

use Illuminate\Support\Collection;

class CartConditions extends Collection
{
    public function apply(CartContent $content, string $type = null)
    {
        return $this
            ->sorted()
            ->reduce(function ($total, CartCondition $condition) use ($content, $type) {
                return $condition->withTarget($content)->apply($total, $type) ;
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
