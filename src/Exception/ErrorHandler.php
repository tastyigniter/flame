<?php

namespace Igniter\Flame\Exception;

use Config;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

/**
 * System Error Handler, this class handles application exception events.
 */
class ErrorHandler extends \October\Rain\Exception\ErrorHandler
{
    public function handleException(Exception $proposedException)
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
     * Returns a more descriptive error message if application
     * debug mode is turned on.
     * @param Exception $exception
     * @return string
     */
    public static function getDetailedMessage($exception)
    {
        $message = $exception->getMessage();

        if (!($exception instanceof ApplicationException) && Config::get('app.debug', FALSE)) {
            $message = sprintf('"%s" on line %s of %s',
                $exception->getMessage(),
                $exception->getLine(),
                $exception->getFile()
            );

            $message .= $exception->getTraceAsString();
        }

        return $message;
    }
}
