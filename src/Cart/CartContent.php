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
        $subTotal = $this->reduce(function ($subTotal, CartItem $cartItem) {
            return $subTotal + $cartItem->subtotal();
        }, 0);

        return $subTotal;
    }
}