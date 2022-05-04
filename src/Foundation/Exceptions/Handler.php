<?php

namespace Igniter\Flame\Foundation\Exceptions;

use Closure;
use Exception;
use Igniter\Flame\Exception\AjaxException;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as IlluminateHandler;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends IlluminateHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AjaxException::class,
        ApplicationException::class,
        ModelNotFoundException::class,
        HttpException::class,
        ValidationException::class,
    ];

    /**
     * All of the register exception handlers.
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * Report or log an exception.
     *
     * @param \Throwable $e
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $e)
    {
        if (!class_exists('Event')) {
            return;
        }

        /**
         * @event exception.beforeReport
         * Fires before the exception has been reported
         *
         * Example usage (prevents the reporting of a given exception)
         *
         *     Event::listen('exception.report', function (\Exception $exception) {
         *         if ($exception instanceof \My\Custom\Exception) {
         *             return false;
         *         }
         *     });
         */
        if (Event::fire('exception.beforeReport', [$e], true) === false) {
            return;
        }

        if ($this->shouldntReport($e)) {
            return;
        }

        if (class_exists('Log')) {
            Log::error($e);
        }

        /**
         * @event exception.report
         * Fired after the exception has been reported
         *
         * Example usage (performs additional reporting on the exception)
         *
         *     Event::listen('exception.report', function (\Exception $exception) {
         *         app('sentry')->captureException($exception);
         *     });
         */
        Event::fire('exception.report', [$e]);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Throwable $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $e)
    {
        if (!class_exists('Event')) {
            return parent::render($request, $e);
        }

        $statusCode = $this->getStatusCode($e);
        $response = $this->callCustomHandlers($e);

        if (!is_null($response)) {
            if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
                return $response;
            }

            return Response::make($response, $statusCode);
        }

        if ($event = Event::fire('exception.beforeRender', [$e, $statusCode, $request], true)) {
            return Response::make($event, $statusCode);
        }

        return parent::render($request, $e);
    }

    /**
     * Checks if the exception implements the HttpExceptionInterface, or returns
     * as generic 500 error code for a server side error.
     * @param \Throwable $exception
     * @return int
     */
    protected function getStatusCode($exception)
    {
        if ($exception instanceof HttpExceptionInterface) {
            $code = $exception->getStatusCode();
        }
        elseif ($exception instanceof AjaxException) {
            $code = 406;
        }
        else {
            $code = 500;
        }

        return $code;
    }

    /**
     * Get the default context variables for logging.
     *
     * @return array
     */
    protected function context()
    {
        return [];
    }

    //
    // Custom handlers
    //

    /**
     * Register an application error handler.
     *
     * @param \Closure $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        array_unshift($this->handlers, $callback);
    }

    /**
     * Handle the given exception.
     *
     * @param \Throwable $exception
     * @param bool $fromConsole
     */
    protected function callCustomHandlers($exception, $fromConsole = false)
    {
        foreach ($this->handlers as $handler) {
            // If this exception handler does not handle the given exception, we will just
            // go the next one. A handler may type-hint an exception that it handles so
            //  we can have more granularity on the error handling for the developer.
            if (!$this->handlesException($handler, $exception)) {
                continue;
            }

            $code = $this->getStatusCode($exception);

            // We will wrap this handler in a try / catch and avoid white screens of death
            // if any exceptions are thrown from a handler itself. This way we will get
            // at least some errors, and avoid errors with no data or not log writes.
            try {
                $response = $handler($exception, $code, $fromConsole);
            }
            catch (Exception $e) {
                $response = $this->convertExceptionToResponse($e);
            }
            // If this handler returns a "non-null" response, we will return it so it will
            // get sent back to the browsers. Once the handler returns a valid response
            // we will cease iterating through them and calling these other handlers.
            if (isset($response) && !is_null($response)) {
                return $response;
            }
        }
    }

    /**
     * Determine if the given handler handles this exception.
     *
     * @param \Closure $handler
     * @param \Throwable $exception
     * @return bool
     */
    protected function handlesException(Closure $handler, $exception)
    {
        $reflection = new ReflectionFunction($handler);

        return $reflection->getNumberOfParameters() == 0 || $this->hints($reflection, $exception);
    }

    /**
     * Determine if the given handler type hints the exception.
     *
     * @param \ReflectionFunction $reflection
     * @param \Throwable $exception
     * @return bool
     */
    protected function hints(ReflectionFunction $reflection, $exception)
    {
        $parameters = $reflection->getParameters();
        $expected = $parameters[0];

        try {
            return (new ReflectionClass($expected->getType()->getName()))
                ->isInstance($exception);
        }
        catch (Throwable $t) {
            return false;
        }
    }
}
