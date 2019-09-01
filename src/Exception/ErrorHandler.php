<?php

namespace Igniter\Flame\Exception;

use Config;
use Exception;

/**
 * System Error Handler, this class handles application exception events.
 */
class ErrorHandler extends \October\Rain\Exception\ErrorHandler
{
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