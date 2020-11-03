<?php

namespace Igniter\Flame\Cart;

use Igniter\Flame\Cart\Contracts\Buyable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

class CartItem implements Arrayable, Jsonable
{
    /**
     * The rowID of the cart item.
     *
     * @var string
     */
    public $rowId;

    /**
     * The ID of the cart item.
     *
     * @var int|string
     */
    public $id;

    /**
     * The quantity for this cart item.
     *
     * @var int|float
     */
    public $qty;

    /**
     * The name of the cart item.
     *
     * @var string
     */
    public $name;

    /**
     * The price of the cart item.
     *
     * @var float
     */
    public $price;

    /**
     * The comment of the cart item.
     *
     * @var string
     */
    public $comment;

    /**
     * The options for this cart item.
     *
     * @var array
     */
    public $options;

    /**
     * The conditions for this cart item.
     *
     * @var array
     */
    public $conditions;

    /**
     * The FQN of the associated model.
     *
     * @var string|null
     */
    protected $associatedModel;

    /**
     * CartItem constructor.
     *
     * @param int|string $id
     * @param string $name
     * @param float $price
     * @param array $options
     * @param null $comment
     * @param array $conditions
     */
    public function __construct($id, $name, $price, array $options = [], $comment = null, array $conditions = [])
    {
        if (!strlen($id)) {
            throw new \InvalidArgumentException('Please supply a valid cart item identifier.');
        }
        if (!strlen($name)) {
            throw new \InvalidArgumentException('Please supply a valid cart item name.');
        }
        if (strlen($price) < 0 OR !is_numeric($price)) {
            throw new \InvalidArgumentException('Please supply a valid cart item price.');
        }

        $this->id = $id;
        $this->name = $name;
        $this->price = (float)$price;
        $this->options = $this->makeCartItemOptions($options);
        $this->conditions = $this->makeCartItemConditions($conditions);
        $this->comment = $comment;
        $this->rowId = $this->generateRowId($id, $options);
    }

    /**
     * Returns the formatted price
     *
     * @return float
     */
    public function price()
    {
        $price = $this->priceWithoutConditions();

        return optional($this->conditions)->apply($price, $this) ?? $price;
    }

    /**
     * Returns the subtotal.
     * Subtotal is price for whole CartItem with options
     *
     * @return float
     */
    public function subtotal()
    {
        $price = $this->price();

        $optionsSum = $this->options->subtotal();

        return $this->qty * ($price + $optionsSum);
    }

    public function priceWithoutConditions()
    {
        return $this->price;
    }

    public function subtotalWithoutConditions()
    {
        $price = $this->priceWithoutConditions();

        $optionsSum = $this->options->subtotal();

        return $this->qty * ($price + $optionsSum);
    }

    public function comment()
    {
        return $this->comment;
    }

    public function hasOptions()
    {
        return count($this->options);
    }

    public function hasOptionValue($valueIndex)
    {
        return $this->options->search(function ($option) use ($valueIndex) {
            return in_array($valueIndex, $option->values->pluck('id')->all());
        });
    }

    public function hasConditions()
    {
        return count($this->conditions);
    }

    public function clearConditions()
    {
        $this->conditions = new CartItemConditions();

        return $this;
    }

    public function getModel()
    {
        if (!is_null($this->associatedModel)) {
            return with(new $this->associatedModel)->find($this->id);
        }
    }

    /**
     * Set the quantity for this cart item.
     *
     * @param int|float $qty
     */
    public function setQuantity($qty)
    {
        if (!is_numeric($qty))
            throw new \InvalidArgumentException('Please supply a valid quantity.');

        $this->qty = $qty;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * Update the cart item from a Buyable.
     *
     * @param \Igniter\Flame\Cart\Contracts\Buyable $item
     *
     * @return void
     */
    public function updateFromBuyable(Buyable $item)
    {
        $this->id = $item->getBuyableIdentifier();
        $this->name = $item->getBuyableName();
        $this->price = $item->getBuyablePrice();
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
        $this->price = array_get($attributes, 'price', $this->price);
        $this->qty = array_get($attributes, 'qty', $this->qty);
        $this->options = $this->makeCartItemOptions(array_get($attributes, 'options', $this->options));
        $this->conditions = $this->makeCartItemConditions(array_get($attributes, 'conditions', $this->conditions));
        $this->comment = array_get($attributes, 'comment', $this->comment);

        $this->rowId = $this->generateRowId($this->id, $this->options->all());
    }

    /**
     * Associate the cart item with the given model.
     *
     * @param mixed $model
     *
     * @return \Igniter\Flame\Cart\CartItem
     */
    public function associate($model)
    {
        $this->associatedModel = is_string($model) ? $model : get_class($model);

        return $this;
    }

    /**
     * Get an attribute from the cart item or get the associated model.
     *
     * @param string $attribute
     *
     * @return mixed
     */
    public function __get($attribute)
    {
        if ($attribute === 'subtotal') {
            return $this->subtotal();
        }

        if ($attribute === 'model' AND !is_null($this->associatedModel)) {
            return with(new $this->associatedModel)->find($this->id);
        }

        return null;
    }

    /**
     * Create a new instance from a Buyable.
     *
     * @param \Igniter\Flame\Cart\Contracts\Buyable $item
     * @param array $options
     * @param null $comment
     * @param array $conditions
     *
     * @return \Igniter\Flame\Cart\CartItem
     */
    public static function fromBuyable(Buyable $item, array $options = [], $comment = null, array $conditions = [])
    {
        return new self(
            $item->getBuyableIdentifier(),
            $item->getBuyableName(),
            $item->getBuyablePrice(),
            $options,
            $comment,
            $conditions
        );
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array $attributes
     *
     * @return \Igniter\Flame\Cart\CartItem
     */
    public static function fromArray(array $attributes)
    {
        return new self(
            $attributes['id'],
            $attributes['name'],
            $attributes['price'],
            array_get($attributes, 'options', []),
            array_get($attributes, 'comment'),
            array_get($attributes, 'conditions', [])
        );
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param string $id
     * @param array $options
     *
     * @return string
     */
    protected function generateRowId($id, array $options)
    {
        ksort($options);

        return md5($id.serialize($options).$this->comment);
    }

    protected function makeCartItemOptions($options)
    {
        if ($options instanceof CartItemOptions)
            return $options;

        return new CartItemOptions(array_map(function ($option) {
            return CartItemOption::fromArray($option);
        }, $options));
    }

    protected function makeCartItemConditions($conditions)
    {
        if ($conditions instanceof CartItemConditions)
            return $conditions;

        return new CartItemConditions($conditions);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'rowId' => $this->rowId,
            'id' => $this->id,
            'name' => $this->name,
            'qty' => $this->qty,
            'price' => $this->price,
            'options' => $this->options->toArray(),
            'conditions' => $this->conditions->toArray(),
            'comment' => $this->comment,
            'subtotal' => $this->subtotal(),
        ];
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }
}
