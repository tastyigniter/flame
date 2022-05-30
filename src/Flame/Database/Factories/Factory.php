<?php

namespace Igniter\Flame\Database\Factories;

abstract class Factory extends \Illuminate\Database\Eloquent\Factories\Factory
{

    /**
     * Get the factory name for the given model name.
     *
     * @param string $modelName
     * @return string
     */
    public static function resolveFactoryName(string $modelName)
    {
        $resolver = static::$factoryNameResolver ?: function (string $modelName) {
            $modelName = str_replace('\\Models\\', '\\Database\\Factories\\', $modelName);

            return $modelName.'Factory';
        };

        return $resolver($modelName);
    }
}
