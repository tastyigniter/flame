<?php

namespace Igniter\Flame\Pagic\Concerns;

use Illuminate\Support\Str;

trait GuardsAttributes
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Get the fillable attributes for the model.
     *
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * Set the fillable attributes for the model.
     *
     * @param  array $fillable
     *
     * @return $this
     */
    public function fillable(array $fillable)
    {
        $this->fillable = $fillable;

        return $this;
    }

    /**
     * Get the fillable attributes of a given array.
     *
     * @param  array $attributes
     *
     * @return array
     */
    protected function fillableFromArray(array $attributes)
    {
        if (count($this->getFillable()) > 0) {
            return array_intersect_key($attributes, array_flip(
                array_merge(['fileName'], $this->getFillable())
            ));
        }

        return $attributes;
    }

    /**
     * Determine if the given attribute may be mass assigned.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function isFillable($key)
    {
        // File name is always treated as a fillable attribute.
        if ($key === 'fileName') {
            return TRUE;
        }

        // If the key is in the "fillable" array, we can of course assume that it's
        // a fillable attribute. Otherwise, we will check the guarded array when
        // we need to determine if the attribute is black-listed on the model.
        if (in_array($key, $this->getFillable())) {
            return TRUE;
        }

        return empty($this->getFillable()) &&
            !Str::startsWith($key, '_');
    }
}
