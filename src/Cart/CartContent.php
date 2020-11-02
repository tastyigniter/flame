<?php

namespace Igniter\Flame\Cart;

use Illuminate\Support\Collection;

class CartContent extends Collection
{
    public function quantity()
    {
        return $this->sum('qty');
    }

    public function subtotal()
    {
        return $this->sum(function (CartItem $cartItem) {
            return $cartItem->subtotal();
        });
    }
}
