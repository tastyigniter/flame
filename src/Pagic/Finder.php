<?php

namespace Igniter\Flame\Pagic;

use BadMethodCallException;
use Igniter\Flame\Pagic\Exception\InvalidExtensionException;
use Igniter\Flame\Pagic\Exception\InvalidFileNameException;
use Igniter\Flame\Pagic\Exception\MissingFileNameException;
use Igniter\Flame\Pagic\Processors\Processor;
use Igniter\Flame\Pagic\Source\MemorySource;
use Igniter\Flame\Pagic\Source\SourceInterface;
use Illuminate\Support\Collection;

class Finder
{
    /**
     * The source instance.
     * @var \Igniter\Flame\Pagic\Source\SourceInterface
     */
    protected $source;

    /**
     * The source query processor instance.
     * @var \Igniter\Flame\Pagic\Processors\Processor
     */
    protected $processor;

    /**
     * The model being queried.
     * @var \Igniter\Flame\Pagic\Model
     */
    protected $model;

    /**
     * Filter by these file extensions.
     * @var array
     */
    public $extensions;

    /**
     * The columns that should be returned.
     * @var array
     */
    public $columns;

    /**
     * The directory name which the finder is targeting.
     * @var string
     */
    public $in;

    /**
     * Query should pluck a single record.
     * @var bool
     */
    public $select;

    /**
     * Match files using the specified pattern.
     * @var string
     */
    public $fileMatch;

    /**
     * The orderings for the query.
     * @var array
     */
    public $orders;

    /**
     * The maximum number of records to return.
     * @var int
     */
    public $limit;

    /**
     * The number of records to skip.
     * @var int
     */
    public $offset;

    /**
     * The key that should be used when caching the query.
     * @var string
     */
    protected $cacheKey;

    /**
     * The number of seconds to cache the query.
     * @var int
     */
    protected $cacheSeconds;

    /**
     * The tags for the query cache.
     * @var array
     */
    protected $cacheTags;

    /**
     * The cache driver to be used.
     * @var string
     */
    protected $cacheDriver;

    /**
     * Internal variable to specify if the record was loaded from cache.
     * @var bool
     */
    protected $loadedFromCache = FALSE;

    /**
     * Create a new query finder instance.
     *
     * @param \Igniter\Flame\Pagic\Source\SourceInterface $source
     * @param \Igniter\Flame\Pagic\Processors\Processor $processor
     */
    public function __construct(SourceInterface $source, Processor $processor)
    {
        $this->source = $source;
        $this->processor = $processor;
    }

    /**
     * Switches mode to select a single template by its name.
     *
     * @param  string $fileName
     *
     * @return $this
     */
    public function whereFileName($fileName)
    {
        $this->select = $this->model->getFileNameParts($fileName);

        return $this;
    }

    /**
     * Set the directory name which the finder is targeting.
     *
     * @param  string $dirName
     *
     * @return $this
     */
    public function in($dirName)
    {
        $this->in = $dirName;

        return $this;
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param  int $value
     *
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param  int $value
     *
     * @return \Igniter\Flame\Pagic\Finder|static
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int $value
     *
     * @return $this
     */
    public function limit($value)
    {
        if ($value >= 0) {
            $this->limit = $value;
        }

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param  int $value
     *
     * @return \Igniter\Flame\Pagic\Finder|static
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Find a single template by its file name.
     *
     * @param  string $fileName
     *
     * @return mixed|static
     */
    public function find($fileName)
    {
        return $this->whereFileName($fileName)->first();
    }

    /**
     * Execute the query and get the first result.
     * @return mixed|static
     */
    public function first()
    {
        return $this->limit(1)->get()->first();
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array $columns
     *
     * @return Collection|static[]
     */
    public function get($columns = ['*'])
    {
        if (!is_null($this->cacheSeconds)) {
            $results = $this->getCached($columns);
        }
        else {
            $results = $this->getFresh($columns);
        }

        $models = $this->getModels($results ?: []);

        return $this->model->newCollection($models);
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column
     * @param  string $key
     *
     * @return array|\Illuminate\Support\Collection
     */
    public function lists($column, $key = null)
    {
        $select = is_null($key) ? [$column] : [$column, $key];

        $collection = $this->get($select);

        return $collection->pluck($column, $key);
    }

    /**
     * Insert a new record into the source.
     *
     * @param  array $values
     *
     * @return bool
     */
    public function insert(array $values)
    {
        if (empty($values)) {
            return TRUE;
        }

        $this->validateFileName();

        [$name, $extension] = $this->model->getFileNameParts();

        $result = $this->processor->processInsert($this, $values);

        return $this->source->insert(
            $this->model->getTypeDirName(),
            $name,
            $extension,
            $result
        );
    }

    /**
     * Update a record in the source.
     *
     * @param  array $values
     *
     * @return int
     */
    public function update(array $values)
    {
        $this->validateFileName();

        [$name, $extension] = $this->model->getFileNameParts();

        $result = $this->processor->processUpdate($this, $values);

        $oldName = $oldExtension = null;

        if ($this->model->isDirty('fileName')) {
            [$oldName, $oldExtension] = $this->model->getFileNameParts(
                $this->model->getOriginal('fileName')
            );
        }

        return $this->source->update(
            $this->model->getTypeDirName(),
            $name,
            $extension,
            $result,
            $oldName,
            $oldExtension
        );
    }

    /**
     * Delete a source from the filesystem.
     *
     * @return bool
     */
    public function delete()
    {
        $this->validateFileName();

        [$name, $extension] = $this->model->getFileNameParts();

        return $this->source->delete(
            $this->model->getTypeDirName(),
            $name,
            $extension
        );
    }

    /**
     * Returns the last modified time of the object.
     *
     * @return int
     */
    public function lastModified()
    {
        $this->validateFileName();

        [$name, $extension] = $this->model->getFileNameParts();

        return $this->source->lastModified(
            $this->model->getTypeDirName(),
            $name,
            $extension
        );
    }

    /**
     * Execute the query as a fresh "select" statement.
     *
     * @param  array $columns
     *
     * @return Collection|static[]
     */
    public function getFresh($columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        $processCmd = $this->select ? 'processSelect' : 'processSelectAll';

        return $this->processor->{$processCmd}($this, $this->runSelect());
    }

    /**
     * Run the query as a "select" statement against the source.
     * @return array
     */
    protected function runSelect()
    {
        if ($this->select) {
            [$name, $extension] = $this->select;

            return $this->source->select($this->in, $name, $extension);
        }

        return $this->source->selectAll($this->in, [
            'columns' => $this->columns,
            'extensions' => $this->extensions,
        ]);
    }

    /**
     * Set a model instance for the model being queried.
     *
     * @param \Igniter\Flame\Pagic\Model $model
     *
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;

        $this->extensions = $this->model->getAllowedExtensions();

        $this->in($this->model->getTypeDirName());

        return $this;
    }

    /**
     * Get the hydrated models.
     *
     * @param  array $results
     *
     * @return \Igniter\Flame\Pagic\Model[]
     */
    public function getModels(array $results)
    {
        $source = $this->model->getSourceName();

        $models = $this->model->hydrate($results, $source);

        // Flag the models as loaded from cache, then reset the internal property.
        if ($this->loadedFromCache) {
            $models->each(function (Model $model) {
                $model->setLoadedFromCache($this->loadedFromCache);
            });

            $this->loadedFromCache = FALSE;
        }

        return $models->all();
    }

    /**
     * Get the model instance being queried.
     * @return \Igniter\Flame\Pagic\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    //
    // Validation
    //

    /**
     * Validate the supplied filename, extension and path.
     *
     * @param string $fileName
     *
     * @return bool
     */
    protected function validateFileName($fileName = null)
    {
        if ($fileName === null) {
            $fileName = $this->model->fileName;
        }

        if (!strlen($fileName)) {
            throw (new MissingFileNameException)->setModel($this->model);
        }

        if (!$this->validateFileNamePath($fileName, $this->model->getMaxNesting())) {
            throw (new InvalidFileNameException)->setInvalidFileName($fileName);
        }

        $this->validateFileNameExtension($fileName, $this->model->getAllowedExtensions());

        return TRUE;
    }

    /**
     * Validates whether a file has an allowed extension.
     *
     * @param string $fileName Specifies a path to validate
     * @param array $allowedExtensions A list of allowed file extensions
     *
     * @return void
     */
    protected function validateFileNameExtension($fileName, $allowedExtensions)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (strlen($extension) AND !in_array($extension, $allowedExtensions)) {
            throw (new InvalidExtensionException)
                ->setInvalidExtension($extension)
                ->setAllowedExtensions($allowedExtensions);
        }
    }

    /**
     * Validates a template path.
     * Template directory and file names can contain only alphanumeric symbols, dashes and dots.
     *
     * @param string $filePath Specifies a path to validate
     * @param integer $maxNesting Specifies the maximum allowed nesting level
     *
     * @return bool
     */
    protected function validateFileNamePath($filePath, $maxNesting = 2)
    {
        if (strpos($filePath, '..') !== FALSE) {
            return FALSE;
        }

        if (strpos($filePath, './') !== FALSE || strpos($filePath, '//') !== FALSE) {
            return FALSE;
        }

        $segments = explode('/', $filePath);
        if ($maxNesting !== null AND count($segments) > $maxNesting) {
            return FALSE;
        }

        foreach ($segments as $segment) {
            if (!preg_match('/^[a-z0-9\_\-\.\/]+$/i', $segment)) {
                return FALSE;
            }
        }

        return TRUE;
    }

    //
    // Caching
    //

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int $seconds
     * @param  string $key
     *
     * @return $this
     */
    public function remember($seconds, $key = null)
    {
        [$this->cacheSeconds, $this->cacheKey] = [$seconds, $key];

        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @param  string $key
     *
     * @return $this
     */
    public function rememberForever($key = null)
    {
        return $this->remember(-1, $key);
    }

    /**
     * Indicate that the results, if cached, should use the given cache tags.
     *
     * @param  array|mixed $cacheTags
     *
     * @return $this
     */
    public function cacheTags($cacheTags)
    {
        $this->cacheTags = $cacheTags;

        return $this;
    }

    /**
     * Indicate that the results, if cached, should use the given cache driver.
     *
     * @param  string $cacheDriver
     *
     * @return $this
     */
    public function cacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;

        return $this;
    }

    /**
     * Execute the query as a cached "select" statement.
     *
     * @param  array $columns
     *
     * @return array
     */
    public function getCached($columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        $key = $this->getCacheKey();

        if (array_key_exists($key, MemorySource::$cache)) {
            return MemorySource::$cache[$key];
        }

        $seconds = $this->cacheSeconds;
        $cache = $this->getCache();
        $callback = $this->getCacheCallback($columns);
        $isNewCache = !$cache->has($key);

        // If the "seconds" value is less than zero, we will use that as the indicator
        // that the value should be remembered values should be stored indefinitely
        // and if we have seconds we will use the typical remember function here.
        if ($seconds < 0) {
            $result = $cache->rememberForever($key, $callback);
        }
        else {
            $result = $cache->remember($key, $seconds, $callback);
        }

        // If this is an old cache record, we can check if the cache has been busted
        // by comparing the modification times. If this is the case, forget the
        // cache and then prompt a recycle of the results.
        if (!$isNewCache AND $this->isCacheBusted($result)) {
            $cache->forget($key);
            $isNewCache = TRUE;

            if ($seconds < 0) {
                $result = $cache->rememberForever($key, $callback);
            }
            else {
                $result = $cache->remember($key, $seconds, $callback);
            }
        }

        $this->loadedFromCache = !$isNewCache;

        return MemorySource::$cache[$key] = $result;
    }

    /**
     * Returns true if the cache for the file is busted. This only applies
     * to single record selection.
     *
     * @param  array $result
     *
     * @return bool
     */
    protected function isCacheBusted($result)
    {
        if (!$this->select) {
            return FALSE;
        }

        $mTime = $result ? array_get(reset($result), 'mTime') : null;

        [$name, $extension] = $this->select;

        $lastMTime = $this->source->lastModified(
            $this->in,
            $name,
            $extension
        );

        return $lastMTime != $mTime;
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCache()
    {
        $cache = $this->model->getCacheManager()->driver($this->cacheDriver);

        return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache;
    }

    /**
     * Get a unique cache key for the complete query.
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cacheKey ?: $this->generateCacheKey();
    }

    /**
     * Generate the unique cache key for the query.
     * @return string
     */
    public function generateCacheKey()
    {
        $payload = [];
        $payload[] = $this->select ? serialize($this->select) : '*';
        $payload[] = $this->columns ? serialize($this->columns) : '*';
        $payload[] = $this->fileMatch;
        $payload[] = $this->limit;
        $payload[] = $this->offset;

        return $this->in.$this->source->makeCacheKey(implode('-', $payload));
    }

    /**
     * Get the Closure callback used when caching queries.
     *
     * @param $columns
     *
     * @return \Closure
     */
    protected function getCacheCallback($columns)
    {
        return function () use ($columns) {
            return $this->processInitCacheData($this->getFresh($columns));
        };
    }

    /**
     * Initialize the cache data of each record.
     *
     * @param  array $data
     *
     * @return array
     */
    protected function processInitCacheData($data)
    {
        if ($data) {
            $model = get_class($this->model);

            foreach ($data as &$record) {
                $model::initCacheItem($record);
            }
        }

        return $data;
    }

    /**
     * Clears the internal request-level object cache.
     */
    public static function clearInternalCache()
    {
        MemorySource::$cache = [];
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string $method
     * @param  array $parameters
     *
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        $className = get_class($this);

        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}