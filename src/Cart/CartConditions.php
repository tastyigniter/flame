<?php

namespace Igniter\Flame\Cart;

use Illuminate\Support\Collection;

class CartConditions extends Collection
{
    public function apply($subtotal)
    {
        return $this
            ->sorted()
            ->reduce(function ($total, CartCondition $condition) {
                if ($condition->beforeApply() === FALSE)
                    return $total;

                $condition->apply($total);

                $condition->afterApply();

                return $condition->calculateTotal($total);
            }, $subtotal);
    }

    public function applied()
    {
        return $this->filter(function (CartCondition $condition) {
            return ($condition->isValid() AND $condition->isApplied());
        });
    }

    public function sorted()
    {
        return $this->sortBy(function ($condition) {
            return $condition->priority;
        });
    }
}
