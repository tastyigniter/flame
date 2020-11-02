<?php

namespace Igniter\Flame\Cart\Helpers;

trait ActsAsItemable
{
    /**
     * Get the instance to apply on a cart item.
     *
     * @param \Igniter\Flame\Cart\CartItem $cartItem
     * @return static
     */
    public function toItem()
    {
        return new static($this->toArray());
    }

    public static function isApplicableTo($cartItem)
    {
    }
}
