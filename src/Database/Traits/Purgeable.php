<?php

namespace Igniter\Flame\Database\Traits;

use Exception;

/**
 * Purgeable model trait
 *
 * Usage:
 **
 * In the model class definition:
 *
 *   use \Igniter\Flame\Database\Traits\Purgeable;
 *
 * You must set attribute names which should not be saved to the database:
 *
 *   protected $purgeable = ['password'];
 */
trait Purgeable
{
    /**
     * @var array List of original attribute values before they were purged.
     */
    protected $originalPurgeableValues = [];

    /**
     * Boot the purgeable trait for a model.
     * @return void
     * @throws \Exception
     */
    public static function bootPurgeable()
    {
        if (!property_exists(get_called_class(), 'purgeable'))
            throw new Exception(sprintf(
                'You must define a $purgeable property in %s to use the Purgeable trait.', get_called_class()
            ));

        /*
         * Remove any purge attributes from the data set
         */
        static::extend(function ($model) {
            $model->bindEvent('model.saveInternal', function () use ($model) {
                $model->purgeAttributes();
            });
        });
    }

    /**
     * Adds an attribute to the purgeable attributes list
     *
     * @param  array|string|null $attributes
     *
     * @return $this
     */
    public function addPurgeable($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();

        $this->purgeable = array_merge($this->purgeable, $attributes);

        return $this;
    }

    /**
     * Removes purged attributes, used before saving.
     *
     * @param null $attributesToPurge
     *
     * @return array Clean attributes
     */
    public function purgeAttributes($attributesToPurge = null)
    {
        if ($attributesToPurge !== null) {
            $purgeable = is_array($attributesToPurge) ? $attributesToPurge : [$attributesToPurge];
        }
        else {
            $purgeable = $this->getPurgeableAttributes();
        }

        $attributes = $this->getAttributes();
        $cleanAttributes = array_diff_key($attributes, array_flip($purgeable));
        $originalAttributes = array_diff_key($attributes, $cleanAttributes);

        if (is_array($this->originalPurgeableValues)) {
            $this->originalPurgeableValues = array_merge($this->originalPurgeableValues, $originalAttributes);
        }
        else {
            $this->originalPurgeableValues = $originalAttributes;
        }

        return $this->attributes = $cleanAttributes;
    }

    /**
     * Returns a collection of fields to be hashed.
     */
    public function getPurgeableAttributes()
    {
        return $this->purgeable;
    }

    /**
     * Returns the original values.
     */
    public function getOriginalPurgeValues()
    {
        return $this->originalPurgeableValues;
    }

    /**
     * Returns the original value of a single attribute.
     *
     * @param $attribute
     *
     * @return null
     */
    public function getOriginalPurgeValue($attribute)
    {
        return isset($this->originalPurgeableValues[$attribute])
            ? $this->originalPurgeableValues[$attribute]
            : null;
    }

    /**
     * Restores the original values.
     */
    public function restorePurgedValues()
    {
        $this->attributes = array_merge($this->getAttributes(), $this->originalPurgeableValues);

        return $this;
    }
}
