<?php

namespace Igniter\Flame\Database;

use Igniter\Flame\Traits\Singleton;
use Illuminate\Support\Collection;

class PermalinkMaker
{
    use Singleton;

    /** @var \Model */
    protected $model;

    public function slug(Model $model, $force = FALSE)
    {
        $this->setModel($model);

        $attributes = [];
        foreach ($this->model->permalinkable() as $attribute => $config) {
            $config = $this->getConfiguration($config);

            $slug = $this->buildSlug($attribute, $config, $force);
            $this->model->setAttribute($attribute, $slug);
            $attributes[] = $attribute;
        }

        return $this->model->isDirty($attributes);
    }

    /**
     * Get the permalink configuration for the current model,
     * including default values that where not specified.
     *
     * @param array $overrides
     *
     * @return array
     */
    public function getConfiguration($overrides = [])
    {
        static $defaultConfig = null;

        if ($defaultConfig === null) {
            $defaultConfig = [
                'source' => null,
                // The controller name used when building the permalink
                // each permalink are unique to controllers
                'controller' => 'pages',
                'maximumLength' => 250,
                'separator' => '-',
                'generateUnique' => TRUE,
                'generateOnCreate' => TRUE,
                'generateOnUpdate' => FALSE,
                'reserved' => [],
                'uniqueSuffix' => null,
                'includeTrashed' => FALSE,
            ];
        }

        return array_merge($defaultConfig, $overrides);
    }

    /**
     * Build the slug for the given attribute of the current model.
     *
     * @param string $attribute
     * @param array $config
     * @param bool $force
     *
     * @return null|string
     */
    public function buildSlug($attribute, array $config, $force = null)
    {
        $slug = $this->model->getAttribute($attribute);

        if ($force OR $this->needsSlugging($attribute, $config)) {

            $source = $this->getSlugSource($config['source']);

            if ($source OR is_numeric($source)) {
                $slug = $this->generateSlug($source, $config, $attribute);
                $slug = $this->validateSlug($slug, $config, $attribute);
                $slug = $this->makeSlugUnique($slug, $config, $attribute);
            }
        }

        return $slug;
    }

    /**
     * Determines whether the model needs slugging.
     *
     * @param string $attribute
     * @param array $config
     *
     * @return bool
     */
    protected function needsSlugging($attribute, array $config)
    {
        if ($config['generateOnUpdate'] === TRUE
            OR empty($this->model->getAttributeValue($attribute))
        )
            return TRUE;

        if ($this->model->isDirty($attribute))
            return FALSE;

        return ($config['generateOnCreate'] === TRUE AND !$this->model->exists);
    }

    /**
     * Get the string that should be used as base for the slug.
     *
     * @param $from
     *
     * @return mixed|string
     */
    protected function getSlugSource($from)
    {
        if (is_null($from)) {
            return $this->model->__toString();
        }

        if (is_callable($from)) {
            return call_user_func($from, $this);
        }

        $sourceStrings = array_map(function ($fieldName) {
            $value = data_get($this->model, $fieldName);

            return (is_bool($value)) ? (int)$value : $value;
        }, (array)$from);

        return implode(' ', $sourceStrings);
    }

    /**
     * Generate a slug from the given source string.
     *
     * @param string $source
     * @param array $config
     * @param string $attribute
     *
     * @return string
     */
    protected function generateSlug($source, array $config, $attribute)
    {
        $separator = $config['separator'];
        $maxLength = $config['maximumLength'];

        $slug = str_slug($source, $separator);

        if (is_string($slug) && $maxLength) {
            $slug = mb_substr($slug, 0, $maxLength);
        }

        return $slug;
    }

    /**
     * Checks if the slug should be unique, and makes it so if needed.
     *
     * @param string $slug
     * @param array $config
     * @param string $attribute
     *
     * @return string
     * @throws \UnexpectedValueException
     */
    protected function makeSlugUnique($slug, array $config, $attribute)
    {
        if (!$config['generateUnique']) {
            return $slug;
        }

        $separator = $config['separator'];

        // find all models where the slug is like the current one
        $list = $this->getExistingSlugs($slug, $attribute, $config);

        // if ...
        // 	a) the list is empty, or
        // 	b) our slug isn't in the list
        // ... we are okay
        if ($list->count() === 0 OR $list->contains($slug) === FALSE) {
            return $slug;
        }

        // if our slug is in the list, but
        // 	a) it's for our model, or
        //  b) it looks like a suffixed version of our slug
        // ... we are also okay (use the current slug)
        if ($list->has($this->model->getKey())) {
            $currentSlug = $list->get($this->model->getKey());
            if ($currentSlug === $slug OR strpos($currentSlug, $slug) === 0)
                return $currentSlug;
        }

        return $slug.$separator.$list->count();
    }

    /**
     * Get all existing slugs that are similar to the given slug.
     *
     * @param string $slug
     * @param string $attribute
     * @param array $config
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getExistingSlugs($slug, $attribute, array $config)
    {
        $includeTrashed = $config['includeTrashed'];

        $query = $this->model->newQuery()->findSimilarSlugs($attribute, $config, $slug);

        // Use the model scope to find similar slugs
        if (method_exists($this->model, 'scopeWithUniqueSlugConstraints')) {
            $this->model->withUniqueSlugConstraints($query, $attribute, $config, $slug);
        }

        // Include trashed models if required
        if ($includeTrashed AND $this->usesSoftDeleting()) {
            $query->withTrashed();
        }

        // Get the list of all matching slugs
        return $query->pluck($attribute, $this->model->getKeyName());
    }

    /**
     * Checks that the given slug is not a reserved word.
     *
     * @param string $slug
     * @param array $config
     * @param string $attribute
     *
     * @return string
     */
    protected function validateSlug($slug, array $config, $attribute)
    {
        $separator = $config['separator'];
        $reserved = $config['reserved'];
        if ($reserved === null) {
            return $slug;
        }

        // check for reserved names
        if ($reserved instanceof \Closure) {
            $reserved = $reserved($this->model);
        }

        if (is_array($reserved)) {
            if (in_array($slug, $reserved)) {
                $method = $config['uniqueSuffix'];
                if ($method === null) {
                    $suffix = $this->generateSuffix($slug, $separator, collect($reserved));
                }
                elseif (is_callable($method)) {
                    $suffix = $method($slug, $separator, collect($reserved));
                }
                else {
                    throw new \UnexpectedValueException('Sluggable "uniqueSuffix" for '.get_class($this->model).':'.$attribute.' is not null, or a closure.');
                }

                return $slug.$separator.$suffix;
            }

            return $slug;
        }

        throw new \UnexpectedValueException('Sluggable "reserved" for '.get_class($this->model).':'.$attribute.' is not null, an array, or a closure that returns null/array.');
    }

    /**
     * Generate a unique suffix for the given slug (and list of existing, "similar" slugs.
     *
     * @param string $slug
     * @param string $separator
     * @param \Illuminate\Support\Collection $list
     *
     * @return string
     */
    protected function generateSuffix($slug, $separator, Collection $list)
    {
        $len = strlen($slug.$separator);

        // If the slug already exists, but belongs to
        // our model, return the current suffix.
        if ($list->search($slug) === $this->model->getKey()) {
            $suffix = explode($separator, $slug);

            return end($suffix);
        }

        $list->transform(function ($value, $key) use ($len) {
            return (int)substr($value, $len);
        });

        // find the highest value and return one greater.
        return $list->max() + 1;
    }

    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }
}