<?php

namespace Igniter\Flame\Cart;

use Illuminate\Support\Collection;

class CartItemOptions extends Collection
{
    /**
     * Get the option by the given key.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    public function subtotal()
    {
        return $this->sum(function (CartItemOption $option) {
            return $option->subtotal();
        });
    }
}
