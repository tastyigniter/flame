<?php

namespace Igniter\Flame\Mixins;

use Igniter\Main\Classes\MainController;

/** @mixin \Illuminate\Routing\Router */
class Router
{
    public function pagic()
    {
        return function ($uri, $name = null) {
            /** @var \Illuminate\Routing\Router $this */
            $route = $this->any($uri, [MainController::class, 'remap']);

            if (!is_null($name))
                $route->name($name);

            return $route;
        };
    }
}
