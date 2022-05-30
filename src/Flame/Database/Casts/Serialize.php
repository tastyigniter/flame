<?php

namespace Igniter\Flame\Database\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class Serialize implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        return isset($value) ? @unserialize($value) : null;
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return isset($value) ? serialize($value) : null;
    }

    /**
     * Get the serialized representation of the value.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $key
     * @param mixed $value
     * @param array $attributes
     * @return mixed
     */
    public function serialize($model, string $key, $value, array $attributes)
    {
        return (string)$value;
    }
}
