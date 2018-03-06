<?php

namespace Igniter\Flame\Cart\Contracts;

interface Buyable
{
    /**
     * Get the identifier of the Buyable item.
     *
     * @param array $options
     *
     * @return int|string
     */
    public function getBuyableIdentifier($options = null);

    /**
     * Get the description or title of the Buyable item.
     *
     * @param array $options
     *
     * @return string
     */
    public function getBuyableName($options = null);

    /**
     * Get the price of the Buyable item.
     *
     * @param array $options
     *
     * @return float
     */
    public function getBuyablePrice($options = null);
}