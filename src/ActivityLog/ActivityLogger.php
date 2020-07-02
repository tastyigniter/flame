<?php namespace Igniter\Flame\ActivityLog;

use Event;
use Igniter\Flame\ActivityLog\Contracts\ActivityInterface;
use Igniter\Flame\ActivityLog\Models\Activity;
use Igniter\Flame\Auth\Models\User;
use Igniter\Flame\Database\Model;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Traits\Macroable;

class ActivityLogger
{
    use Macroable;

    public $authDriver;

    /** @var \Igniter\Flame\Auth\Manager */
    protected $sendTo;

    protected $logName = '';

    /** @var bool */
    protected $logEnabled;

    /** @var Model */
    protected $performedOn;

    /** @var Model */
    protected $causedBy;

    /** @var \Illuminate\Support\Collection */
    protected $properties;

    /**
     * @var \Illuminate\Events\Dispatcher
     */
    protected $events;

    public function __construct(Application $app)
    {
        $this->events = $app['events'];
        $this->properties = collect();
        $this->logName = $app['config']->get('system.activityLogName', 'default');
        $this->logEnabled = $app['config']->get('system.activityLogEnabled', TRUE);
    }

    public function sendTo(User $user)
    {
        $this->sendTo = $user;

        return $this;
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
     * @param Model $model
     *
     * @return $this
     */
    public function causedBy($model)
    {
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

    public function logAs($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null|mixed
     */
    public function log()
    {
        if (!$this->logEnabled)
            return FALSE;

        $activity = $this->getModelInstance();

        if ($this->sendTo)
            $activity->user()->associate($this->sendTo);

        if ($this->performedOn)
            $activity->subject()->associate($this->performedOn);

        if ($this->causedBy)
            $activity->causer()->associate($this->causedBy);

        $activity->type = $this->type;
        $activity->log_name = $this->logName;
        $activity->properties = $this->properties;
        $activity->save();

        $this->events->fire('activityLogger.logCreated', [$activity]);

        return $activity;
    }

    public function pushLog(ActivityInterface $activity, array $recipients)
    {
        $this->logActivity($activity, $recipients);
    }

    public function logActivity(ActivityInterface $activity, array $recipients)
    {
        Event::fire('activityLogger.beforeLogActivity', [$activity, $recipients]);

        $type = $activity->getType();
        $causer = $activity->getCauser();
        $subject = $activity->getSubject();
        $properties = $activity->getProperties();

        foreach ($recipients as $user) {
            $this->logAs($type)
                ->causedBy($causer)->performedOn($subject)
                ->withProperties($properties);

            if ($user instanceof User)
                $this->sendTo($user);

            $this->log();
        }

        Event::fire('activityLogger.activityLogged', [$activity, $recipients]);
    }

    /**
     * @return \Igniter\Flame\ActivityLog\Models\Activity
     */
    public function getModelInstance()
    {
        return new Activity;
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