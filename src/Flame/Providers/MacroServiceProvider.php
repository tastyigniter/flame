<?php

namespace Igniter\Flame\Providers;

use Igniter\Flame\Mixins\Mail as MailMixin;
use Igniter\Flame\Mixins\Router;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Mail::mixin(new MailMixin);
        Route::mixin(new Router);
    }
}
