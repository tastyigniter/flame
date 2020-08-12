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

    /**
     * Resolve a new Validator instance.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @return \Igniter\Flame\Validation\Validator
     */
    protected function resolve(array $data, array $rules, array $messages, array $customAttributes)
    {
        if (is_null($this->resolver)) {
            return new Validator($this->translator, $data, $rules, $messages, $customAttributes);
        }

        return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $customAttributes);
    }
}
