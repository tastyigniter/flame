<?php

namespace Igniter\Flame\Cart\Helpers;

use Exception;

trait CartConditionHelper
{
    protected function validate($rules)
    {
        $passed = collect($rules)->filter(function ($rule) {
            return $this->ruleIsValid($rule);
        })->count();

        return $passed == count($rules);
    }

    protected function calculateValue($result, $actionValue)
    {
        if ($this->valueIsPercentage($actionValue)) {
            $cleanValue = $this->cleanValue($actionValue);
            $value = ($result * ($cleanValue / 100));
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

        $result = collect($this->getActions())->reduce(function ($total, $action) {
            $action = $this->parseAction($action);

            $actionValue = array_get($action, 'value', 0);

            $value = $this->calculateValue($total, $actionValue);

            $result = $total;
            if ($this->actionIsInclusive($action)) {
                $result = $total;
            }
            else if ($this->valueIsToBeSubtracted($actionValue)) {
                $result = ($total - $value);
            }
            else if ($this->valueIsToBeAdded($actionValue)) {
                $result = ($total + $value);
            }
            else if ($this->valueIsToBeMultiplied($actionValue)) {
                $result = ($total * $value);
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
        if (!method_exists($this->cartContent, $this->target))
            throw new \BadMethodCallException(sprintf('Cart content property [%s] was not found on %s',
                $this->target, get_class($this->cartContent)));

        return call_user_func([$this->cartContent, $this->target]);
    }

    protected function getContentValue($key)
    {
        if (property_exists($this, $key))
            return $this->{$key};

        if (!method_exists($this->cartContent, $key))
            return $key;

        return call_user_func([$this->cartContent, $key]);
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
            throw new Exception(sprintf('Cart condition rule [%s] format is invalid on %s.', $rule, get_class($this)));

        array_shift($matches);

        return $matches;
    }

    protected function parseAction($action)
    {
        if ($action == [])
            return $action;

        if (!isset($action['value']))
            throw new Exception(sprintf('Cart condition action [%s] format is invalid on %s.', $action, get_class($this)));

        return $action;
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
}