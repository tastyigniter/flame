<?php

namespace Igniter\Flame\Exception;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ErrorHandler
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

    public function __construct(ExceptionHandler $handler)
    {
        $handler->reportable(function (Throwable $ex) {
            return $this->report($ex);
        });

        $handler->renderable(function (Throwable $ex) {
            return $this->render(request(), $ex);
        });
    }

    /**
     * Report or log an exception.
     *
     * @param \Throwable $e
     * @return false|void
     *
     * @throws \Throwable
     */
    public function report(Throwable $e)
    {
        if (!class_exists('Event'))
            return;

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
        if (Event::fire('exception.beforeReport', [$e], true) === false)
            return;

        if ($this->shouldntReport($e))
            return false;

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
     * @return \Illuminate\Http\Response|void
     */
    public function render($request, Throwable $e)
    {
        if (!class_exists('Event'))
            return;

        $statusCode = $this->getStatusCode($e);

        if ($event = Event::fire('exception.beforeRender', [$e, $statusCode, $request], true)) {
            return Response::make($event, $statusCode);
        }
    }

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param \Throwable $e
     * @return bool
     */
    protected function shouldntReport(Throwable $e)
    {
        return !is_null(Arr::first($this->dontReport, fn($type) => $e instanceof $type));
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
}
