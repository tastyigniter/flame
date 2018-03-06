<?php

namespace Igniter\Flame\Foundation\Exceptions;

use ApplicationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use October\Rain\Foundation\Exception\Handler as OctoberHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends OctoberHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
//        AjaxException::class,
        ApplicationException::class,
        ModelNotFoundException::class,
        HttpException::class,
    ];

    /**
     * All of the register exception handlers.
     *
     * @var array
     */
    protected $handlers = [];
}
