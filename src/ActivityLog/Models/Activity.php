<?php namespace Igniter\Flame\ActivityLog\Models;

use Igniter\Flame\Database\Builder;
use Model;

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

    /**
     * @var string The database table name
     */
    public $table = 'activities';

    /**
     * @var string The database table primary key
     */
    public $primaryKey = 'activity_id';

    protected $fillable = [
        'domain',
        'context',
        'user',
        'user_id',
        'action',
        'message',
        'status',
        'date_added',
    ];

    public $timestamps = TRUE;

    public $casts = [
        'properties' => 'collection',
    ];

    public $relation = [
        'morphTo' => [
            'subject' => [],
            'causer'  => [],
        ],
    ];

    //
    // Scopes
    //

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
}