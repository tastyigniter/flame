<?php

namespace Igniter\Flame\Cart;

use Exception;
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
class CartCondition implements Arrayable, Jsonable, Serializable
{
    /**
     * The uniqueID of the cart condition.
     *
     * @var string
     */
    public $uniqueId;

    /**
     * The name for this cart condition.
     *
     * @var int|float
     */
    public $name;

    /**
     * The label for this cart condition.
     *
     * @var int|float
     */
    public $label;

    /**
     * The type of the cart condition.
     *
     * @var int|string
     */
    public $type;

    /**
     * The priority for this cart condition.
     *
     * @var int
     */
    public $priority;

    /**
     * The target of the cart condition.
     *
     * @var string subtotal or total or quantity
     */
    public $target = 'subtotal';

    /**
     * The actions for this cart condition.
     *
     * @var array
     */
    public $actions = [];

    /**
     * The rules for this cart condition.
     *
     * @var array
     */
    public $rules = [];

    public $metaData = [];

    public $removeable = FALSE;

    public $passed = TRUE;

    protected $result;

    protected $content;

    protected $calculatedTotal;

    protected $whenInvalidCallbacks = [];

    protected $whenValidCallbacks = [];

    /**
     * CartItem constructor.
     *
     * @param $name
     * @param $config
     */
    public function __construct($name, $config)
    {
        if (!strlen($name))
            throw new \InvalidArgumentException('Please supply a valid name.');

        $this->name = $name;
        $this->evalConfig($config);
    }

    protected function evalConfig($config)
    {
        if (!isset($config['label']) OR !strlen($config['label']))
            throw new \InvalidArgumentException('Please supply a valid label.');

        $priority = array_get($config, 'priority', 0);
        if (strlen($priority) < 0 OR !is_numeric($priority))
            throw new \InvalidArgumentException('Please supply a valid priority.');

        $this->priority = $priority;
        $this->name = array_get($config, 'name', $this->name);
        $this->label = array_get($config, 'label', $this->label);
        $this->type = array_get($config, 'type', 'other');
        $this->target = array_get($config, 'target', $this->target);
        $this->metaData = array_get($config, 'metaData', $this->metaData);
//        $this->actions = array_get($config, 'actions', $this->actions);
//        $this->rules = array_get($config, 'rules', $this->rules);
        $this->removeable = array_get($config, 'removeable', $this->removeable);
//        $this->passed = array_get($config, 'passed', $this->passed);
        $this->uniqueId = $this->generateUniqueId($this->name, $this->target);
    }

    /**
     * Set the order to apply this condition.
     *
     * @param int $priority
     */
    public function setPriority($priority = 999)
    {
        $this->priority = $priority;
    }

    /**
     * Set the actions for this cart condition.
     *
     * @param $actions
     */
    public function setActions(array $actions)
    {
        if (!isset($actions[0]))
            $actions = [$actions];

        $this->actions = $actions;
    }

    /**
     * Set the rules for this cart condition.
     *
     * @param array $rules
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
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

    public function result()
    {
        return $this->result;
    }

    /**
     * Apply condition to target
     *
     * @param $cartContent
     *
     * @return \Igniter\Flame\Cart\CartCondition
     */
    public function applyContent($cartContent)
    {
        $this->content = $cartContent;

        return $this;
    }

    public function apply($total)
    {
        $validated = $this->validateRules();

        $total = $this->calculateTotal($total);

        if ($this->rules AND $this->totalAsChanged($total)) {
            if ($validated) {
                $this->resolveWhenValid();
            }
            else {
                $this->resolveWhenInvalid();
            }
        }

        return $this->calculatedTotal = $total;
    }

    protected function validateRules()
    {
        collect($this->rules)->each(function ($rule) {
            return $this->passed = $this->ruleIsValid($rule);
        });

        return $this->passed;
    }

    protected function calculateValue($result, $action)
    {
        $actionValue = array_get($action, 'value', 0);

        if ($this->valueIsPercentage($actionValue)) {
            $cleanValue = $this->cleanValue($actionValue);
            $value = (float)($result * ($cleanValue / 100));
        }
        else {
            $value = (float)$this->cleanValue($actionValue);
        }

        $this->result += $value;

        return $value;
    }

    protected function calculateTotal($total)
    {
        $this->result = 0;

        if (!$this->passed)
            return FALSE;

        $result = collect($this->actions)->reduce(function ($total, $action) {
            $actionValue = array_get($action, 'value', 0);

            $value = $this->calculateValue($total, $action);

            $result = $total;
            if ($this->actionIsInclusive($action)) {
                $result = $total;
            }
            else if ($this->valueIsToBeSubtracted($actionValue)) {
                $result = (float)($total - $value);
            }
            else if ($this->valueIsToBeAdded($actionValue)) {
                $result = (float)($total + $value);
            }
            else if ($this->valueIsToBeMultiplied($actionValue)) {
                $result = (float)($total * $value);
            }
            else if ($this->valueIsToBeDivided($actionValue)) {
                $result = (float)($total / $value);
            }

            if ($actionMultiplier = array_get($action, 'multiplier'))
                $result = (float)($total * $this->getContentValue($actionMultiplier));

            $actionMax = array_get($action, 'max', FALSE);
            if ($this->actionHasReachedMax($actionMax, $result))
                $result = $actionMax;

            return $result;
        }, $total);

        return $result;
    }

    protected function actionHasReachedMax($actionMax, $value)
    {
        return ($actionMax AND $value > $actionMax) ? $actionMax : FALSE;
    }

    /**
     * removes some arithmetic signs (%,+,-, /, *) only
     *
     * @param $value
     *
     * @return mixed
     */
    protected function cleanValue($value)
    {
        return str_replace(['%', '-', '+', '*', '/'], '', $value);
    }

    protected function getTargetValue()
    {
        if (!method_exists($this->content, $this->target))
            throw new \BadMethodCallException(sprintf('Attribute [%s] was not found on %s',
                $this->target, get_class($this->content)));

        return call_user_func([$this->content, $this->target]);
    }

    protected function getContentValue($key)
    {
        if (property_exists($this, $key))
            return $this->{$key};

        if (!method_exists($this->content, $key))
            return $key;

        return call_user_func([$this->content, $key]);
    }

    protected function ruleIsValid($rule)
    {
        list($leftOperand, $operator, $rightOperand) = $this->parseRule($rule);
        $leftOperand = $this->getContentValue($leftOperand);
        $rightOperand = $this->getContentValue($rightOperand);

        switch ($operator) {
            case '=':
                return $leftOperand == $rightOperand;
            case '==':
                return $leftOperand === $rightOperand;
            case '!=':
                return $leftOperand != $rightOperand;
            case '<':
                return $leftOperand < $rightOperand;
            case '<=':
                return $leftOperand <= $rightOperand;
            case '>':
                return $leftOperand > $rightOperand;
            case '>=':
                return $leftOperand >= $rightOperand;
        }

        return FALSE;
    }

    protected function parseRule($rule)
    {
        preg_match('/([a-zA-Z0-9\-?]+)(?:\s*)([\=\!\<\>]{1,2})(?:\s*)([\-?a-zA-Z0-9]+)/', $rule, $matches);

        if (!count($matches))
            throw new Exception(sprintf('Rule [%s] format is invalid.', $rule));

        array_shift($matches);

        return $matches;
    }

    protected function totalAsChanged($total)
    {
        return $this->calculatedTotal !== $total;
    }

    protected function actionIsInclusive($action)
    {
        return array_get($action, 'inclusive', FALSE);
    }

    /**
     * check if value is a percentage
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsPercentage($value)
    {
        return (preg_match('/%/', $value) == 1);
    }

    /**
     * check if value is a subtract
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsToBeSubtracted($value)
    {
        return (preg_match('/\-/', $value) == 1);
    }

    /**
     * check if value is to be added
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsToBeAdded($value)
    {
        return (preg_match('/\+/', $value) == 1);
    }

    /**
     * check if value is to be added
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsToBeMultiplied($value)
    {
        return (preg_match('/\*/', $value) == 1);
    }

    /**
     * check if value is to be added
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsToBeDivided($value)
    {
        return (preg_match('/\\//', $value) == 1);
    }

    /**
     * Generate a unique id for the cart condition.
     *
     * @param $type
     * @param $target
     *
     * @return string
     */
    protected function generateUniqueId($type, $target)
    {
        return md5($type.$target);
    }

    //
    // Callbacks
    //

    public function whenValid($callback)
    {
        $this->whenValidCallbacks[] = $callback;
    }

    public function whenInvalid($callback)
    {
        $this->whenInvalidCallbacks[] = $callback;
    }

    protected function resolveWhenValid()
    {
        foreach ($this->whenValidCallbacks as $callback) {
            $callback();
        }
    }

    protected function resolveWhenInvalid()
    {
        foreach ($this->whenInvalidCallbacks as $callback) {
            $callback();
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
            'type'       => $this->type,
            'priority'   => $this->priority,
            'target'     => $this->target,
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
        $this->evalConfig(unserialize($serialized));
    }
}