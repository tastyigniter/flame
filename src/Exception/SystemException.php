<?php

namespace Igniter\Flame\Exception;

use October\Rain\Exception\SystemException as OctoberSystemException;

/**
 * This class represents a critical system exception.
 * System exceptions are logged in the error log.
 */
class SystemException extends OctoberSystemException
{
}