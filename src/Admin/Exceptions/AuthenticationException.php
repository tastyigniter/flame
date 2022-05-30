<?php

namespace Igniter\Admin\Exceptions;

use Igniter\Admin\Facades\Admin;
use Illuminate\Auth\AuthenticationException as Exception;

class AuthenticationException extends Exception
{
    public function render($request)
    {
        return $request->expectsJson()
            ? response()->json(['message' => $this->getMessage()], 403)
            : Admin::redirectGuest('login');
    }
}
