<?php

namespace Igniter\Flame\Exception;

use October\Rain\Exception\AjaxException as OctoberAjaxException;

/**
 * This class represents an AJAX exception.
 * These are considered "smart errors" and will send http code 406,
 * so they can pass response contents.
 */
class AjaxException extends OctoberAjaxException
{
}
