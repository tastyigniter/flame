<?php

namespace Igniter\Flame\Location;

use Closure;
use Igniter\Flame\Geolite\Contracts\CoordinatesInterface;
use Igniter\Flame\Traits\EventEmitter;
use Session;

/**
 * Location Manager Class
 */
abstract class Manager
{
    use EventEmitter;

    protected $sessionKey = 'local_info';

    /**
     * @var \Igniter\Flame\Location\Models\AbstractLocation
     */
    protected $model;

    protected $defaultLocation;

    protected $locationModel = 'Igniter\Flame\Location\Models\AbstractLocation';

    protected $loaded;

    protected static $schedulesCache;

    /**
     * The route parameter resolver callback.
     *
     * @var \Closure
     */
    protected static $locationSlugResolver;

    /**
     * Helper to get the current location instance.
     *
     * @return \Igniter\Flame\Location\Manager
     */
    public function instance()
    {
        return $this;
    }

    /**
     * Resolve the location slug from route parameter.
     *
     * @return string
     */
    public function resolveLocationSlug()
    {
        if (isset(static::$locationSlugResolver)) {
            return call_user_func(static::$locationSlugResolver);
        }
    }

    /**
     * Set the location route parameter resolver callback.
     *
     * @param \Closure $resolver
     * @return void
     */
    public function locationSlugResolver(Closure $resolver)
    {
        static::$locationSlugResolver = $resolver;
    }

    /**
     * @return mixed
     */
    public function getDefaultLocation()
    {
        return $this->defaultLocation;
    }

    /**
     * @param string $defaultLocation
     */
    public function setDefaultLocation($defaultLocation)
    {
        $this->defaultLocation = $defaultLocation;
    }

    public function getDefault()
    {
        return $this->getById($this->getDefaultLocation());
    }

    public function current()
    {
        if (!is_null($this->model))
            return $this->model;

        $model = null;
        if ($slug = $this->resolveLocationSlug())
            $model = $this->getBySlug($slug);

        if (!$model) {
            $id = $this->getSession('id');
            if (!$id OR !$model = $this->getById($id))
                $model = $this->getById($this->getDefaultLocation());
        }

        if ($model)
            $this->setCurrent($model);

        return $this->model;
    }

    /**
     * @param \Igniter\Flame\Location\Contracts\LocationInterface $locationModel
     */
    public function setCurrent(Contracts\LocationInterface $locationModel)
    {
        $this->setModel($locationModel);

        $this->putSession('id', $locationModel->getKey());

        $this->fireSystemEvent('location.current.updated', [$locationModel]);
    }

    public function getModel()
    {
        if (is_null($this->model)) {
            $this->current();
        }

        return $this->model;
    }

    public function setModel(Contracts\LocationInterface $model)
    {
        $this->model = $model;

        return $this;
    }

    public function setModelById($id)
    {
        $this->model = $this->getById($id);

        return $this;
    }

    public function setModelClass($className)
    {
        $this->locationModel = $className;
    }

    /**
     * Creates a new instance of the location model
     * @return \Igniter\Flame\Location\Models\AbstractLocation
     */
    public function createLocationModel()
    {
        $class = '\\'.ltrim($this->locationModel, '\\');
        $model = new $class();

        return $model;
    }

    /**
     * Prepares a query derived from the location model.
     */
    protected function createLocationModelQuery()
    {
        $model = $this->createLocationModel();
        $query = $model->newQuery();
        $this->extendLocationQuery($query);

        return $query;
    }

    /**
     * Extend the query used for finding the location.
     *
     * @param \Igniter\Flame\Database\Builder $query
     *
     * @return void
     */
    public function extendLocationQuery($query)
    {
    }

    /**
     * Retrieve a location by their unique identifier.
     *
     * @param mixed $identifier
     *
     * @return \Igniter\Flame\Location\Models\AbstractLocation|null
     */
    public function getById($identifier)
    {
        $query = $this->createLocationModelQuery();
        $location = $query->find($identifier);

        return $location ?: null;
    }

    /**
     * Retrieve a location by their unique slug.
     *
     * @param string $slug
     *
     * @return \Igniter\Flame\Location\Contracts\LocationInterface|null
     */
    public function getBySlug($slug)
    {
        $model = $this->createLocationModel();
        $query = $this->createLocationModelQuery();
        $location = $query->where($model->getSlugKeyName(), $slug)->first();

        return $location ?: null;
    }

    public function searchByCoordinates(CoordinatesInterface $coordinates, $limit = 20)
    {
        $query = $this->createLocationModelQuery();
        $query->select('*')->selectDistance(
            $coordinates->getLatitude(),
            $coordinates->getLongitude()
        );

        return $query->orderBy('distance', 'asc')->isEnabled()->limit($limit)->get();
    }

    /**
     * @param $type
     * @param null $days
     * @return \Igniter\Flame\Location\WorkingSchedule
     */
    public function workingSchedule($type, $days = null)
    {
        $cacheKey = sprintf('%s.%s', $this->getModel()->getKey(), $type);

        if (isset(self::$schedulesCache[$cacheKey]))
            return self::$schedulesCache[$cacheKey];

        $schedule = $this->getModel()->newWorkingSchedule($type, $days);

        self::$schedulesCache[$cacheKey] = $schedule;

        return $schedule;
    }

    //
    // Session
    //

    /**
     * Retrieves key/value pair from session data.
     *
     * @param string $key Unique key for the data store.
     * @param string $default A default value to use when value is not found.
     *
     * @return mixed
     */
    public function getSession($key = null, $default = null)
    {
        $sessionData = Session::get($this->sessionKey);

        return is_null($key) ? $sessionData : array_get($sessionData, $key, $default);
    }

    public function putSession($key, $value)
    {
        $sessionData = $this->getSession();
        $sessionData[$key] = $value;

        Session::put($this->sessionKey, $sessionData);
    }

    public function forgetSession($key = null)
    {
        if (is_null($key)) {
            Session::forget($this->sessionKey);

            return;
        }

        $sessionData = $this->getSession();
        unset($sessionData[$key]);

        Session::put($this->sessionKey, $sessionData);
    }
}
