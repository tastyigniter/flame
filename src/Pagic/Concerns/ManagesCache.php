<?php

namespace Igniter\Flame\Pagic\Concerns;

trait ManagesCache
{
    /**
     * The cache manager instance.
     * @var \Illuminate\Cache\CacheManager
     */
    protected static $cache;

    /**
     * @var boolean Indicated whether the object was loaded from the cache.
     */
    protected $loadedFromCache = FALSE;

    /**
     * Get the cache manager instance.
     * @return \Illuminate\Cache\CacheManager
     */
    public static function getCacheManager()
    {
        return static::$cache;
    }

    /**
     * Set the cache manager instance.
     *
     * @param  \Illuminate\Cache\CacheManager $cache
     *
     * @return void
     */
    public static function setCacheManager($cache)
    {
        static::$cache = $cache;
    }

    /**
     * Unset the cache manager for models.
     * @return void
     */
    public static function unsetCacheManager()
    {
        static::$cache = null;
    }

    /**
     * Initializes the object properties from the cached data. The extra data
     * set here becomes available as attributes set on the model after fetch.
     *
     * @param $item
     */
    public static function initCacheItem(&$item)
    {
    }

    /**
     * Returns true if the object was loaded from the cache.
     * @return boolean
     */
    public function isLoadedFromCache()
    {
        return $this->loadedFromCache;
    }

    /**
     * Returns true if the object was loaded from the cache.
     *
     * @param $value
     *
     * @return void
     */
    public function setLoadedFromCache($value)
    {
        $this->loadedFromCache = (bool)$value;
    }
}