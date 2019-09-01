<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Igniter\Flame\Assetic\Cache;

/**
 * A simple filesystem cache.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class FilesystemCache implements CacheInterface
{
    private $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function has($key)
    {
        return file_exists($this->dir.'/'.$key);
    }

    public function get($key)
    {
        $path = $this->dir.'/'.$key;

        if (!file_exists($path)) {
            throw new \RuntimeException('There is no cached value for '.$key);
        }

        return file_get_contents($path);
    }

    public function set($key, $value)
    {
        if (!is_dir($this->dir) && FALSE === @mkdir($this->dir, 0777, TRUE)) {
            throw new \RuntimeException('Unable to create directory '.$this->dir);
        }

        $path = $this->dir.'/'.$key;

        if (FALSE === @file_put_contents($path, $value)) {
            throw new \RuntimeException('Unable to write file '.$path);
        }
    }

    public function remove($key)
    {
        $path = $this->dir.'/'.$key;

        if (file_exists($path) && FALSE === @unlink($path)) {
            throw new \RuntimeException('Unable to remove file '.$path);
        }
    }
}
