<?php

namespace Igniter\Flame\Foundation\Exceptions;

use AjaxException;
use ApplicationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AjaxException::class,
        ApplicationException::class,
//        ModelNotFoundException::class,
//        HttpException::class,
    ];
}
