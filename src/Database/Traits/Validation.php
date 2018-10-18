<?php

namespace Igniter\Flame\Database\Traits;

use Igniter\Flame\Exception\ValidationException;
use Watson\Validating\ValidatingTrait;

trait Validation
{
    use ValidatingTrait;

    /**
     * Get the validating attribute names.
     *
     * @return array
     */
    public function getValidationAttributeNames()
    {
        if (!$this->validationAttributeNames
            AND $customAttributes = $this->parseAttributes($this->rules ?? []))
            return $customAttributes;

        return $this->validationAttributeNames ?? [];
    }

    /**
     * Get the global validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->parseRules($this->rules ?? []);
    }

    /**
     * Throw a validation exception.
     *
     * @throws \Watson\Validating\ValidationException
     */
    public function throwValidationException()
    {
        $validator = $this->makeValidator($this->getRules());

        throw new ValidationException($validator, $this);
    }

    /**
     * Returns whether the model will raise an exception or
     * return a boolean when validating.
     *
     * @return bool
     */
    public function getThrowValidationExceptions()
    {
        return $this->throwValidationExceptions ?? TRUE;
    }

    protected function parseRules(array $rules)
    {
        if (!isset($rules[0]))
            return $rules;

        $result = [];
        foreach ($rules as $key => list($field, $label, $rule)) {
            $result[$field] = $rule ?? [];
        }

        return $result;
    }

    protected function parseAttributes(array $rules)
    {
        if (!isset($rules[0]))
            return [];

        $result = [];
        foreach ($rules as $key => list($name, $attribute,)) {
            $result[$name] = (sscanf($attribute, 'lang:%s', $line) === 1) ? lang($line) : $attribute;
        }

        return $result;
    }
}