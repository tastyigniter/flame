<?php

namespace Igniter\Flame\Cart;

use Igniter\Flame\Cart\Helpers\CartConditionHelper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Serializable;

/**
 * CartCondition class
 * Usage:
 **
 *   $condition = new CartCondition($code, $name, $target, $action, $priority);
 *   Cart::condition($condition);
 */
abstract class CartCondition implements Arrayable, Jsonable, Serializable
{
    use CartConditionHelper;

    //
    // Configurable properties
    //

    /**
     * The name for this cart condition.
     *
     * @var int|float
     */
    public $name = 'default';

    /**
     * The label for this cart condition.
     *
     * @var int|float
     */
    public $label;

    /**
     * The priority for this cart condition.
     *
     * @var int
     */
    public $priority = 0;

    /**
     * The target of the cart condition.
     *
     * @var string subtotal or total or quantity
     */
    public $target = 'subtotal';

    public $removeable = FALSE;

    public $disabled = FALSE;

    //
    // Object properties
    //

    protected $cartInstance;

    /**
     * @var \Igniter\Flame\Cart\CartContent
     */
    protected $cartContent;

    protected $passed = FALSE;

    protected $result;

    protected $calculatedTotal;

    /**
     * The config for this cart condition.
     *
     * @var array
     */
    protected $config = [];

    protected $metaData = [];

    /**
     * CartItem constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->config = $config;
        $this->fillFromConfig($config);
    }

    protected function fillFromConfig($config)
    {
        $this->label = array_get($config, 'label', $this->label);
        $this->name = array_get($config, 'name', $this->name);
        $this->target = array_get($config, 'target', $this->target);
        $this->priority = array_get($config, 'priority', $this->priority);
        $this->metaData = array_get($config, 'metaData', $this->metaData);
    }

    public function isValid()
    {
        return $this->passed;
    }

    public function apply($total)
    {
        if ($this->beforeApply() === FALSE)
            return $total;

        $rules = $this->getRules();
        if ($passed = $this->validate($rules))
            $total = $this->calculateTotal($total);

        if ($rules AND $this->totalAsChanged($total)) {
            if ($passed) {
                $this->whenValid();
            }
            else {
                $this->whenInvalid();
            }
        }

        $this->passed = $passed;
        $this->calculatedTotal = $total;

        $this->afterApply();

        return $total;
    }

    public function calculatedValue()
    {
        return $this->result;
    }

    //
    // Extensions & Overrides
    //

    /**
     * Get the rules for this cart condition.
     *
     * @return array
     */
    public function getRules()
    {
        return [];
    }

    /**
     * Get the actions for this cart condition.
     *
     * @return array
     */
    public function getActions()
    {
        return [];
    }

    /**
     * Called before condition is loaded into cart session
     */
    public function onLoad()
    {
    }

    /**
     * Called before the applying of condition on cart total.
     */
    public function beforeApply()
    {
    }

    /**
     * Called after the applying of condition on cart total.
     */
    public function afterApply()
    {
    }

    /**
     * Called once when the condition validation passes.
     */
    public function whenValid()
    {
    }

    /**
     * Called once when the condition validation fails.
     */
    public function whenInvalid()
    {
    }

    //
    // Getters and Setters
    //

    /**
     * Apply condition to target
     *
     * @param $cartInstance
     * @param $cartContent
     *
     * @return \Igniter\Flame\Cart\CartCondition
     */
    public function setCart($cartInstance, CartContent $cartContent)
    {
        $this->cartInstance = $cartInstance;
        $this->cartContent = $cartContent;

        return $this;
    }

    public function getLabel()
    {
        return (sscanf($this->label, 'lang:%s', $line) === 1) ? lang($line) : $this->label;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Set the order in which this condition is applied.
     *
     * @param int $priority
     */
    public function setPriority($priority = 999)
    {
        $this->priority = $priority;
    }

    public function getConfig($key, $default = null)
    {
        return array_get($this->config, $key, $default);
    }

    public function setConfig($key, $value)
    {
        return array_set($this->config, $key, $value);
    }

    public function getMetaData($key = null, $default = null)
    {
        if (is_null($key))
            return $this->metaData;

        return Arr::get($this->metaData, $key, $default);
    }

    public function setMetaData($key, $value)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Arr::set($this->metaData, $k, $v);
            }
        }
        else {
            Arr::set($this->metaData, $key, $value);
        }
    }

    public function removeMetaData($key = null)
    {
        if (is_null($key)) {
            $this->metaData = [];
        }
        else {
            return Arr::pull($this->metaData, $key);
        }
    }

    //
    //
    //

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'name'       => $this->name,
            'label'      => $this->label,
            'target'     => $this->target,
            'priority'   => $this->priority,
            'metaData'   => $this->metaData,
            'removeable' => $this->removeable,
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

    /**
     * String representation of object
     */
    public function serialize()
    {
        return serialize($this->toArray());
    }

    /**
     * Constructs the object
     *
     * @param string $serialized <p>
     *
     * @return void
     */
    public function unserialize($serialized)
    {
        $this->fillFromConfig(unserialize($serialized));
    }
}