<?php

namespace Igniter\Flame\Pagic;

use ArrayAccess;
use BadMethodCallException;
use Exception;
use Igniter\Flame\Pagic\Source\SourceResolverInterface;
use Igniter\Flame\Support\Extendable;
use Igniter\Flame\Traits\EventEmitter;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * Model class.
 */
class Model extends Extendable implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{
    use Concerns\HidesAttributes;
    use Concerns\HasAttributes;
    use Concerns\GuardsAttributes;
    use Concerns\HasEvents;
    use Concerns\ManagesCache;
    use EventEmitter;

    /**
     * The source resolver instance.
     * @var \Igniter\Flame\Pagic\Source\SourceResolverInterface
     */
    protected static $resolver;

    public static $dispatcher;

    /**
     * The array of booted models.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of booted events.
     *
     * @var array
     */
    protected static $eventsBooted = [];

    protected $source;

    /**
     * @var string The directory name associated with the model, eg: _pages.
     */
    protected $dirName;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * @var array List of attribute names which are not considered "settings".
     */
    protected $purgeable = [];

    /**
     * Indicates if the model exists.
     * @var bool
     */
    public $exists = FALSE;

    /**
     * @var array Allowable file extensions.
     */
    protected $allowedExtensions = ['php'];

    /**
     * @var string Default file extension.
     */
    protected $defaultExtension = 'php';

    /**
     * @var int The maximum allowed path nesting level. The default value is 2,
     * meaning that files can only exist in the root directory, or in a
     * subdirectory. Set to null if any level is allowed.
     */
    protected $maxNesting = 2;

    /**
     * Create a new Halcyon model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();

        $this->bootNicerEvents();

        parent::__construct();

        $this->syncOriginal();

        $this->fill($attributes);
    }

    /**
     * Check if the model needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = TRUE;

            $this->fireModelEvent('booting', FALSE);

            static::boot();

            $this->fireModelEvent('booted', FALSE);
        }
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the model.
     *
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;

        foreach (class_uses_recursive($class) as $trait) {
            if (method_exists($class, $method = 'boot'.class_basename($trait))) {
                forward_static_call([$class, $method]);
            }
        }
    }

    /**
     * Clear the list of booted models so they will be re-booted.
     *
     * @return void
     */
    public static function clearBootedModels()
    {
        static::$booted = [];
    }

    /**
     * @param $source
     *
     * @return self|\Igniter\Flame\Pagic\Finder
     */
    public static function on($source)
    {
        $instance = new static;

        $instance->setSource($source);

        return $instance;
    }

    /**
     * @param string|null $source
     *
     * @return \Igniter\Flame\Pagic\Source\SourceInterface
     */
    public static function resolveSource($source = null)
    {
        return static::$resolver->source($source);
    }

    /**
     * Get the source resolver instance.
     * @return \Igniter\Flame\Pagic\Source\SourceResolverInterface
     */
    public static function getSourceResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the source resolver instance.
     *
     * @param \Igniter\Flame\Pagic\Source\SourceResolverInterface $resolver
     *
     * @return void
     */
    public static function setSourceResolver(SourceResolverInterface $resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the source resolver for models.
     * @return void
     */
    public static function unsetSourceResolver()
    {
        static::$resolver = null;
    }

    /**
     * Create a collection of models from plain arrays.
     *
     * @param array $items
     * @param string|null $source
     *
     * @return \Illuminate\Support\Collection
     */
    public static function hydrate(array $items, $source = null)
    {
        $instance = new static;
        $instance->setSource($source);

        $items = array_map(function ($item) use ($instance) {
            return $instance->newFromFinder($item);
        }, $items);

        return $instance->newCollection($items);
    }

    /**
     * Save a new model and return the instance.
     *
     * @param array $attributes
     *
     * @return static
     */
    public static function create(array $attributes = [])
    {
        $model = new static($attributes);

        $model->save();

        return $model;
    }

    /**
     * Begin querying the model.
     *
     * @return \Igniter\Flame\Pagic\Finder
     */
    public static function query()
    {
        return (new static)->newFinder();
    }

    /**
     * Get all of the models from the source.
     *
     * @return \Illuminate\Support\Collection|static[]
     */
    public static function all()
    {
        $instance = new static;

        return $instance->newFinder()->get();
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param array $attributes
     *
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }

        return $this;
    }

    /**
     * @return \Igniter\Flame\Pagic\Source\SourceInterface
     */
    public function getSource()
    {
        return static::resolveSource($this->source);
    }

    /**
     * @param $source
     *
     * @return \Igniter\Flame\Pagic\Model
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get the current source name for the model.
     * @return string
     */
    public function getSourceName()
    {
        return $this->source;
    }

    /**
     * Returns the file name without the extension.
     * @return string
     */
    public function getBaseFileNameAttribute()
    {
        $pos = strrpos($this->fileName, '.');
        if ($pos === FALSE) {
            return $this->fileName;
        }

        return substr($this->fileName, 0, $pos);
    }

    /**
     * The settings is attribute contains everything that should
     * be saved to the settings area.
     * @return array
     */
    public function getSettingsAttribute()
    {
        $except = [
            'fileName',
            'components',
            'content',
            'markup',
            'mTime',
            'code',
        ];

        return array_except($this->attributes, array_merge($except, $this->purgeable));
    }

    /**
     * Filling the settings should merge it with attributes.
     *
     * @param mixed $value
     */
    public function setSettingsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes = array_merge($this->attributes, $value);
        }
    }

    /**
     * File name should always contain an extension.
     * @param mixed $value
     */
    public function setFileNameAttribute($value)
    {
        $fileName = trim($value);

        if (strlen($fileName) && !strlen(pathinfo($value, PATHINFO_EXTENSION))) {
            $fileName .= '.'.$this->defaultExtension;
        }

        $this->attributes['fileName'] = $fileName;
    }

    /**
     * Returns the directory name corresponding to the object type.
     * For pages the directory name is "_pages", for layouts - "_layouts", etc.
     * @return string
     */
    public function getTypeDirName()
    {
        return $this->dirName;
    }

    /**
     * Returns the allowable file extensions supported by this model.
     * @return array
     */
    public function getAllowedExtensions()
    {
        return $this->allowedExtensions;
    }

    /**
     * Returns the maximum directory nesting allowed by this template.
     * @return int
     */
    public function getMaxNesting()
    {
        return $this->maxNesting;
    }

    /**
     * Returns the base file name and extension. Applies a default extension, if none found.
     *
     * @param string $fileName
     *
     * @return array
     */
    public function getFileNameParts($fileName = null)
    {
        if ($fileName === null) {
            $fileName = $this->fileName;
        }

        if (!strlen($extension = pathinfo($fileName, PATHINFO_EXTENSION))) {
            $extension = $this->defaultExtension;
            $baseFile = $fileName;
        }
        else {
            $pos = strrpos($fileName, '.');
            $baseFile = substr($fileName, 0, $pos);
        }

        return [$baseFile, $extension];
    }

    /**
     * Get a new file finder for the object
     * @return \Igniter\Flame\Pagic\Finder
     */
    public function newFinder()
    {
        $source = $this->getSource();

        $finder = new Finder($source, $source->getProcessor());

        return $finder->setModel($this);
    }

    /**
     * Create a new Collection instance.
     *
     * @param array $models
     *
     * @return \Illuminate\Support\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Create a new instance of the given model.
     *
     * @param array $attributes
     * @param bool $exists
     *
     * @return static
     */
    public function newInstance($attributes = [], $exists = FALSE)
    {
        // This method just provides a convenient way for us to generate fresh model
        // instances of this current model. It is particularly useful during the
        // hydration of new objects via the Pagic query finder instances.
        $model = new static((array)$attributes);

        $model->exists = $exists;

        return $model;
    }

    /**
     * Create a new model instance that is existing.
     *
     * @param array $attributes
     * @param string|null $source
     *
     * @return static
     */
    public function newFromFinder($attributes = [], $source = null)
    {
        $instance = $this->newInstance([], TRUE);

        $instance->setRawAttributes((array)$attributes, TRUE);

        $instance->setSource($source ?: $this->source);

        $instance->fireModelEvent('retrieved', FALSE);

        return $instance;
    }

    /**
     * Update the model in the database.
     *
     * @param array $attributes
     *
     * @return bool|int
     */
    public function update(array $attributes = [])
    {
        if (!$this->exists) {
            return $this->newFinder()->update($attributes);
        }

        return $this->fill($attributes)->save();
    }

    /**
     * Save the model to the source.
     *
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        return $this->saveInternal(['force' => FALSE] + $options);
    }

    /**
     * Save the model to the database. Is used by {@link save()} and {@link forceSave()}.
     *
     * @param array $options
     *
     * @return bool
     */
    public function saveInternal(array $options = [])
    {
        // Event
        if ($this->fireEvent('model.saveInternal', [$this->attributes, $options], TRUE) === FALSE) {
            return FALSE;
        }

        $query = $this->newFinder();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === FALSE) {
            return FALSE;
        }

        if ($this->exists) {
            $saved = $this->performUpdate($query, $options);
        }
        else {
            $saved = $this->performInsert($query, $options);
        }

        if ($saved) {
            $this->finishSave($options);
        }

        return $saved;
    }

    /**
     * Finish processing on a successful save operation.
     *
     * @param array $options
     *
     * @return void
     */
    protected function finishSave(array $options)
    {
        $this->fireModelEvent('saved', FALSE);

        $this->mTime = $this->newFinder()->lastModified();

        $this->syncOriginal();
    }

    /**
     * Perform a model update operation.
     *
     * @param \Igniter\Flame\Pagic\Finder $query
     * @param array $options
     *
     * @return bool
     */
    protected function performUpdate(Finder $query, array $options = [])
    {
        $dirty = $this->getDirty();

        if (count($dirty) > 0) {
            // If the updating event returns false, we will cancel the update operation so
            // developers can hook Validation systems into their models and cancel this
            // operation if the model does not pass validation. Otherwise, we update.
            if ($this->fireModelEvent('updating') === FALSE) {
                return FALSE;
            }

            $dirty = $this->getDirty();

            if (count($dirty) > 0) {
                $query->update($dirty);

                $this->fireModelEvent('updated', FALSE);
            }
        }

        return TRUE;
    }

    /**
     * Perform a model insert operation.
     *
     * @param \Igniter\Flame\Pagic\Finder $query
     * @param array $options
     *
     * @return bool
     */
    protected function performInsert(Finder $query, array $options = [])
    {
        if ($this->fireModelEvent('creating') === FALSE) {
            return FALSE;
        }

        // Ensure the settings attribute is passed through so this distinction
        // is recognised, mainly by the processor.
        $attributes = $this->attributesToArray();

        $query->insert($attributes);

        // We will go ahead and set the exists property to true, so that it is set when
        // the created event is fired, just in case the developer tries to update it
        // during the event. This will allow them to do so and run an update here.
        $this->exists = TRUE;

        $this->fireModelEvent('created', FALSE);

        return TRUE;
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        if (is_null($this->fileName)) {
            throw new Exception('No file name (fileName) defined on model.');
        }

        if ($this->exists) {
            if ($this->fireModelEvent('deleting') === FALSE) {
                return FALSE;
            }

            $this->performDeleteOnModel();

            $this->exists = FALSE;

            // Once the model has been deleted, we will fire off the deleted event so that
            // the developers may hook into post-delete operations. We will then return
            // a boolean true as the delete is presumably successful on the database.
            $this->fireModelEvent('deleted', FALSE);

            return TRUE;
        }
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $this->newFinder()->delete();
    }

    /**
     * Convert the model to its string representation.
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        }
        catch (BadMethodCallException $ex) {
            $finder = $this->newFinder();

            return call_user_func_array([$finder, $method], $parameters);
        }
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$instance, $method], $parameters);
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]) OR
            (
                $this->hasGetMutator($key) AND
                !is_null($this->getAttribute($key))
            );
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $key
     *
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Determine if the given attribute exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Get the value for a given offset.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Set the value for a given offset.
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    /**
     * Unset the value for a given offset.
     *
     * @param mixed $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    /**
     * Get the instance as an array.
     * @return array
     */
    public function toArray()
    {
        return $this->attributesToArray();
    }

    /**
     * Convert the model instance to JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
