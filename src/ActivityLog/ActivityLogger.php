<?php namespace Igniter\Flame\ActivityLog;

use Exception;
use Igniter\Flame\ActivityLog\Models\Activity;
use Igniter\Flame\Database\Model;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Traits\Macroable;

class ActivityLogger
{
    use Macroable;

    public $authDriver;

    /** @var \Igniter\Flame\Auth\Manager */
    protected $auth;

    protected $logName = '';

    /** @var bool */
    protected $logEnabled;

    /** @var Model */
    protected $performedOn;

    /** @var Model */
    protected $causedBy;

    /** @var \Illuminate\Support\Collection */
    protected $properties;

    public function __construct(Application $app)
    {
        $this->auth = $app->runningInAdmin()
            ? $app['admin.auth'] : $app['auth'];

        $this->causedBy = $this->auth->user();

        $this->properties = collect();
        $this->logName = $app['config']->get('system.activityLogName', 'default');
        $this->logEnabled = $app['config']->get('system.activityLogEnabled', TRUE);
    }

    /**
     * @param Model $model
     *
     * @return $this
     */
    public function performedOn(Model $model)
    {
        $this->performedOn = $model;

        return $this;
    }

    /**
     * @param Model|int|string $modelOrId
     *
     * @return $this
     */
    public function causedBy($modelOrId)
    {
        $model = $this->normalizeCauser($modelOrId);

        $this->causedBy = $model;

        return $this;
    }

    /**
     * @param array|\Illuminate\Support\Collection $properties
     *
     * @return $this
     */
    public function withProperties($properties)
    {
        $this->properties = collect($properties);

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function withProperty($key, $value)
    {
        $this->properties->put($key, $value);

        return $this;
    }

    public function useLog($logName)
    {
        $this->logName = $logName;

        return $this;
    }

    /**
     * @param string $message
     *
     * @return null|mixed
     */
    public function log($message)
    {
        if (!$this->logEnabled) {
            return FALSE;
        }

        $activity = $this->getModelInstance();

        if ($this->performedOn) {
            $activity->subject()->associate($this->performedOn);
        }

        if ($this->causedBy) {
            $activity->causer()->associate($this->causedBy);
        }

        $activity->properties = $this->properties;
        $activity->message = $this->replacePlaceholders($message, $activity);
        $activity->log_name = $this->logName;
        $activity->save();

        return $activity;
    }

    /**
     * @return \Igniter\Flame\ActivityLog\Models\Activity
     */
    public function getModelInstance()
    {
        return new Activity;
    }

    /**
     * @param Model|int|string $modelOrId
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function normalizeCauser($modelOrId)
    {
        if ($modelOrId instanceof Model) {
            return $modelOrId;
        }

        $model = $this->auth->getById($modelOrId);

        if ($model) {
            return $model;
        }

        throw new Exception("Could not determine a user with identifier '{$modelOrId}''.");
    }

    public function replacePlaceholders($message, Activity $activity)
    {
        return preg_replace_callback('/:[a-z0-9._-]+/i', function ($match) use ($activity) {
            $match = $match[0];
            preg_match('/:(.*?)\./', $match, $match2);

            if (isset($match2[1]) AND !in_array($match2[1], ['subject', 'causer', 'properties'])) {
                return $match;
            }

            $propertyName = substr($match, strpos($match, '.') + 1);
            $attributeValue = $activity->{$match2[1]};
            if (is_null($attributeValue)) {
                return $match;
            }

            $attributeValue = $attributeValue->toArray();

            return array_get($attributeValue, $propertyName, $match);
        }, $message);
    }
}