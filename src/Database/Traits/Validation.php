<?php

namespace Igniter\Flame\Database\Traits;

use Exception;
use Igniter\Flame\Exception\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use System\Helpers\ValidationHelper;

/**
 * Adapted from https://github.com/dwightwatson/validating/blob/master/src/ValidatingTrait.php
 */
trait Validation
{
    /**
     * Error messages as provided by the validator.
     *
     * @var \Illuminate\Support\MessageBag
     */
    protected $validationErrors;

    /**
     * @var array Default custom attribute names.
     */
    protected $validationDefaultAttrNames = [];

    /**
     * Whether the model should undergo validation when saving or not.
     *
     * @var bool
     */
    protected $validating = true;

    /**
     * The Validator factory class used for validation.
     *
     * @var \Illuminate\Validation\Factory
     */
    protected $validator;

    /**
     * Boot the trait. Adds an observer class for validating.
     *
     * @return void
     */
    public static function bootValidation()
    {
        if (!property_exists(get_called_class(), 'rules')) {
            throw new Exception(sprintf(
                'You must define a $rules property in %s to use the Validation trait.',
                get_called_class()
            ));
        }

        static::extend(function ($model) {
            $model->bindEvent('model.beforeSave', function () use ($model) {
                return $model->performValidation('saving');
            });

            $model->bindEvent('model.restoring', function () use ($model) {
                return $model->performValidation('restoring');
            });
        });
    }

    /**
     * Returns whether or not the model will attempt to validate
     * itself when saving.
     *
     * @return bool
     */
    public function getValidating()
    {
        return $this->validating;
    }

    /**
     * Set whether the model should attempt validation on saving.
     *
     * @param bool $value
     * @return void
     */
    public function setValidating($value)
    {
        $this->validating = (bool)$value;
    }

    /**
     * Get the casted model attributes.
     *
     * @return array
     */
    public function getValidationAttributes()
    {
        return $this->attributesToArray();
    }

    /**
     * Get the custom validation messages being used by the model.
     *
     * @return array
     */
    public function getValidationMessages()
    {
        return isset($this->validationMessages) ? $this->validationMessages : [];
    }

    /**
     * Get the custom validation attribute names being used by the model.
     *
     * @return array
     */
    public function getValidationAttributeNames()
    {
        return isset($this->validationAttributeNames) ? $this->validationAttributeNames : [];
    }

    /**
     * Get the Validator instance.
     *
     * @return \Illuminate\Validation\Factory
     */
    public function getValidator()
    {
        return $this->validator ?: Validator::getFacadeRoot();
    }

    public function validate()
    {
        $validation = $this->makeValidator($this->getRules());

        $result = $validation->passes();

        $this->setErrors($validation->messages());

        return $result;
    }

    /**
     * Get the global validation rules.
     *
     * @return array
     */
    public function getRules()
    {
        return isset($this->rules) ? $this->rules : [];
    }

    /**
     * Get the validation error messages from the model.
     *
     * @return \Illuminate\Support\MessageBag
     */
    public function getErrors()
    {
        return $this->validationErrors ?: new MessageBag;
    }

    /**
     * Set the error messages.
     *
     * @param \Illuminate\Support\MessageBag $validationErrors
     * @return void
     */
    public function setErrors(MessageBag $validationErrors)
    {
        $this->validationErrors = $validationErrors;
    }

    /**
     * Throw a validation exception.
     *
     * @throws \Igniter\Flame\Database\Traits\Validation
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
        return $this->throwValidationExceptions ?? true;
    }

    /**
     * Returns whether or not the model will add it's unique
     * identifier to the rules when validating.
     *
     * @return bool
     */
    public function getInjectUniqueIdentifier()
    {
        return isset($this->injectUniqueIdentifier) ? $this->injectUniqueIdentifier : true;
    }

    /**
     * Set the model to add unique identifier to rules when performing
     * validation.
     *
     * @param bool $value
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setInjectUniqueIdentifier($value)
    {
        $this->injectUniqueIdentifier = (bool)$value;
    }

    /**
     * Perform validation with the specified ruleset.
     *
     * @param string $event
     * @return bool
     */
    protected function performValidation($event)
    {
        // If the model has validating enabled, perform it.
        if ($this->getValidating()) {
            // Fire the namespaced validating event and prevent validation
            // if it returns a value.
            if ($this->fireValidatingEvents($event)) {
                return;
            }

            if ($this->validate() === false) {
                // Fire the validating failed event.
                $this->fireValidatedEvents('failed');
                $this->fireEvent('model.afterValidate', ['failed']);

                if ($this->getThrowValidationExceptions()) {
                    $this->throwValidationException();
                }

                return false;
            }
            // Fire the validating.passed event.
            $this->fireValidatedEvents('passed');
            $this->fireEvent('model.afterValidate', [$event]);
        }
        else {
            $this->fireValidatedEvents('skipped');
            $this->fireEvent('model.afterValidate', [$event]);
        }
    }

    protected function makeValidator($rules = [])
    {
        $parsed = ValidationHelper::prepareRules($rules);
        $rules = Arr::get($parsed, 'rules', $rules);

        // Get the casted model attributes.
        $attributes = $this->getValidationAttributes();

        if ($this->getInjectUniqueIdentifier()) {
            $rules = $this->injectUniqueIdentifierToRules($rules);
        }

        return $this->getValidator()->make(
            $attributes,
            $rules,
            $this->getValidationMessages(),
            Arr::get($parsed, 'attributes', $this->getValidationAttributeNames())
        );
    }

    /**
     * Fire the namespaced validating event.
     *
     * @param string $event
     * @return mixed
     */
    protected function fireValidatingEvents($event)
    {
        if (Event::until('eloquent.validating: '.get_class($this), [$this, $event]) !== null)
            return true;

        if ($this->fireEvent('model.beforeValidate', [], true) === false)
            return true;

        if ($this->methodExists('beforeValidate')) {
            $this->beforeValidate();
        }
    }

    /**
     * Fire the namespaced post-validation event.
     *
     * @param string $status
     * @return void
     */
    protected function fireValidatedEvents($status)
    {
        Event::dispatch('eloquent.validated: '.get_class($this), [$this, $status]);
    }

    /**
     * If the model already exists and it has unique validations
     * it is going to fail validation unless we also pass it's
     * primary key to the rule so that it may be ignored.
     *
     * This will go through all the rules and append the model's
     * primary key to the unique rules so that the validation
     * will work as expected.
     *
     * @param array $rules
     * @return array
     */
    protected function injectUniqueIdentifierToRules(array $rules)
    {
        foreach ($rules as $field => &$ruleset) {
            // If the ruleset is a pipe-delimited string, convert it to an array.
            $ruleset = is_string($ruleset) ? explode('|', $ruleset) : $ruleset;

            foreach ($ruleset as $key => $rule) {
                // Only treat stringy definitions and leave Rule classes and Closures as-is.
                if (is_string($rule)) {
                    $parameters = explode(':', $rule);
                    $validationRule = array_shift($parameters);

                    if ($method = $this->getUniqueIdentifierInjectorMethod($validationRule)) {
                        $ruleset[$key] = call_user_func_array(
                            [$this, $method],
                            [explode(',', head($parameters)), $field]
                        );
                    }
                    elseif ($validationRule === 'unique' && $this->exists) {
                        $ruleset[$key] = $this->processValidationUniqueRule($rule, $field);
                    }
                    elseif (starts_with($rule, 'required:create') && $this->exists) {
                        unset($ruleset[$key]);
                    }
                    elseif (starts_with($rule, 'required:update') && !$this->exists) {
                        unset($ruleset[$key]);
                    }
                }
            }
        }

        return $rules;
    }

    /**
     * Get the dynamic method name for a unique identifier injector rule if it
     * exists, otherwise return false.
     *
     * @param string $validationRule
     * @return mixed
     */
    protected function getUniqueIdentifierInjectorMethod($validationRule)
    {
        $method = 'prepare'.Str::studly($validationRule).'Rule';

        return method_exists($this, $method) ? $method : false;
    }

    /**
     * Rebuilds the unique validation rule to force for the existing ID
     * @param string $definition
     * @param string $fieldName
     * @return string
     */
    protected function processValidationUniqueRule($definition, $fieldName)
    {
        [
            $table,
            $column,
            $key,
            $keyName,
            $whereColumn,
            $whereValue,
        ] = array_pad(explode(',', $definition), 6, null);

        $table = $this->getConnectionName().'.'.$this->getTable();
        $column = $column ?: $fieldName;
        $key = $keyName ? $this->$keyName : $this->getKey();
        $keyName = $keyName ?: $this->getKeyName();

        $params = [$table, $column, $key, $keyName];

        if ($whereColumn) {
            $params[] = $whereColumn;
        }

        if ($whereValue) {
            $params[] = $whereValue;
        }

        return 'unique:'.implode(',', $params);
    }
}
