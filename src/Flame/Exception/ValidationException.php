<?php

namespace Igniter\Flame\Exception;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

class ValidationException extends Exception
{
    /**
     * @var array Collection of invalid fields.
     */
    protected $fields;

    /**
     * @var \Illuminate\Support\MessageBag The message bag instance containing validation error messages
     */
    protected $errors;

    /**
     * The model with validation errors.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Create a new validation exception instance.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function __construct($validation, Model $model = null)
    {
        parent::__construct();

        if (is_null($validation)) {
            return;
        }

        if ($validation instanceof Validator) {
            $this->errors = $validation->messages();
        }
        elseif (is_array($validation)) {
            $this->errors = new MessageBag($validation);
        }
        else {
            throw new InvalidArgumentException('ValidationException constructor requires instance of Validator or array');
        }

        $this->evalErrors();

        $this->model = $model;
    }

    /**
     * Get the mdoel with validation errors.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function model()
    {
        return $this->model;
    }

    /**
     * Get the mdoel with validation errors.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model();
    }

    /**
     * Evaluate errors.
     */
    protected function evalErrors()
    {
        foreach ($this->errors->getMessages() as $field => $messages) {
            $this->fields[$field] = $messages;
        }

        $this->message = $this->errors->first();
    }

    /**
     * Returns directly the message bag instance with the model's errors.
     * @return \Illuminate\Support\MessageBag
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Returns invalid fields.
     */
    public function getFields()
    {
        return $this->fields;
    }
}
