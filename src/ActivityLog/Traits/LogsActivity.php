<?php namespace Igniter\Flame\ActivityLog\Traits;

traceLog('LogsActivity traits has been Deprecated. Use activity()->logActivity() instead');

use App;
use Igniter\Flame\ActivityLog\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * LogsActivity model trait
 *
 **
 * In the model class definition:
 *
 *   use \Igniter\Flame\Database\Traits\LogsActivity;
 *
 * You can log the changed attributes for all these events :
 *   protected static $logAttributes = ['name', 'text'];
 *
 * You can customize the events being logged :
 *   protected static $recordEvents = ['deleted'];
 *
 * You can customize the description :
 *   public function getMessageForEvent(string $eventName) {
 *      return "This model has been {$eventName}";
 *   };
 *
 * You can ignore changes to certain attributes :
 *   protected static $ignoreChangedAttributes = ['text'];
 *
 * You can log only the changed attributes :
 *   protected static $logOnlyDirty = true;
 *
 */
trait LogsActivity
{
    protected $enableLoggingModelsEvents = TRUE;

    protected $oldAttributes = [];

    protected static function bootLogsActivity()
    {
        static::eventsToBeRecorded()->each(function ($eventName) {
            return static::$eventName(function (Model $model) use ($eventName) {

                if ($eventName == 'updated') {
                    //temporary hold the original attributes on the model
                    //as we'll need these in the updating event
                    $oldValues = $model->replicate()->setRawAttributes($model->getOriginal());
                    $model->oldAttributes = static::logChanges($oldValues);
                }

                if (!$model->shouldLogEvent($eventName)) {
                    return;
                }

                $description = $model->getMessageForEvent($eventName);
                if ($description == '') {
                    return;
                }

                $model->getActivityLogger()
                      ->useLog($model->getLogNameToUse($eventName))
                      ->performedOn($model)
                      ->withProperties($model->attributeValuesToBeLogged($eventName))
                      ->log($description);
            });
        });
    }

    public function disableLogging()
    {
        $this->enableLoggingModelsEvents = FALSE;

        return $this;
    }

    public function enableLogging()
    {
        $this->enableLoggingModelsEvents = TRUE;

        return $this;
    }

    public function activity()
    {
        return $this->morphMany('System\Models\Activities_model', 'subject');
    }

    public function getMessageForEvent($eventName)
    {
        return $eventName;
    }

    public function getLogNameToUse($eventName = '')
    {
        return 'default';
    }

    /**
     * Get the event names that should be recorded.
     */
    protected static function eventsToBeRecorded()
    {
        if (isset(static::$recordEvents)) {
            return collect(static::$recordEvents);
        }

        $events = collect([
            'created',
            'updated',
            'deleted',
        ]);

        if (collect(class_uses(__CLASS__))->contains(SoftDeletes::class)) {
            $events->push('restored');
        }

        return $events;
    }

    public function attributesToBeIgnored()
    {
        if (!isset(static::$ignoreChangedAttributes)) {
            return [];
        }

        return static::$ignoreChangedAttributes;
    }

    /**
     * @return ActivityLogger
     */
    public function getActivityLogger()
    {
        return App::make(ActivityLogger::class);
    }

    public function shouldLogOnlyDirty()
    {
        if (!isset(static::$logOnlyDirty)) {
            return FALSE;
        }

        return static::$logOnlyDirty;
    }

    public function attributesToBeLogged()
    {
        if (!isset(static::$logAttributes)) {
            return [];
        }

        return static::$logAttributes;
    }

    protected function shouldLogEvent($eventName)
    {
        if (!$this->enableLoggingModelsEvents) {
            return FALSE;
        }

        if (!in_array($eventName, ['created', 'updated'])) {
            return TRUE;
        }

        if (array_has($this->getDirty(), 'date_deleted')) {
            if ($this->getDirty()['date_deleted'] === null) {
                return FALSE;
            }
        }

        //do not log update event if only ignored attributes are changed
        return (bool)count($this->getDirty());
    }

    public function attributeValuesToBeLogged($processingEvent)
    {
        if (!count($this->attributesToBeLogged())) {
            return [];
        }
        $properties['attributes'] = static::logChanges($this->exists ? $this->fresh() : $this);
        if (static::eventsToBeRecorded()->contains('updated') && $processingEvent == 'updated') {
            $nullProperties = array_fill_keys(array_keys($properties['attributes']), null);
            $properties['old'] = array_merge($nullProperties, $this->oldAttributes);
        }
        if ($this->shouldLogOnlyDirty() && isset($properties['old'])) {
            $properties['attributes'] = array_udiff(
                $properties['attributes'],
                $properties['old'],
                function ($new, $old) {
                    return strcmp($new, $old);
                }
            );
            $properties['old'] = collect($properties['old'])->only(array_keys($properties['attributes']))->all();
        }

        return $properties;
    }

    public static function logChanges(Model $model)
    {
        $changes = [];
        foreach ($model->attributesToBeLogged() as $attribute) {
            if (str_contains($attribute, '.')) {
                $changes += self::getRelatedModelAttributeValue($model, $attribute);
            }
            else {
                $changes += collect($model)->only($attribute)->toArray();
            }
        }

        return $changes;
    }

    protected static function getRelatedModelAttributeValue(Model $model, $attribute)
    {
        if (substr_count($attribute, '.') > 1) {
            throw new \Exception("Invalid attribute passed to {$attribute}");
        }

        [$relatedModelName, $relatedAttribute] = explode('.', $attribute);
        $relatedModel = isset($model->$relatedModelName) ? $model->$relatedModelName : $model->$relatedModelName();

        return ["{$relatedModelName}.{$relatedAttribute}" => $relatedModel->$relatedAttribute];
    }
}