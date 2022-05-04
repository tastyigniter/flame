<?php

namespace Igniter\Flame\Database;

use Closure;
use Igniter\Flame\Traits\EventEmitter;
use Igniter\Flame\Traits\ExtendableTrait;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Model Class
 */
class Model extends EloquentModel
{
    use ExtendableTrait;
    use EventEmitter;
    use Concerns\HasAttributes;
    use Concerns\HasRelationships;

    /**
     * @var array Behaviors implemented by this model.
     */
    public $implement;

    /**
     * @var array Make the model's attributes public so actions can modify them.
     */
    protected $attributes = [];

    public $timestamps = false;

    /**
     * The storage format of the model's time columns.
     * @var string
     */
    protected $timeFormat;

    /**
     * The attributes that should be cast to native types.
     * New Custom types: serialize, time
     * @var array
     */
    protected $casts = [];

    /**
     * @var array The array of models booted events.
     */
    protected static $eventsBooted = [];

    /**
     * The built-in, primitive cast types supported by Eloquent.
     *
     * @var string[]
     */
    protected static $primitiveCastTypes = [
        'array',
        'bool',
        'boolean',
        'collection',
        'custom_datetime',
        'date',
        'datetime',
        'decimal',
        'double',
        'encrypted',
        'encrypted:array',
        'encrypted:collection',
        'encrypted:json',
        'encrypted:object',
        'float',
        'immutable_date',
        'immutable_datetime',
        'immutable_custom_datetime',
        'int',
        'integer',
        'json',
        'object',
        'real',
        'serialize',
        'string',
        'timestamp',
        'time',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->bootNicerEvents();
        $this->extendableConstruct();
        $this->fill($attributes);
    }

    /**
     * Create a new model and return the instance.
     *
     * @param array $attributes
     *
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public static function make($attributes = [])
    {
        return new static($attributes);
    }

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     * @param string $sessionKey
     *
     * @return \Illuminate\Database\Eloquent\Model|static
     * @throws \Exception
     */
    public static function create(array $attributes = [], $sessionKey = null)
    {
        $model = new static($attributes);
        $model->save(null, $sessionKey);

        return $model;
    }

    /**
     * Reloads the model attributes from the database.
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function reload()
    {
        if (!$this->exists) {
            $this->syncOriginal();
        }
        elseif ($fresh = static::find($this->getKey())) {
            $this->setRawAttributes($fresh->getAttributes(), true);
        }

        return $this;
    }

    /**
     * Reloads the model relationship cache.
     *
     * @param string $relationName
     *
     * @return void
     */
    public function reloadRelations($relationName = null)
    {
        if (!$relationName) {
            $this->setRelations([]);
        }
        else {
            unset($this->relations[$relationName]);
        }
    }

    /**
     * Extend this object properties upon construction.
     *
     * @param \Closure $callback
     */
    public static function extend(Closure $callback)
    {
        self::extendableExtendCallback($callback);
    }

    /**
     * Bind some nicer events to this model, in the format of method overrides.
     */
    protected function bootNicerEvents()
    {
        $class = get_called_class();

        if (isset(static::$eventsBooted[$class])) {
            return;
        }

        $radicals = ['creat', 'sav', 'updat', 'delet', 'fetch'];
        $hooks = ['before' => 'ing', 'after' => 'ed'];

        foreach ($radicals as $radical) {
            foreach ($hooks as $hook => $event) {
                $eventMethod = $radical.$event; // saving / saved
                $method = $hook.ucfirst($radical); // beforeSave / afterSave
                if ($radical != 'fetch') $method .= 'e';

                self::$eventMethod(function (Model $model) use ($method) {
                    $model->fireEvent('model.'.$method);

                    if ($model->methodExists($method))
                        return $model->$method();
                });
            }
        }

        // Hook to boot events
        static::registerModelEvent('booted', function (Model $model) {
            $model->fireEvent('model.afterBoot');
            if ($model->methodExists('afterBoot'))
                return $model->afterBoot();
        });

        static::$eventsBooted[$class] = true;
    }

    /**
     * Remove all of the event listeners for the model
     * Also flush registry of models that had events booted
     * Allows painless unit testing.
     * @override
     * @return void
     */
    public static function flushEventListeners()
    {
        parent::flushEventListeners();
        static::$eventsBooted = [];
    }

    /**
     * Create a new model instance that is existing.
     *
     * @param array $attributes
     *
     * @param null $connection
     *
     * @return \Illuminate\Database\Eloquent\Model|static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $instance = $this->newInstance([], true);
        if ($instance->fireModelEvent('fetching') === false)
            return $instance;

        $instance->setRawAttributes((array)$attributes, true);

        $instance->setConnection($connection ?: $this->getConnectionName());

        $instance->fireModelEvent('fetched', false);
        $instance->fireModelEvent('retrieved', false);

        return $instance;
    }

    /**
     * Handle the "creating" model event
     */
    protected function beforeCreate()
    {
    }

    /**
     * Handle the "created" model event
     */
    protected function afterCreate()
    {
    }

    /**
     * Handle the "updating" model event
     */
    protected function beforeUpdate()
    {
    }

    /**
     * Handle the "updated" model event
     */
    protected function afterUpdate()
    {
    }

    /**
     * Handle the "saving" model event
     */
    protected function beforeSave()
    {
    }

    /**
     * Handle the "saved" model event
     */
    protected function afterSave()
    {
    }

    /**
     * Handle the "deleting" model event
     */
    protected function beforeDelete()
    {
    }

    /**
     * Handle the "deleted" model event
     */
    protected function afterDelete()
    {
    }

    /**
     * Handle the "fetching" model event
     */
    protected function beforeFetch()
    {
    }

    /**
     * Handle the "fetched" model event
     */
    protected function afterFetch()
    {
    }

    /**
     * Create a new native event for handling beforeFetch().
     *
     * @param Closure|string $callback
     *
     * @return void
     */
    public static function fetching($callback)
    {
        static::registerModelEvent('fetching', $callback);
    }

    /**
     * Create a new native event for handling afterFetch().
     *
     * @param Closure|string $callback
     *
     * @return void
     */
    public static function fetched($callback)
    {
        static::registerModelEvent('fetched', $callback);
    }

    public function setUpdatedAt($value)
    {
        if (!is_null(static::UPDATED_AT))
            $this->{static::UPDATED_AT} = $value;

        return $this;
    }

    public function setCreatedAt($value)
    {
        if (!is_null(static::CREATED_AT))
            $this->{static::CREATED_AT} = $value;

        return $this;
    }

    //
    // Overrides
    //

    /**
     * Get the observable event names.
     * @return array
     */
    public function getObservableEvents()
    {
        return array_merge(
            [
                'retrieved', 'creating', 'created', 'updating', 'updated',
                'deleting', 'deleted', 'forceDeleted', 'saving', 'saved',
                'restoring', 'restored', 'replicating', 'fetching', 'fetched',
            ],
            $this->observables
        );
    }

    protected function isRelationPurgeable($name)
    {
        $purgeableAttributes = [];
        if (method_exists($this, 'getPurgeableAttributes'))
            $purgeableAttributes = $this->getPurgeableAttributes($name);

        return in_array($name, $purgeableAttributes);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Igniter\Flame\Database\Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get the default foreign key name for the model.
     * @return string
     */
    public function getForeignKey()
    {
        return Str::snake(Str::singular(str_replace('_model', '', class_basename($this)))).'_id';
    }

    //
    // Magic
    //

    public function __get($key)
    {
        return $this->extendableGet($key);
    }

    public function __set($name, $value)
    {
        return $this->extendableSet($name, $value);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param $params
     *
     * @return mixed
     */
    public function __call($method, $params)
    {
        if ($this->hasRelation($method))
            return $this->handleRelation($method);

        return $this->extendableCall($method, $params);
    }

    //
    // Pivot
    //

    /**
     * Create a generic pivot model instance.
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param array $attributes
     * @param string $table
     * @param bool $exists
     * @param string|null $using
     * @return \Igniter\Flame\Database\Pivot
     */
    public function newPivot(EloquentModel $parent, array $attributes, $table, $exists, $using = null)
    {
        return $using
            ? $using::fromRawAttributes($parent, $attributes, $table, $exists)
            : new Pivot($parent, $attributes, $table, $exists);
    }

    /**
     * Create a pivot model instance specific to a relation.
     * @param string $relationName
     * @param \Illuminate\Database\Eloquent\Model $parent
     * @param array $attributes
     * @param string $table
     * @param bool $exists
     * @return \Igniter\Flame\Database\Pivot
     */
    public function newRelationPivot($relationName, $parent, $attributes, $table, $exists)
    {
        $definition = $this->getRelationDefinition($relationName);

        if (!is_null($definition) && array_key_exists('pivotModel', $definition)) {
            $pivotModel = $definition['pivotModel'];

            return new $pivotModel($parent, $attributes, $table, $exists);
        }
    }

    //
    // Saving
    //

    /**
     * Save the model to the database. Is used by {@link save()}
     *
     * @param array $options
     *
     * @return bool
     * @throws \Exception
     */
    protected function saveInternal($options = [])
    {
        // Event
        if ($this->fireEvent('model.saveInternal', [$this->attributes, $options], true) === false) {
            return false;
        }

        // Save the record
        $result = parent::save($options);

        // Halted by event
        if ($result === false) {
            return $result;
        }

        //If there is nothing to update, Eloquent will not fire afterSave(),
        // events should still fire for consistency.
        if ($result === null) {
            $this->fireModelEvent('updated', false);
            $this->fireModelEvent('saved', false);
        }

        return $result;
    }

    /**
     * Save the model to the database.
     *
     * @param array $options
     * @param null $sessionKey
     *
     * @return bool
     * @throws \Exception
     */
    public function save(array $options = null, $sessionKey = null)
    {
        return $this->saveInternal(['force' => false] + (array)$options);
    }

    /**
     * Save the model and all of its relationships.
     *
     * @param array $options
     * @param null $sessionKey
     *
     * @return bool
     * @throws \Exception
     */
    public function push($options = null, $sessionKey = null)
    {
        $always = Arr::get($options, 'always', false);

        if (!$this->save(null, $sessionKey) && !$always) {
            return false;
        }

        foreach ($this->relations as $name => $models) {
            if (!$this->isRelationPushable($name)) {
                continue;
            }

            if ($models instanceof EloquentCollection) {
                $models = $models->all();
            }
            elseif ($models instanceof EloquentModel) {
                $models = [$models];
            }
            else {
                $models = (array)$models;
            }

            foreach (array_filter($models) as $model) {
                if (!$model->push(null, $sessionKey)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Pushes the first level of relations even if the parent
     * model has no changes.
     *
     * @param array $options
     * @param string $sessionKey
     *
     * @return bool
     */
    public function alwaysPush($options = null, $sessionKey = null)
    {
        return $this->push(['always' => true] + (array)$options, $sessionKey);
    }

    /**
     * Perform the actual delete query on this model instance.
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $this->performDeleteOnRelations();
        $this->setKeysForSaveQuery($this->newQueryWithoutScopes())->delete();
    }

    /**
     * Locates relations with delete flag and cascades the delete event.
     * @return void
     */
    protected function performDeleteOnRelations()
    {
        $definitions = $this->getRelationDefinitions();
        foreach ($definitions as $type => $relations) {
            /*
             * Hard 'delete' definition
             */
            foreach ($relations as $name => $options) {
                if (!Arr::get($options, 'delete', false)) {
                    continue;
                }

                if (!$relation = $this->{$name}) {
                    continue;
                }

                if ($relation instanceof EloquentModel) {
                    $relation->forceDelete();
                }
                elseif ($relation instanceof EloquentCollection) {
                    $relation->each(function ($model) {
                        $model->forceDelete();
                    });
                }
            }

            /*
             * Belongs-To-Many should clean up after itself always
             */
            if ($type == 'belongsToMany') {
                foreach ($relations as $name => $options) {
                    if (!Arr::get($options, 'delete', Arr::get($options, 'detach', true))) {
                        continue;
                    }

                    $this->{$name}()->detach();
                }
            }
        }
    }
}
