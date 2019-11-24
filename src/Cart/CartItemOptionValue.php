<?php

namespace Igniter\Flame\Cart;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class CartItemOptionValue implements Arrayable, Jsonable
{
    /**
     * The ID of the cart item option value.
     *
     * @var int|string
     */
    public $id;

    /**
     * The name of the cart item option value.
     *
     * @var string
     */
    public $name;

    /**
     * The price of the cart item option value.
     *
     * @var float
     */
    public $price;

    /**
     * CartItem constructor.
     *
     * @param int|string $id
     * @param string $name
     * @param float $price
     * @param array $options
     * @param null $comment
     */
    public function __construct($id, $name, $price)
    {
        if (!strlen($id)) {
            throw new \InvalidArgumentException('Please supply a valid cart item option value identifier.');
        }
        if (!strlen($name)) {
            throw new \InvalidArgumentException('Please supply a valid cart item option value name.');
        }
        if (strlen($price) < 0 OR !is_numeric($price)) {
            throw new \InvalidArgumentException('Please supply a valid cart item option value price.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->price = (float)$price;
    }

    /**
     * Returns the formatted price of this cart item option value
     *
     * @return string
     */
    public function price()
    {
        return $this->price;
    }

    /**
     * Update the cart item option value from an array.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function updateFromArray(array $attributes)
    {
        $this->id = array_get($attributes, 'id', $this->id);
        $this->name = array_get($attributes, 'name', $this->name);
        $this->price = array_get($attributes, 'price', $this->price);
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array $attributes
     *
     * @return \Igniter\Flame\Cart\CartItemOptionValue
     */
    public static function fromArray(array $attributes)
    {
        return new self(
            $attributes['id'],
            $attributes['name'],
            $attributes['price']
        );
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
            'price' => $this->price,
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}