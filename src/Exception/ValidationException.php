<?php

namespace Igniter\Flame\Exception;

use Illuminate\Database\Eloquent\Model;

class ValidationException extends \October\Rain\Exception\ValidationException
{
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
    public function __construct($validator, Model $model = null)
    {
        parent::__construct($validator);

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
}
