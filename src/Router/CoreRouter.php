<?php

namespace Igniter\Flame\Router;

use Illuminate\Http\Request;
use Illuminate\Routing\Router as IlluminateRouter;

class CoreRouter extends IlluminateRouter
{
    /**
     * Dispatch the request to the application.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function dispatch(Request $request)
    {
        $this->currentRequest = $request;

        $this->events->dispatch('router.before', [$request]);

        $response = $this->dispatchToRoute($request);

        $this->events->dispatch('router.after', [$request, $response]);

        return $response;
    }

    /**
     * Register a new "before" filter with the router.
     *
     * @param string|callable $callback
     * @return void
     */
    public function before($callback)
    {
        $this->events->listen('router.before', $callback);
    }

    /**
     * Register a new "after" filter with the router.
     *
     * @param string|callable $callback
     * @return void
     */
    public function after($callback)
    {
        $this->events->listen('router.after', $callback);
    }
}
