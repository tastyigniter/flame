<?php

namespace Igniter\Flame\Database\Traits;

use Igniter\Flame\Exception\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use System\Helpers\ValidationHelper;
use Watson\Validating\ValidatingTrait;

trait Validation
{
    use ValidatingTrait;

    /**
     * Get the Validator instance.
     *
     * @return \Illuminate\Validation\Factory
     */
    public function getValidator()
    {
        return $this->validator ?: Validator::getFacadeRoot();
    }

    protected function makeValidator($rules = [])
    {
        $parsed = ValidationHelper::prepareRules($rules);
        $rules = Arr::get($parsed, 'rules', $rules);

        // Get the casted model attributes.
        $attributes = $this->getModelAttributes();

        if ($this->getInjectUniqueIdentifier()) {
            $rules = $this->injectUniqueIdentifierToRules($rules);
        }

        $messages = $this->getValidationMessages();

        $validator = $this->getValidator()->make($attributes, $rules, $messages);

        if ($this->getValidationAttributeNames()) {
            $validator->setAttributeNames(Arr::get(
                $parsed, 'attributes', $this->getValidationAttributeNames()
            ));
        }

        return $validator;
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
}
