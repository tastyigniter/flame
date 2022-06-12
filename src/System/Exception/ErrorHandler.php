<?php

namespace Igniter\System\Exception;

use Exception;
use Igniter\Flame\Exception\AjaxException;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Exception\BaseException;
use Igniter\Flame\Igniter;
use Igniter\Main\Classes\MainController;
use Igniter\Main\Classes\Router;
use Igniter\Main\Classes\ThemeManager;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * System Error Handler, this class handles application exception events.
 */
class ErrorHandler
{
    /**
     * @var mixed A prepared mask exception used to mask any exception fired.
     */
    protected static $activeMask;

    /**
     * @var array A collection of masks, so multiples can be applied in order.
     */
    protected static $maskLayers = [];

    public function handleException(Throwable $proposedException)
    {
        // Disable the error handler for test and CLI environment
        if (App::runningUnitTests() || App::runningInConsole()) {
            return;
        }

        if ($proposedException->getPrevious() instanceof TokenMismatchException)
            $proposedException = new HttpException(419, lang('igniter::admin.alert_invalid_csrf_token'), $proposedException->getPrevious());

        // Detect AJAX request and use error 500
        if (Request::ajax()) {
            return $proposedException instanceof AjaxException
                ? $proposedException->getContents()
                : static::getDetailedMessage($proposedException);
        }

        $this->beforeHandleError($proposedException);

        // Clear the output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Friendly error pages are used
        if (($customError = $this->handleCustomError()) !== null) {
            return $customError;
        }

        // If the exception is already our brand, use it.
        if ($proposedException instanceof BaseException) {
            $exception = $proposedException;
        }
        // If there is an active mask prepared, use that.
        elseif (static::$activeMask !== null) {
            $exception = static::$activeMask;
            $exception->setMask($proposedException);
        }
        // Otherwise we should mask it with our own default scent.
        else {
            $exception = new ApplicationException($proposedException->getMessage(), 0);
            $exception->setMask($proposedException);
        }

        return $this->handleDetailedError($exception);
    }

    /**
     * Prepares a mask exception to be used when any exception fires.
     * @param Exception $exception The mask exception.
     * @return void
     */
    public static function applyMask(Exception $exception)
    {
        if (static::$activeMask !== null) {
            static::$maskLayers[] = static::$activeMask;
        }

        static::$activeMask = $exception;
    }

    /**
     * Destroys the prepared mask by applyMask()
     * @return void
     */
    public static function removeMask()
    {
        if (count(static::$maskLayers) > 0) {
            static::$activeMask = array_pop(static::$maskLayers);
        }
        else {
            static::$activeMask = null;
        }
    }

    /**
     * Returns a more descriptive error message if application
     * debug mode is turned on.
     * @param Throwable $exception
     * @return string
     */
    public static function getDetailedMessage($exception)
    {
        $message = $exception->getMessage();

        if (!($exception instanceof ApplicationException) && Config::get('app.debug', false)) {
            $message = sprintf('"%s" on line %s of %s',
                $exception->getMessage(),
                $exception->getLine(),
                $exception->getFile()
            );

            $message .= $exception->getTraceAsString();
        }

        return $message;
    }

    //
    // Overrides
    //

    public function beforeHandleError($exception)
    {
        if ($exception instanceof ApplicationException) {
            Log::error($exception);
        }
    }

    /**
     * Looks up an error page using the route "/error". If the route does not
     * exist, this function will use the error view found in the MAIN app.
     * @return mixed Error page contents.
     */
    public function handleCustomError()
    {
        if (Config::get('app.debug', false)) {
            return false;
        }

        if (!Igniter::hasDatabase() || !$theme = resolve(ThemeManager::class)->getActiveTheme())
            return View::make('igniter.main::error');

        $router = new Router($theme);

        // Use the default view if no "/error" URL is found.
        if (!$router || !$router->findByUrl('/error')) {
            return View::make('igniter.main::error');
        }

        // Route to the main error page.
        $controller = new MainController($theme);
        $result = $controller->remap('/error', []);

        // Extract content from response object
        if ($result instanceof Response) {
            $result = $result->getContent();
        }

        return $result;
    }

    /**
     * Displays the detailed system exception page.
     * @return View Object containing the error page.
     */
    public function handleDetailedError($exception)
    {
    }
}
