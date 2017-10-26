<?php

namespace Igniter\Flame\Exception;

use \October\Rain\Exception\ApplicationException as OctoberApplicationException;

/**
 * This class represents an application exception.
 * Application exceptions are not logged in the error log.
 */
class ApplicationException extends OctoberApplicationException
{
}