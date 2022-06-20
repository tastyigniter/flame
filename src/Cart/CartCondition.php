<?php

namespace Igniter\Flame\Cart;

use Igniter\Flame\Cart\Helpers\CartConditionHelper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Serializable;

/**
 * CartCondition class
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
     * @var string
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

    public $removeable = false;

    //
    // Object properties
    //

    protected $sessionKey = 'cart.%s.conditions.%s';

    protected $cartInstance = 'default';

    /**
     * @var \Igniter\Flame\Cart\CartContent|\Igniter\Flame\Cart\CartItem
     */
    protected $target;

    protected $passed;

    protected $calculatedValue;

    /**
     * The config for this cart condition.
     *
     * @var array
     */
    protected $config = [];

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

    public function fillFromConfig($config)
    {
        $this->label = array_get($config, 'label', $this->label);
        $this->name = array_get($config, 'name', $this->name);
        $this->priority = array_get($config, 'priority', $this->priority);
        $this->removeable = array_get($config, 'removeable', $this->removeable);
        $this->cartInstance = array_get($config, 'cartInstance', $this->cartInstance);

        if ($metaData = array_get($config, 'metaData'))
            Session::put($this->getSessionKey(), $metaData);
    }

    public function isValid()
    {
        return $this->passed;
    }

    public function isApplied()
    {
        return $this->isValid();
    }

    public function isInclusive()
    {
        return collect($this->getActions())
            ->filter(function ($action) {
                return array_get($action, 'inclusive', false);
            })
            ->isNotEmpty();
    }

    /**
     * Apply condition to cart content
     *
     * @param $subTotal
     *
     * @return float|string
     */
    public function apply($subTotal)
    {
        if ($this->beforeApply() === false)
            return $subTotal;

        if ($this->validate($this->getRules()))
            $subTotal = $this->calculate($subTotal);

        $this->afterApply();

        return $subTotal;
    }

    /**
     * Get the calculated the value of this condition
     * Used internally when applying to cart item
     *
     * @param $subTotal
     *
     * @return float|string
     */
    public function calculate($subTotal)
    {
        $this->calculatedValue = 0;

        return collect($this->getActions())
            ->map(function ($action) use ($subTotal) {
                return $this->processActionValue($action, $subTotal);
            })
            ->reduce(function ($total, $action) {
                return $this->calculateActionValue($action, $total);
            }, $subTotal);
    }

    //
    // Extensions & Overrides
    //

    /**
     * Called before condition is loaded into cart session
     */
    public function onLoad()
    {
    }

    /**
     * Called before the applying of condition on the entire cart.
     */
    public function beforeApply()
    {
    }

    /**
     * Called after the applying of condition on the entire cart.
     */
    public function afterApply()
    {
    }

    /**
     * Returns the rules for this cart condition.
     *
     * @return array
     */
    public function getRules()
    {
        return [];
    }

    /**
     * Returns the actions for this cart condition.
     *
     * @return array
     */
    public function getActions()
    {
        return [];
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

    public function withTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    public function setCartContent($cartContent)
    {
        traceLog('CartCondition::setCartContent() is deprecated. See CartCondition::withTarget()');

        return $this->withTarget($cartContent);
    }

    public function getCartContent()
    {
        traceLog('CartCondition::getCartContent() is deprecated. Use Cart::content() instead');

        return $this->target;
    }

    public function getLabel()
    {
        return is_lang_key($this->label) ? lang($this->label) : $this->label;
    }

    public function getValue()
    {
        return $this->calculatedValue;
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

    protected function getConfig($key, $default = null)
    {
        return array_get($this->config, $key, $default);
    }

    protected function setConfig($key, $value)
    {
        return array_set($this->config, $key, $value);
    }

    public function getMetaData($key = null, $default = null)
    {
        $metaData = Session::get($this->getSessionKey(), []);
        if (is_null($key))
            return $metaData;

        return Arr::get($metaData, $key, $default);
    }

    public function setMetaData($key, $value = null)
    {
        $metaData = Session::get($this->getSessionKey(), []);

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                Arr::set($metaData, $k, $v);
            }
        }
        else {
            Arr::set($metaData, $key, $value);
        }

        Session::put($this->getSessionKey(), $metaData);
    }

    public function removeMetaData($key = null)
    {
        $metaData = Session::get($this->getSessionKey(), []);

        if (is_null($key)) {
            $metaData = [];
        }
        else {
            Arr::pull($metaData, $key);
        }

        Session::put($this->getSessionKey(), $metaData);
    }

    public function clearMetaData()
    {
        Session::pull($this->getSessionKey());
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
            'name' => $this->name,
            'label' => $this->label,
            'priority' => $this->priority,
            'removeable' => $this->removeable,
            'metaData' => Session::get($this->getSessionKey(), []),
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
