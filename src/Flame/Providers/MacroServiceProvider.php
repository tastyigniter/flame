<?php

namespace Igniter\Flame\Providers;

use Igniter\Flame\Mixins\Mail as MailMixin;
use Igniter\Flame\Mixins\Router;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MacroServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function boot()
    {
        $this->callAfterResolving('mail.manager', function () {
            Mail::mixin(new MailMixin);
        });

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
