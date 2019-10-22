<?php

namespace Igniter\Flame\Translation\Models;

use Cache;
use Igniter\Flame\Database\Model;

class Translation extends Model
{
    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected static $cacheKey = 'igniter.translation';

    public $timestamps = TRUE;

    /**
     *  Table name in the database.
     * @var string
     */
    protected $table = 'language_translations';

    /**
     * @var string The database table primary key
     */
    protected $primaryKey = 'translation_id';

    /**
     *  List of variables that can be mass assigned
     * @var array
     */
    protected $fillable = ['locale', 'namespace', 'group', 'item', 'text', 'unstable'];

    public $casts = [
        'unstable' => 'boolean',
        'locked' => 'boolean',
    ];

    public static function boot()
    {
        parent::boot();

        static::saved(function (Translation $model) {
            $model->flushCache();
        });

        static::deleted(function (Translation $model) {
            $model->flushCache();
        });
    }

    public static function getCacheKey($locale, $group, $namespace)
    {
        return static::$cacheKey.".{$locale}.{$namespace}.{$group}";
    }

    /**
     *  Returns the full translation code for an entry: namespace.group.item
     * @return string
     */
    public function getCodeAttribute()
    {
        return $this->namespace === '*' ? "{$this->group}.{$this->item}" : "{$this->namespace}::{$this->group}.{$this->item}";
    }

    /**
     *  Flag this entry as Reviewed
     * @return \Igniter\Flame\Translation\Models\Translation
     */
    public function flagAsReviewed()
    {
        $this->unstable = 0;

        return $this;
    }

    /**
     *  Flag this entry as pending review.
     */
    public function flagAsUnstable()
    {
        $this->unstable = 1;

        $this->save();
    }

    /**
     *  Set the translation to the locked state
     * @return \Igniter\Flame\Translation\Models\Translation
     */
    public function lockState()
    {
        $this->locked = 1;

        return $this;
    }

    /**
     *  Check if the translation is locked
     * @return boolean
     */
    public function isLocked()
    {
        return (boolean)$this->locked;
    }

    protected function flushCache()
    {
        Cache::forget(static::getCacheKey($this->locale, $this->group, $this->namespace));
    }

    public static function getFresh($locale, $group, $namespace = null)
    {
        return static::query()
                     ->where('locale', $locale)
                     ->where('group', $group)
                     ->where('namespace', $namespace)
                     ->get();
    }

    public static function getCached($locale, $group, $namespace = null)
    {
        return Cache::rememberForever(static::getCacheKey($locale, $group, $namespace),
            function () use ($locale, $group, $namespace) {
                $result = static::getFresh($locale, $group, $namespace)->reduce(
                    function ($lines, Translation $model) {
                        array_set($lines, $model->item, $model->text);

                        return $lines;
                    }
                );

                return $result ?: [];
            }
        );
    }
}