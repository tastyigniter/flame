<?php

namespace Igniter\Flame\Pagic;

use ArrayAccess;
use BadMethodCallException;
use Igniter\Flame\Pagic\Source\SourceResolverInterface;
use Igniter\Flame\Support\Extendable;
use Igniter\Flame\Traits\EventEmitter;
use Illuminate\Contracts\Events\Dispatcher;
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
     * Indicates if the model was inserted during the current request lifecycle.
     * @var bool
     */
    public $wasRecentlyCreated = FALSE;

    /**
     * @var array Allowable file extensions.
     */
    protected $allowedExtensions = ['php'];

    protected $defaultExtension = 'php';

    /**
     * Create a new Halcyon model instance.
     *
     * @param  array $attributes
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
     * @param  string|null $source
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
     * @param  \Igniter\Flame\Pagic\Source\SourceResolverInterface $resolver
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
     * @param  array $items
     * @param  string|null $source
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
     * Fill the model with an array of attributes.
     *
     * @param  array $attributes
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
     * @param  array $models
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
     * @param  array $attributes
     * @param  bool $exists
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
     * @param  array $attributes
     * @param  string|null $source
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
     * @param  string $key
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
     * @param  string $key
     * @param  mixed $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        var_dump($key);
        $this->setAttribute($key, $value);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string $method
     * @param  array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        } catch (BadMethodCallException $ex) {
            $finder = $this->newFinder();

            return call_user_func_array([$finder, $method], $parameters);
        }
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string $method
     * @param  array $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$instance, $method], $parameters);
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     *
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     *
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     *
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }

    /**
     * Get the instance as an array.
     * @return array
     */
    public function toArray()
    {
        // TODO: Implement toArray() method.
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        // TODO: Implement toJson() method.
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }
}
