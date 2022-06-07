<?php

namespace Igniter\Flame\Providers;

//use Igniter\Flame\Mixins\Mail as MailMixin;
use Igniter\Flame\Mixins\Router;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

//use Illuminate\Support\Facades\Mail;

class MacroServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
//        Mail::mixin(new MailMixin);
        Route::mixin(new Router);
    }

    public function provides()
    {
        return [
            'mail.manager',
            'router',
        ];
    }
}
