<?php

namespace Igniter\Flame\Exception;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\View\View;
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
     * @param Exception $exception
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

    /**
     * We are about to display an error page to the user,
     * provide an opportunity to handle extra functions.
     * @return void
     */
    public function beforeHandleError($exception)
    {
    }

    /**
     * Check if using a custom error page, if so return the contents.
     * Return NULL if a custom error is not set up.
     * @return mixed Error page contents.
     */
    public function handleCustomError()
    {
    }

    /**
     * Displays the detailed system exception page.
     * @return View Object containing the error page.
     */
    public function handleDetailedError($exception)
    {
    }
}
