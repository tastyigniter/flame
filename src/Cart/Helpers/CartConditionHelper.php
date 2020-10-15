<?php

namespace Igniter\Flame\Cart\Helpers;

use Exception;

trait CartConditionHelper
{
    /**
     * @var \Illuminate\Support\Collection
     */
    protected $actionCollection;

    protected function validate($rules)
    {
        $validated = collect($rules)->filter(function ($rule) {
            return $this->ruleIsValid($rule);
        })->count();

        if ($this->passed = ($validated == count($rules))) {
            $this->whenValid();
        }
        else {
            $this->whenInvalid();
        }

        return $this->passed;
    }

    /**
     * Added for backward compatibility
     *
     * @param $subTotal
     * @return float|string
     */
    protected function processValue($subTotal)
    {
        return $this->calculate($subTotal);
    }

    protected function processActionValue($action, $total)
    {
        $action = $this->parseAction($action);
        $actionValue = array_get($action, 'value', 0);

        if ($this->valueIsPercentage($actionValue)) {
            $cleanValue = $this->cleanValue($actionValue);
            $value = ($total * ($cleanValue / 100));
        }
        else {
            $value = (float)$this->cleanValue($actionValue);
        }

        $this->calculatedValue += $value;
        $action['cleanValue'] = $value;

        return $action;
    }

    protected function calculateActionValue($action, $total)
    {
        $action = $this->parseAction($action);
        $actionValue = array_get($action, 'value', 0);
        $calculatedValue = array_get($action, 'cleanValue', 0);
        $actionMultiplier = array_get($action, 'multiplier');
        $actionMax = array_get($action, 'max', FALSE);

        $result = $total;
        if ($this->actionIsInclusive($action)) {
            $result = $total;
        }
        elseif ($this->valueIsToBeSubtracted($actionValue)) {
            $result = ($total - $calculatedValue);
        }
        elseif ($this->valueIsToBeAdded($actionValue)) {
            $result = ($total + $calculatedValue);
        }
        elseif ($this->valueIsToBeMultiplied($actionValue)) {
            $result = ($total * $calculatedValue);
        }
        elseif ($this->valueIsToBeDivided($actionValue)) {
            $result = (float)($total / $calculatedValue);
        }

        if ($actionMultiplier)
            $result = (float)($total * $this->operandValue($actionMultiplier));

        if ($this->actionHasReachedMax($actionMax, $result))
            $result = $actionMax;

        return max($result, 0);
    }

    protected function actionHasReachedMax($actionMax, $value)
    {
        return ($actionMax AND $value > $actionMax) ? $actionMax : FALSE;
    }

    /**
     * Removes some arithmetic signs (%,+,-, /, *) only
     *
     * @param $value
     * @param string $include
     *
     * @return mixed
     */
    protected function cleanValue($value)
    {
        return str_replace(['%', '-', '+', '*', '/'], '', $value);
    }

    protected function operandValue($key)
    {
        if (property_exists($this, $key))
            return $this->{$key};

        if ($key !== 'total' AND $this->target AND method_exists($this->target, $key))
            return call_user_func([$this->target, $key]);

        return $key;
    }

    protected function ruleIsValid($rule)
    {
        [$leftOperand, $operator, $rightOperand] = $this->parseRule($rule);
        $leftOperand = $this->operandValue($leftOperand);
        $rightOperand = $this->operandValue($rightOperand);

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
            throw new Exception(sprintf('Cart condition rule [%s] format is invalid on %s.', $rule, get_class($this)));

        array_shift($matches);

        return $matches;
    }

    protected function parseAction($action)
    {
        if ($action == [])
            return $action;

        if (!array_key_exists('value', $action))
            throw new Exception(sprintf('Cart condition action [%s] format is invalid on %s.', $action, get_class($this)));

        return $action;
    }

    protected function actionIsInclusive($action)
    {
        return array_get($action, 'inclusive', FALSE);
    }

    /**
     * Check if value is a percentage
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsPercentage($value)
    {
        return preg_match('/%/', $value) == 1;
    }

    /**
     * Check if value is a subtract
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsToBeSubtracted($value)
    {
        return preg_match('/\-/', $value) == 1;
    }

    /**
     * Check if value is to be added
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsToBeAdded($value)
    {
        return preg_match('/\+/', $value) == 1;
    }

    /**
     * Check if value is to be added
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsToBeMultiplied($value)
    {
        return preg_match('/\*/', $value) == 1;
    }

    /**
     * Check if value is to be added
     *
     * @param $value
     *
     * @return bool
     */
    protected function valueIsToBeDivided($value)
    {
        return preg_match('/\\//', $value) == 1;
    }

    //
    // Session
    //

    protected function getSessionKey()
    {
        return sprintf($this->sessionKey, $this->name);
    }
}
