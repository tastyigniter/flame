<?php

namespace Igniter\Flame\Database\Traits;

use Exception;
use Igniter\Flame\Database\PermalinkMaker;
use Model;

/**
 * HasPermalink model trait
 * Usage:
 **
 * In the model class definition:
 *   use \Igniter\Flame\Database\Traits\HasPermalink;
 * You can change the slug field used by declaring:
 *   protected $permalink = ['permalink_slug' => ['source' => 'name']];
 */
trait HasPermalink
{
    /**
     * Boot the sortable trait for this model.
     * @return void
     * @throws \Exception
     */
    public static function bootHasPermalink()
    {
        if (!property_exists(get_called_class(), 'permalinkable')) {
            throw new Exception(sprintf(
                'You must define a $permalinkable property in %s to use the HasPermalink trait.', get_called_class()
            ));
        }

        static::saving(function (Model $model) {
            $model->generatePermalinkOnSave();
        });
    }

    /**
     * Handle adding permalink slug on model update.
     */
    protected function generatePermalinkOnSave()
    {
        $this->getPermalinkMaker()->slug($this);
    }

    /**
     * Primary slug column of this model.
     * @return string
     */
    public function getSlugKeyName()
    {
        if (property_exists($this, 'slugKeyName')) {
            return $this->slugKeyName;
        }

        $config = $this->permalinkable();
        $name = reset($config);
        $key = key($config);

        // check for short configuration
        if ($key === 0) {
            return $name;
        }

        return $key;
    }

    /**
     * Primary slug value of this model.
     * @return string
     */
    public function getSlugKey()
    {
        return $this->getAttribute($this->getSlugKeyName());
    }

    /**
     * Query scope for finding a model by its primary slug.
     *
     * @param $query
     * @param string $slug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereSlug($query, $slug)
    {
        return $query->where($this->getSlugKeyName(), $slug);
    }

    /**
     * Query scope for finding "similar" slugs, used to determine uniqueness.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $attribute
     * @param array $config
     * @param string $slug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindSimilarSlugs($query, $attribute, array $config, $slug)
    {
        $separator = $config['separator'];

        return $query->where($attribute, $slug)
                     ->orWhere($attribute, 'LIKE', $slug.$separator.'%');
    }

    public function findSlug($slug, $columns = ['*'])
    {
        return $this->whereSlug($slug)->first($columns);
    }

    public function permalinkable()
    {
        $result = [];
        $permalinkable = isset($this->permalinkable) ? $this->permalinkable : [];
        foreach ($permalinkable as $attribute => $config) {
            if (is_numeric($attribute)) {
                $attribute = $config;
                $config = [];
            }

            $result[$attribute] = $config;
        }

        return $result;
    }

    /**
     * @return \Igniter\Flame\Database\PermalinkMaker
     */
    protected function getPermalinkMaker()
    {
        return PermalinkMaker::instance();
    }
}