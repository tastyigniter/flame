<?php

namespace Igniter\Flame\Validation;

use Illuminate\Support\Facades\Event;

class Factory extends \Illuminate\Validation\Factory
{
    public function make(array $data, array $rules, array $messages = [], array $customAttributes = [])
    {
        $args = (object)compact('data', 'rules', 'messages', 'customAttributes');

        Event::dispatch('validator.beforeMake', [$args]);

        return parent::make(
            $args->data,
            $args->rules,
            $args->messages,
            $args->customAttributes
        );
    }
}