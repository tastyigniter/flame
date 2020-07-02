<?php namespace Igniter\Flame\ActivityLog\Models;

use Carbon\Carbon;
use Igniter\Flame\ActivityLog\ActivityLogger;
use Igniter\Flame\ActivityLog\Contracts\ActivityInterface;
use Igniter\Flame\Database\Builder;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Model;
use ReflectionClass;

/**
 * Activity Model Class
 *
 * @package        Igniter\Flame\ActivityLog\Models
 */
class Activity extends Model
{
    /**
     * @var array Auto-fill the created date field on insert
     */
    const CREATED_AT = 'date_added';

    const UPDATED_AT = 'date_updated';

    protected static $callbacks = [];

    protected static $activityTypes;

    /**
     * @var string The database table name
     */
    public $table = 'activities';

    /**
     * @var string The database table primary key
     */
    public $primaryKey = 'activity_id';

    public $timestamps = TRUE;

    public $casts = [
        'properties' => 'collection',
        'subject_id' => 'integer',
        'causer_id' => 'integer',
        'read_at' => 'datetime',
    ];

    public $dates = ['read_at', 'deleted_at'];

    public $relation = [
        'morphTo' => [
            'user' => [],
            'subject' => [],
            'causer' => [],
        ],
    ];

    public $class_name;

    //
    // Accessors & Mutators
    //

    public function getTitleAttribute()
    {
        $className = $this->getActivityTypeClass();
        if ($className AND method_exists($className, 'getTitle'))
            return $className::getTitle($this);

        return '';
    }

    public function getUrlAttribute()
    {
        $className = $this->getActivityTypeClass();
        if ($className AND method_exists($className, 'getUrl'))
            return $className::getUrl($this);

        return '';
    }

    public function getMessageAttribute()
    {
        $className = $this->getActivityTypeClass();
        if (!($className AND method_exists($className, 'getMessage')))
            return '';

        $message = $className::getMessage($this);

        return app(ActivityLogger::class)->replacePlaceholders($message, $this);
    }

    //
    // Events
    //

    protected function afterFetch()
    {
        $this->applyActivityTypeClassName();
    }

    //
    // Scopes
    //

    /**
     * Scope a query to only include activities by a given user.
     *
     * @param \Igniter\Flame\Database\Builder $query
     * @param Model $user
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUser(Builder $query, Model $user)
    {
        return $query
            ->where('user_type', $user->getMorphClass())
            ->where('user_id', $user->getKey());
    }

    /**
     * Scope a query to only include activities by a given causer.
     *
     * @param \Igniter\Flame\Database\Builder $query
     * @param Model $causer
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCausedBy(Builder $query, Model $causer)
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    /**
     * Scope a query to only include activities for a given subject.
     *
     * @param \Igniter\Flame\Database\Builder $query
     * @param Model $subject
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSubject(Builder $query, Model $subject)
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeWhereIsRead($query)
    {
        return $query->whereNotNull('read_at')->whereDate('read_at', '<=', Carbon::now());
    }

    public function scopeWhereIsUnread($query)
    {
        return $query->whereNull('read_at');
    }

    //
    // Helpers
    //

    public function isRead()
    {
        return $this->read_at AND $this->read_at->lte(Carbon::now());
    }

    public function isUnread()
    {
        return !$this->isRead();
    }

    public function markAsRead()
    {
        $this->read_at = Carbon::now();

        return $this;
    }

    public function markAsUnread()
    {
        $this->read_at = null;

        return $this;
    }

    public function applyActivityTypeClassName($type = null)
    {
        if (is_null($type))
            $type = $this->type;

        $className = self::getActivityType($type);
        if (!class_exists($className)) {
            $className = null;
        }

        $this->class_name = $className;
    }

    public function getActivityTypeClass()
    {
        return $this->class_name;
    }

    //
    // Registration
    //

    /**
     * Returns a list of the registered activity types.
     * @return array
     */
    public static function getActivityTypes()
    {
        if (self::$activityTypes === null) {
            (new static)->loadActivityTypes();
        }

        return self::$activityTypes;
    }

    /**
     * Returns a registered activity types.
     * @param string $type
     * @return string
     */
    public static function getActivityType($type)
    {
        foreach (self::getActivityTypes() as $className => $types) {
            if (in_array($type, $types))
                return $className;
        }
    }

    /**
     * Loads registered activity types from extensions
     * @return void
     */
    public function loadActivityTypes()
    {
        if (!static::$activityTypes) {
            static::$activityTypes = [];
        }

        foreach (static::$callbacks as $callback) {
            $callback($this);
        }
    }

    /**
     * Registers the activity types.
     * @param array $definitions
     */
    public function registerActivityTypes(array $definitions)
    {
        foreach ($definitions as $className => $types) {
            if (!is_string($className)) {
                Log::error('Registering activityTypes using array of class names has been deprecated, use activityTypeClassName => activityTypeName or activityTypeClassName => [activityTypeName]');
                continue;
            }

            $this->registerActivityType($className, $types);
        }
    }

    public function registerActivityType($className, $types)
    {
        if (!(new ReflectionClass($className))->implementsInterface(ActivityInterface::class))
            throw new InvalidArgumentException('Activity type '.$className.' must implement '.ActivityInterface::class);

        if (!is_array($types))
            $types = [$types];

        static::$activityTypes[$className] = $types;
    }

    /**
     * Registers a callback function that defines activity types.
     * The callback function should register permissions by calling the manager's
     * registerActivityTypes() function. The manager instance is passed to the
     * callback function as an argument. Usage:
     * <pre>
     *   Resource::registerCallback(function($manager){
     *       $manager->registerActivityTypes([...]);
     *   });
     * </pre>
     *
     * @param callable $callback A callable function.
     */
    public static function registerCallback(callable $callback)
    {
        self::$callbacks[] = $callback;
    }
}