<?php

namespace Igniter\Flame\Cart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class CartItemOption implements Arrayable, Jsonable
{
    /**
     * The ID of the cart item option.
     *
     * @var int|string
     */
    public $id;

    /**
     * The name of the cart item option.
     *
     * @var string
     */
    public $name;

    /**
     * The values for this cart item option.
     *
     * @var \Illuminate\Support\Collection
     */
    public $values;

    /**
     * CartItem constructor.
     *
     * @param int|string $id
     * @param string $name
     * @param array $values
     */
    public function __construct($id, $name, $values = [])
    {
        if (!strlen($id)) {
            throw new \InvalidArgumentException('Please supply a valid cart item option identifier.');
        }
        if (!strlen($name)) {
            throw new \InvalidArgumentException('Please supply a valid cart item option name.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->values = $this->makeCartOptionValues($values);
    }

    /**
     * Returns the subtotal.
     * Subtotal is price for whole CartItem with options
     *
     * @return string
     */
    public function subtotal()
    {
        return $this->values->reduce(function ($subtotal, CartItemOptionValue $optionValue) {
            return $subtotal + $optionValue->subtotal();
        }, 0);
    }

    /**
     * Update the cart item from an array.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function updateFromArray(array $attributes)
    {
        $this->id = array_get($attributes, 'id', $this->id);
        $this->name = array_get($attributes, 'name', $this->name);
        $this->values = $this->makeCartOptionValues(array_get($attributes, 'values', $this->values));
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array $attributes
     *
     * @return \Igniter\Flame\Cart\CartItemOption
     */
    public static function fromArray(array $attributes)
    {
        return new self(
            $attributes['id'],
            $attributes['name'],
            array_get($attributes, 'values', [])
        );
    }

    protected function makeCartOptionValues($values)
    {
        if ($values instanceof CartItemOptionValues)
            return $values;

        return new CartItemOptionValues(array_map(function ($item) {
            return CartItemOptionValue::fromArray($item);
        }, $values));
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'values' => $this->values,
            'subtotal' => $this->subtotal(),
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}