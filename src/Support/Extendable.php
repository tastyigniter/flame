<?php

namespace Igniter\Flame\Support;

use Igniter\Flame\Traits\ExtendableTrait;

/**
 * Extendable Class
 *
 * If a class extends this class, it will enable support for using "Private traits".
 *
 * Usage:
 *
 *     public $implement = ['Path\To\Some\Namespace\Class'];
 *
 * Based on october\extension Extendable Class
 * @link https://github.com/octobercms/library/tree/master/src/Extension/Extendable.php
 */
class Extendable
{
    use ExtendableTrait;

    public $implement;

    public function __construct()
    {
        $this->extendableConstruct();
    }

    public function __get($name)
    {
        return $this->extendableGet($name);
    }

    public function __set($name, $value)
    {
        $this->extendableSet($name, $value);
    }

    public function __call($name, $params)
    {
        return $this->extendableCall($name, $params);
    }

    public static function __callStatic($name, $params)
    {
        return self::extendableCallStatic($name, $params);
    }

    public static function extend(callable $callback)
    {
        self::extendableExtendCallback($callback);
    }
}