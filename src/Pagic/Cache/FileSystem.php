<?php

namespace Igniter\Flame\Pagic\Cache;

use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use RuntimeException;

class FileSystem
{
    protected $path;

    protected $options;

    protected $dataCacheKey = 'php-file-data';

    /**
     * @param $path string The cache file path
     */
    public function __construct($path = null)
    {
        $this->path = $path ?? storage_path().'/system/cache/';
    }

    public function getCacheKey($name, $hashName = false)
    {
        $hash = md5($name);
        $result = $this->path.'/';
        if ($hashName)
            return $result.$hash.'.php';

        $result .= substr($hash, 0, 3).'/';
        $result .= substr($hash, 3, 3).'/';

        return $result.basename($name);
    }

    public function load($key)
    {
        if (File::exists($key)) {
            include_once $key;
        }
    }

    public function write($path, $content)
    {
        $dir = dirname($path);
        if (!File::isDirectory($dir) && !File::makeDirectory($dir, 0777, true))
            throw new RuntimeException(sprintf('Unable to create the cache directory (%s).', $dir));

        $tmpFile = tempnam($dir, basename($path));
        if (@file_put_contents($tmpFile, $content) === false)
            throw new RuntimeException(sprintf('Failed to write cache file "%s".', $tmpFile));

        if (!@rename($tmpFile, $path))
            throw new RuntimeException(sprintf('Failed to write cache file "%s".', $path));

        File::chmod($path);

        // Compile cached file into bytecode cache
        if (Config::get('system.forceBytecodeInvalidation', false)) {
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            }
            elseif (function_exists('apc_compile_file')) {
                apc_compile_file($path);
            }
        }
    }

    public function getTimestamp($key)
    {
        if (!File::exists($key))
            return 0;

        return (int)filemtime($key);
    }

    public function getCached($filePath = null)
    {
        $cached = Cache::get($this->dataCacheKey, false);

        if (
            $cached !== false &&
            ($cached = @unserialize(@base64_decode($cached))) !== false
        ) {
            if (is_null($filePath))
                return $cached;

            if (array_key_exists($filePath, $cached)) {
                return $cached[$filePath];
            }
        }

        return null;
    }

    /**
     * Stores result data inside cache.
     *
     * @param $filePath
     * @param $cacheItem
     *
     * @return void
     */
    public function storeCached($filePath, $cacheItem)
    {
        $cached = $this->getCached() ?: [];
        $cached[$filePath] = $cacheItem;

        Cache::put($this->dataCacheKey, base64_encode(serialize($cached)), now()->addDay());
    }
}
