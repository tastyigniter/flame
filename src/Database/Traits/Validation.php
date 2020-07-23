<?php

namespace Igniter\Flame\Database\Traits;

use Igniter\Flame\Exception\ValidationException;
use Illuminate\Support\Facades\Validator;
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
